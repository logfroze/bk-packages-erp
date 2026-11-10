<?php

namespace App\Http\Controllers;

use App\Models\ConversionRun;
use App\Models\GateRegister;
use App\Models\ProductionBatch;
use App\Services\FactoryAiCopilotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AiCopilotController extends Controller
{
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array|max:10',
        ]);

        try {
            $result = FactoryAiCopilotService::ask(
                $request->user(),
                $validated['message'],
                $validated['history'] ?? []
            );

            return response()->json($result);
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

        $subRequest = clone $request;
        $subRequest->replace($payload);

        try {
            DB::transaction(function () use ($path, $subRequest) {
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

