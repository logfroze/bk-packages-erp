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

        $lastSync = $request->query('last_sync');
        $updatedMessages = collect();

        if ($afterId > 0) {
            $updatedQuery = ($activeType === 'user' && $receiverId)
                ? ChatMessage::betweenUsers($me->id, (int) $receiverId)->with('sender')
                : ChatMessage::groupChannel()->with('sender');

            $since = $lastSync ? \Carbon\Carbon::parse($lastSync) : now()->subSeconds(20);
            $updatedMessages = $updatedQuery->where('id', '<=', $afterId)
                ->where('updated_at', '>=', $since)
                ->get()
                ->map(fn ($m) => $this->formatMessage($m, $me->id));
        }

        $formatted = $messages->map(function ($m) use ($me) {
            return $this->formatMessage($m, $me->id);
        });

        return response()->json([
            'messages' => $formatted,
            'updated_messages' => $updatedMessages,
            'count' => $formatted->count(),
            'sync_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Edit a previously sent message (WhatsApp-style).
     */
    public function editMessage(Request $request, ChatMessage $message): JsonResponse
    {
        $me = Auth::user();

        if ($message->sender_id !== $me->id && ! $me->isFullAdmin()) {
            return response()->json(['error' => 'You can only edit your own messages.'], 403);
        }

        if ($message->is_deleted) {
            return response()->json(['error' => 'Deleted messages cannot be edited.'], 422);
        }

        $validated = $request->validate([
            'message_text' => 'required|string|max:5000',
        ]);

        $message->update([
            'message_text' => trim($validated['message_text']),
            'is_edited' => true,
            'edited_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $this->formatMessage($message->fresh('sender'), $me->id),
        ]);
    }

    /**
     * Delete a sent message (WhatsApp-style "Delete for Everyone").
     * Also unlinks and cleans up physical attachment files to preserve server storage.
     */
    public function deleteMessage(Request $request, ChatMessage $message): JsonResponse
    {
        $me = Auth::user();

        if ($message->sender_id !== $me->id && ! $me->isFullAdmin()) {
            return response()->json(['error' => 'You can only delete your own messages.'], 403);
        }

        // Delete physical attachment from disk if present to free disk space
        if (! empty($message->attachment_path) && ! str_starts_with($message->attachment_path, 'data:')) {
            $fullPath = public_path($message->attachment_path);
            if (File::exists($fullPath)) {
                @unlink($fullPath);
            }
        }

        $message->update([
            'is_deleted' => true,
            'deleted_by' => $me->id,
            'message_text' => null,
            'attachment_path' => null,
            'attachment_name' => null,
            'attachment_type' => null,
            'attachment_mime' => null,
            'attachment_size' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => $this->formatMessage($message->fresh('sender'), $me->id),
        ]);
    }

    /**
     * Delete multiple selected messages at once (Bulk Delete for Everyone).
     * Unlinks all physical attachment files from disk to prevent storage bloat.
     */
    public function deleteMultiple(Request $request): JsonResponse
    {
        $me = Auth::user();

        $validated = $request->validate([
            'message_ids' => 'required|array|min:1',
            'message_ids.*' => 'integer|exists:chat_messages,id',
        ]);

        $query = ChatMessage::whereIn('id', $validated['message_ids']);

        // Non-admins can only delete their own messages
        if (! $me->isFullAdmin()) {
            $query->where('sender_id', $me->id);
        }

        $messages = $query->with('sender')->get();
        $updatedList = [];

        foreach ($messages as $msg) {
            if (! empty($msg->attachment_path) && ! str_starts_with($msg->attachment_path, 'data:')) {
                $fullPath = public_path($msg->attachment_path);
                if (File::exists($fullPath)) {
                    @unlink($fullPath);
                }
            }

            $msg->update([
                'is_deleted' => true,
                'deleted_by' => $me->id,
                'message_text' => null,
                'attachment_path' => null,
                'attachment_name' => null,
                'attachment_type' => null,
                'attachment_mime' => null,
                'attachment_size' => null,
            ]);

            $updatedList[] = $this->formatMessage($msg->fresh('sender'), $me->id);
        }

        return response()->json([
            'success' => true,
            'deleted_count' => count($updatedList),
            'messages' => $updatedList,
            'message' => count($updatedList) . ' message(s) deleted.',
        ]);
    }

    /**
     * Clear / Delete the entire chat history for the active conversation.
     * Deletes all attachment files from public/uploads/chat to immediately reclaim disk space.
     */
    public function clearChat(Request $request): JsonResponse
    {
        $me = Auth::user();
        $validated = $request->validate([
            'active_type' => 'required|in:group,user',
            'receiver_id' => 'nullable|exists:users,id',
        ]);

        $query = ($validated['active_type'] === 'user' && ! empty($validated['receiver_id']))
            ? ChatMessage::betweenUsers($me->id, (int) $validated['receiver_id'])
            : ChatMessage::groupChannel();

        // 1. Reclaim disk space by deleting all physical attachment files
        $attachments = (clone $query)->whereNotNull('attachment_path')->pluck('attachment_path');
        foreach ($attachments as $filePath) {
            if ($filePath && ! str_starts_with($filePath, 'data:')) {
                $fullPath = public_path($filePath);
                if (File::exists($fullPath)) {
                    @unlink($fullPath);
                }
            }
        }

        // 2. Delete database records
        $deletedCount = $query->delete();

        return response()->json([
            'success' => true,
            'deleted_count' => $deletedCount,
            'freed_files' => $attachments->count(),
            'message' => "Chat cleared successfully ({$deletedCount} messages removed).",
        ]);
    }

    /**
     * Auto-Clean / Purge old chat messages and files older than a chosen threshold.
     */
    public function autoClean(Request $request): JsonResponse
    {
        $me = Auth::user();
        if (! $me->isFullAdmin() && ! $me->isManager()) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $validated = $request->validate([
            'period' => 'required|in:24_hours,7_days,30_days,all',
        ]);

        $cutoff = match ($validated['period']) {
            '24_hours' => now()->subDay(),
            '7_days' => now()->subDays(7),
            '30_days' => now()->subDays(30),
            'all' => now(),
        };

        $query = ChatMessage::where('created_at', '<=', $cutoff);

        // Delete physical files
        $attachments = (clone $query)->whereNotNull('attachment_path')->pluck('attachment_path');
        foreach ($attachments as $filePath) {
            if ($filePath && ! str_starts_with($filePath, 'data:')) {
                $fullPath = public_path($filePath);
                if (File::exists($fullPath)) {
                    @unlink($fullPath);
                }
            }
        }

        $deletedCount = $query->delete();

        return response()->json([
            'success' => true,
            'deleted_count' => $deletedCount,
            'freed_files' => $attachments->count(),
            'message' => "Purged {$deletedCount} old messages and {$attachments->count()} attachment files from server storage.",
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

            try {
                $uploadDir = public_path('uploads/chat');
                if (! File::isDirectory($uploadDir)) {
                    @File::makeDirectory($uploadDir, 0777, true, true);
                }

                $fileName = 'chat_'.time().'_'.uniqid().'.'.($ext ?: 'bin');
                $file->move($uploadDir, $fileName);
                $attachmentPath = '/uploads/chat/'.$fileName;
            } catch (\Throwable $e) {
                // Serverless read-only filesystem fallback: base64 Data URI
                try {
                    $content = @file_get_contents($file->getRealPath());
                    if ($content !== false) {
                        $attachmentPath = 'data:' . ($attachmentMime ?: 'application/octet-stream') . ';base64,' . base64_encode($content);
                    }
                } catch (\Throwable $ex) {}
            }
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
        $user = Auth::user();
        $canModify = $isMe || ($user && $user->isFullAdmin());

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
            'is_edited' => (bool) $m->is_edited,
            'edited_at' => $m->edited_at?->format('h:i A'),
            'is_deleted' => (bool) $m->is_deleted,
            'deleted_by' => $m->deleted_by,
            'deleted_by_me' => ($m->deleted_by === $myId),
            'can_modify' => $canModify,
        ];
    }
}
