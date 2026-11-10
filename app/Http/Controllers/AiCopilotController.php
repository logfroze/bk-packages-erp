<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\ConversionRun;
use App\Models\GateRegister;
use App\Models\ProductionBatch;
use App\Services\FactoryAiCopilotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AiCopilotController extends Controller
{
    public function conversations(Request $request): JsonResponse
    {
        $conversations = AiConversation::where('user_id', $request->user()->id)
            ->withCount('messages')
            ->with('latestMessage')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->take(50)
            ->get()
            ->map(fn (AiConversation $c) => [
                'id' => $c->id,
                'title' => $c->title,
                'messages_count' => $c->messages_count,
                'preview' => Str::limit(preg_replace('/\s+/', ' ', (string) ($c->latestMessage?->content ?? '')), 85),
                'updated_at_iso' => $c->updated_at?->toIso8601String(),
                'updated_at_human' => $c->updated_at?->format('d M, H:i'),
            ])
            ->values();

        return response()->json(['conversations' => $conversations]);
    }

    public function showConversation(Request $request, AiConversation $conversation): JsonResponse
    {
        if ((int) $conversation->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $messages = $conversation->messages()->get()->map(fn (AiMessage $m) => [
            'id' => $m->id,
            'role' => $m->role,
            'text' => $m->content,
            'pending_action' => $m->pending_action,
            'action_status' => $m->action_status,
            'created_at_iso' => $m->created_at?->toIso8601String(),
        ])->values();

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
            ],
            'messages' => $messages,
        ]);
    }

    public function destroyConversation(Request $request, AiConversation $conversation): JsonResponse
    {
        if ((int) $conversation->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $conversation->delete();

        return response()->json(['status' => 'deleted']);
    }

    public function dismissAction(Request $request, AiMessage $message): JsonResponse
    {
        $message->loadMissing('conversation');
        if (! $message->conversation || (int) $message->conversation->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        if ($message->action_status !== 'executed') {
            $message->update(['action_status' => 'dismissed']);
        }

        return response()->json(['status' => 'dismissed']);
    }

    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'conversation_id' => 'nullable|integer',
            'history' => 'nullable|array|max:10',
        ]);

        $user = $request->user();
        $conversation = null;

        if (! empty($validated['conversation_id'])) {
            $conversation = AiConversation::find($validated['conversation_id']);
            if (! $conversation) {
                return response()->json(['reply' => 'Conversation not found.', 'pending_action' => null], 404);
            }
            if ((int) $conversation->user_id !== (int) $user->id) {
                return response()->json(['reply' => 'Access denied.', 'pending_action' => null], 403);
            }
        }

        $history = $validated['history'] ?? [];
        if (empty($history) && $conversation) {
            $history = $conversation->messages()
                ->latest('id')
                ->take(6)
                ->get()
                ->reverse()
                ->map(fn (AiMessage $m) => ['role' => $m->role, 'text' => $m->content])
                ->values()
                ->all();
        }

        try {
            $result = FactoryAiCopilotService::ask(
                $user,
                $validated['message'],
                $history
            );

            $saved = DB::transaction(function () use ($conversation, $user, $validated, $result) {
                if (! $conversation) {
                    $titleText = preg_replace('/\s+/', ' ', trim($validated['message']));
                    $conversation = AiConversation::create([
                        'user_id' => $user->id,
                        'title' => Str::limit($titleText ?: 'New Conversation', 65),
                    ]);
                }

                $userMsg = AiMessage::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'user',
                    'content' => trim($validated['message']),
                ]);

                $modelMsg = AiMessage::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'model',
                    'content' => $result['reply'],
                    'pending_action' => $result['pending_action'] ?? null,
                    'action_status' => null,
                    'model_name' => $result['model'] ?? null,
                ]);

                $conversation->touch();

                return [$conversation, $userMsg, $modelMsg];
            });

            [$savedConv, $userMsg, $modelMsg] = $saved;

            return response()->json(array_merge($result, [
                'conversation_id' => $savedConv->id,
                'conversation_title' => $savedConv->title,
                'user_message_id' => $userMsg->id,
                'model_message_id' => $modelMsg->id,
            ]));
        } catch (\Throwable $e) {
            return response()->json([
                'reply' => 'AI Assistant temporarily unavailable: ' . $e->getMessage(),
                'pending_action' => null,
            ], 500);
        }
    }

    public function execute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_path' => 'required|string|max:120',
            'payload' => 'required|array',
            'message_id' => 'nullable|integer',
        ]);

        $path = parse_url($validated['target_path'], PHP_URL_PATH) ?: $validated['target_path'];
        $payload = $validated['payload'];

        // Strictly enforce RBAC permissions before executing any AI-proposed write
        $isGatePath = $path === '/gate-register' || preg_match('#^/gate-register/\d+/weigh-exit$#', $path);
        if (! $isGatePath && ! $request->user()?->isFinancialAuthorized()) {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'Access denied. Your account cannot perform financial or production updates.',
            ], 403);
        }

        $aiMessage = null;
        if (! empty($validated['message_id'])) {
            $aiMessage = AiMessage::with('conversation')->find($validated['message_id']);
            if ($aiMessage && (! $aiMessage->conversation || (int) $aiMessage->conversation->user_id !== (int) $request->user()->id)) {
                return response()->json([
                    'status' => 'forbidden',
                    'message' => 'Access denied.',
                ], 403);
            }
            if ($aiMessage && $aiMessage->action_status === 'executed') {
                return response()->json([
                    'status' => 'already_executed',
                    'message' => 'This action was already confirmed and saved earlier.',
                ]);
            }
        }

        $subRequest = clone $request;
        $subRequest->replace($payload);

        try {
            DB::transaction(function () use ($path, $subRequest, $aiMessage) {
                if ($path === '/production') {
                    app(ProductionController::class)->store($subRequest);
                } elseif (preg_match('#^/production/(\d+)/output$#', $path, $m)) {
                    $batch = ProductionBatch::findOrFail($m[1]);
                    app(ProductionController::class)->recordOutput($subRequest, $batch);
                } elseif ($path === '/production/conversion') {
                    app(ConversionController::class)->storeRun($subRequest);
                } elseif (preg_match('#^/production/conversion/(\d+)/sheets$#', $path, $m)) {
                    $run = ConversionRun::findOrFail($m[1]);
                    app(ConversionController::class)->storeSheetCut($subRequest, $run);
                } elseif (preg_match('#^/production/conversion/(\d+)/cartons$#', $path, $m)) {
                    $run = ConversionRun::findOrFail($m[1]);
                    app(ConversionController::class)->storeCartonOutput($subRequest, $run);
                } elseif ($path === '/inventory/reels') {
                    app(InventoryController::class)->storeReel($subRequest);
                } elseif ($path === '/inventory/cartons') {
                    app(InventoryController::class)->storeCarton($subRequest);
                } elseif ($path === '/gate-register') {
                    app(GateRegisterController::class)->store($subRequest);
                } elseif (preg_match('#^/gate-register/(\d+)/weigh-exit$#', $path, $m)) {
                    $gate = GateRegister::findOrFail($m[1]);
                    app(GateRegisterController::class)->updateWeightAndExit($subRequest, $gate);
                } elseif ($path === '/khata/party') {
                    app(KhataController::class)->storeParty($subRequest);
                } elseif ($path === '/khata/voucher') {
                    app(KhataController::class)->storeVoucher($subRequest);
                } elseif ($path === '/attendance/worker') {
                    app(AttendanceController::class)->storeWorker($subRequest);
                } elseif ($path === '/attendance/advance') {
                    app(AttendanceController::class)->storeAdvance($subRequest);
                } else {
                    throw new \InvalidArgumentException("Unsupported AI action path: {$path}");
                }

                if ($aiMessage) {
                    $aiMessage->update(['action_status' => 'executed']);
                }
            });
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'validation_error',
                'message' => 'Missing or invalid details: ' . collect($e->errors())->flatten()->implode(' '),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }

        return response()->json([
            'status' => 'executed',
            'message' => 'Action confirmed and saved to the factory database!',
        ]);
    }
}
