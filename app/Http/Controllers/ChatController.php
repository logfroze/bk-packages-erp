<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class ChatController extends Controller
{
    /**
     * Show main chat interface.
     */
    public function index(Request $request): View
    {
        $me = Auth::user();

        // The 3 factory users (and any active users)
        $users = User::where('id', '!=', $me->id)
            ->whereIn('email', ['mz.sohail764245@gmail.com', 'drghulamabbas15@gmail.com', 'bkpackges@gmail.com'])
            ->get();

        // Determine active conversation: default to group or first user or query param
        $activeType = $request->query('type', 'group'); // 'group' or 'user'
        $activeUserId = $request->query('user_id');

        $activeUser = null;
        if ($activeType === 'user' && $activeUserId) {
            $activeUser = User::find($activeUserId);
            if (! $activeUser) {
                $activeType = 'group';
            }
        }

        // Fetch messages for active conversation
        if ($activeType === 'user' && $activeUser) {
            // Mark incoming messages as read
            ChatMessage::where('sender_id', $activeUser->id)
                ->where('receiver_id', $me->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            $messages = ChatMessage::betweenUsers($me->id, $activeUser->id)
                ->with('sender')
                ->orderBy('created_at')
                ->take(150)
                ->get();
        } else {
            $activeType = 'group';
            $messages = ChatMessage::groupChannel()
                ->with('sender')
                ->orderBy('created_at')
                ->take(150)
                ->get();
        }

        // Unread message counts per user
        $unreadCounts = ChatMessage::where('receiver_id', $me->id)
            ->where('is_read', false)
            ->groupBy('sender_id')
            ->selectRaw('sender_id, count(*) as count')
            ->pluck('count', 'sender_id')
            ->toArray();

        $groupMessageCount = ChatMessage::groupChannel()->count();

        return view('chat.index', compact(
            'users',
            'activeType',
            'activeUser',
            'messages',
            'unreadCounts',
            'groupMessageCount'
        ));
    }

    /**
     * Polling endpoint: fetch new messages since after_id.
     */
    public function getMessages(Request $request): JsonResponse
    {
        $me = Auth::user();
        $activeType = $request->query('type', 'group');
        $receiverId = $request->query('receiver_id');
        $afterId = (int) $request->query('after_id', 0);

        if ($activeType === 'user' && $receiverId) {
            $query = ChatMessage::betweenUsers($me->id, (int) $receiverId)->with('sender');
            if ($afterId > 0) {
                $query->where('id', '>', $afterId);
            }
            $messages = $query->orderBy('created_at')->get();

            // Mark newly delivered incoming messages as read
            ChatMessage::where('sender_id', (int) $receiverId)
                ->where('receiver_id', $me->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        } else {
            $query = ChatMessage::groupChannel()->with('sender');
            if ($afterId > 0) {
                $query->where('id', '>', $afterId);
            }
            $messages = $query->orderBy('created_at')->get();
        }

        $formatted = $messages->map(function ($m) use ($me) {
            return $this->formatMessage($m, $me->id);
        });

        return response()->json([
            'messages' => $formatted,
            'count' => $formatted->count(),
        ]);
    }

    /**
     * Send a text message and/or multimedia attachment (Image, Audio, PDF, Document).
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $me = Auth::user();

        $validated = $request->validate([
            'active_type' => 'required|in:group,user',
            'receiver_id' => 'nullable|exists:users,id',
            'message_text' => 'nullable|string|max:5000',
            'attachment' => 'nullable|file|max:25600', // max 25MB
        ]);

        if (empty(trim($validated['message_text'] ?? '')) && ! $request->hasFile('attachment')) {
            return response()->json(['error' => 'Please type a message or select a file to send.'], 422);
        }

        $receiverId = ($validated['active_type'] === 'user') ? (int) $validated['receiver_id'] : null;

        $attachmentPath = null;
        $attachmentName = null;
        $attachmentType = null;
        $attachmentMime = null;
        $attachmentSize = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $attachmentMime = $file->getClientMimeType() ?: $file->getMimeType();
            $attachmentSize = $file->getSize();
            $ext = strtolower($file->getClientOriginalExtension());

            // Detect attachment category
            if (str_starts_with($attachmentMime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                $attachmentType = 'image';
            } elseif (str_starts_with($attachmentMime, 'audio/') || in_array($ext, ['mp3', 'wav', 'ogg', 'webm', 'm4a', 'aac'])) {
                $attachmentType = 'audio';
            } elseif ($attachmentMime === 'application/pdf' || $ext === 'pdf') {
                $attachmentType = 'pdf';
            } else {
                $attachmentType = 'document';
            }

            // Upload directly to public/uploads/chat for instant zero-config serving
            $uploadDir = public_path('uploads/chat');
            if (! File::isDirectory($uploadDir)) {
                File::makeDirectory($uploadDir, 0777, true, true);
            }

            $fileName = 'chat_'.time().'_'.uniqid().'.'.($ext ?: 'bin');
            $file->move($uploadDir, $fileName);
            $attachmentPath = '/uploads/chat/'.$fileName;
        }

        $message = ChatMessage::create([
            'sender_id' => $me->id,
            'receiver_id' => $receiverId,
            'message_text' => trim($validated['message_text'] ?? '') ?: null,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_type' => $attachmentType,
            'attachment_mime' => $attachmentMime,
            'attachment_size' => $attachmentSize,
            'is_read' => false,
        ]);

        $message->load('sender');

        return response()->json([
            'success' => true,
            'message' => $this->formatMessage($message, $me->id),
        ]);
    }

    /**
     * Global polling endpoint for top bar & sidebar badge notifications.
     */
    public function getGlobalUnread(): JsonResponse
    {
        $me = Auth::user();
        if (! $me) {
            return response()->json(['unread_total' => 0]);
        }

        $unreadTotal = ChatMessage::where('receiver_id', $me->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'unread_total' => $unreadTotal,
        ]);
    }

    /**
     * Transform ChatMessage model into client-ready payload.
     */
    protected function formatMessage(ChatMessage $m, int $myId): array
    {
        $isMe = ($m->sender_id === $myId);

        return [
            'id' => $m->id,
            'sender_id' => $m->sender_id,
            'sender_name' => $m->sender->name ?? 'User',
            'sender_role' => $m->sender->role ?? 'STAFF',
            'sender_initials' => $m->sender ? $m->sender->initials() : 'BK',
            'is_me' => $isMe,
            'message_text' => $m->message_text,
            'attachment_path' => $m->attachment_path,
            'attachment_name' => $m->attachment_name,
            'attachment_type' => $m->attachment_type,
            'formatted_size' => $m->formattedSize(),
            'formatted_time' => $m->formattedTime(),
            'is_read' => (bool) $m->is_read,
        ];
    }
}
