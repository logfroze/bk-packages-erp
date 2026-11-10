<?php

namespace App\Http\Controllers;

use App\Models\CartonProduct;
use App\Models\Party;
use App\Models\ProductionBatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductionController extends Controller
{
    public function index(Request $request): View
    {
        $statusFilter = $request->get('status', 'ALL');

        $query = ProductionBatch::with(['cartonProduct', 'client'])->latest();

        if ($statusFilter !== 'ALL') {
            $query->where('status', $statusFilter);
        }

        $batches = $query->get();
        $cartonProducts = CartonProduct::with('client')->orderBy('box_title')->get();
        $customers = Party::where('type', 'CUSTOMER')->orderBy('name')->get();

        return view('production.index', compact('batches', 'cartonProducts', 'customers', 'statusFilter'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'carton_product_id' => 'required|exists:carton_products,id',
            'target_quantity' => 'required|integer|min:1',
            'produced_quantity' => 'nullable|integer|min:0',
            'shift' => 'required|string',
            'current_machine' => 'required|string',
            'status' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        DB::transaction(function () use ($validated) {
            $product = CartonProduct::findOrFail($validated['carton_product_id']);
            $producedNow = (int) ($validated['produced_quantity'] ?? 0);

            $lastId = ProductionBatch::max('id') ?? 0;
            $batchNumber = 'BATCH-' . (2041 + $lastId);

            $status = $validated['status'] ?? ($producedNow >= $validated['target_quantity'] ? 'COMPLETED' : 'RUNNING');

            ProductionBatch::create([
                'batch_number' => $batchNumber,
                'carton_product_id' => $product->id,
                'client_id' => $product->client_id,
                'shift' => $validated['shift'],
                'target_quantity' => $validated['target_quantity'],
                'produced_quantity' => $producedNow,
                'current_machine' => $validated['current_machine'],
                'status' => $status,
                'due_date' => $validated['due_date'] ?? now()->addDay(),
                'started_at' => now(),
                'finished_at' => $status === 'COMPLETED' ? now() : null,
            ]);

            if ($producedNow > 0) {
                $product->increment('stock_pieces', $producedNow);
            }
        });

        return back()->with('success', 'Production batch recorded and finished stock updated.');
    }

    public function recordOutput(Request $request, ProductionBatch $batch): RedirectResponse
    {
        $validated = $request->validate([
            'add_pieces' => 'required|integer|min:1',
            'rejected_pieces' => 'nullable|integer|min:0',
            'mark_completed' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($validated, $batch) {
            $addPieces = (int) $validated['add_pieces'];
            $rejected = (int) ($validated['rejected_pieces'] ?? 0);

            $batch->produced_quantity += $addPieces;
            $batch->rejected_waste_pieces += $rejected;

            if ($request->boolean('mark_completed') || $batch->produced_quantity >= $batch->target_quantity) {
                $batch->status = 'COMPLETED';
                $batch->finished_at = now();
            } else {
                $batch->status = 'RUNNING';
                $batch->started_at = $batch->started_at ?? now();
            }

            $batch->save();

            if ($batch->cartonProduct) {
                $batch->cartonProduct->increment('stock_pieces', $addPieces);
            }
        });

        return back()->with('success', "Added {$validated['add_pieces']} pcs to {$batch->batch_number} and updated Finished Godown stock.");
    }
}

