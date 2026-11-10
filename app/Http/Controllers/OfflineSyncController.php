<?php

namespace App\Http\Controllers;

use App\Models\ConversionRun;
use App\Models\GateRegister;
use App\Models\ProductionBatch;
use App\Models\SyncLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfflineSyncController extends Controller
{
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'online',
            'csrf_token' => csrf_token(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function replay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_uuid' => 'required|string|max:64',
            'target_path' => 'required|string|max:120',
            'payload' => 'required|array',
        ]);

        $uuid = $validated['client_uuid'];
        $path = parse_url($validated['target_path'], PHP_URL_PATH) ?: $validated['target_path'];
        $payload = $validated['payload'];

        // Idempotency check: prevent duplicate entries from offline retries
        if (SyncLog::where('client_uuid', $uuid)->exists()) {
            return response()->json([
                'status' => 'already_synced',
                'message' => 'Entry already synchronized earlier.',
            ]);
        }

        // Enforce RBAC on offline sync replay for financial/admin endpoints
        $isGatePath = $path === '/gate-register' || preg_match('#^/gate-register/\d+/weigh-exit$#', $path);
        if (! $isGatePath && ! $request->user()?->isFinancialAuthorized()) {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'Unauthorized offline action for current user role.',
            ], 403);
        }

        // Hydrate a sub-request preserving Laravel session & validator context
        $subRequest = clone $request;
        $subRequest->replace($payload);

        try {
            DB::transaction(function () use ($path, $subRequest, $uuid) {
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
                } elseif ($path === '/attendance') {
                    app(AttendanceController::class)->save($subRequest);
                } elseif ($path === '/attendance/worker') {
                    app(AttendanceController::class)->storeWorker($subRequest);
                } elseif ($path === '/attendance/advance') {
                    app(AttendanceController::class)->storeAdvance($subRequest);
                } else {
                    throw new \InvalidArgumentException("Unsupported offline sync path: {$path}");
                }

                SyncLog::create([
                    'client_uuid' => $uuid,
                    'target_path' => $path,
                    'status' => 'SYNCED',
                    'synced_at' => now(),
                ]);
            });
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'validation_error',
                'errors' => $e->errors(),
                'message' => 'Validation error while syncing entry.',
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }

        return response()->json([
            'status' => 'synced',
            'client_uuid' => $uuid,
            'message' => 'Offline entry synchronized to database.',
        ]);
    }
}

