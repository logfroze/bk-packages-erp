<?php

namespace App\Http\Controllers;

use App\Models\CartonProduct;
use App\Models\PaperReel;
use App\Models\Party;
use App\Models\PrintedTopSheet;
use App\Models\RawMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->get('tab', 'cartons');

        // Cached lookup data to eliminate roundtrip latency from Singapore DB
        $cartons = Cache::remember('all_cartons_with_client', 60, fn() => CartonProduct::with('client')->orderBy('box_title')->get());
        $cartonsByParty = $cartons->groupBy(fn($c) => $c->client?->name ?? 'Unassigned Client');

        $rawMaterials = Cache::remember('all_raw_materials', 60, fn() => RawMaterial::orderBy('name')->get());
        $paperReels = PaperReel::with(['rawMaterial', 'supplier'])->latest('id')->get();
        $totalReelsKg = $paperReels->sum('remaining_weight_kg');
        $reelsByDeckle = $paperReels->groupBy(fn($r) => (string) ($r->deckle_size_inches ?: '33.0'));

        $printedTopSheets = PrintedTopSheet::with(['cartonProduct', 'supplier'])->orderBy('item_name')->get();
        $totalTopSheetsPieces = $printedTopSheets->sum('stock_pieces');

        $customers = Cache::remember('customers_sorted', 60, fn() => Party::where('type', 'CUSTOMER')->orderBy('name')->get());
        $suppliers = Cache::remember('suppliers_sorted', 60, fn() => Party::where('type', 'SUPPLIER')->orWhere('classification', 'PAPER_MILL')->orderBy('name')->get());

        $deckleSizes = PaperReel::availableDeckleSizes();
        $paperGrades = PaperReel::availablePaperGrades();
        $sourcingCities = ['Faisalabad', 'Multan', 'Lahore', 'Rahim Yar Khan', 'Jumber', 'Okara'];

        return view('inventory.index', compact(
            'tab',
            'cartons',
            'cartonsByParty',
            'rawMaterials',
            'paperReels',
            'totalReelsKg',
            'reelsByDeckle',
            'printedTopSheets',
            'totalTopSheetsPieces',
            'customers',
            'suppliers',
            'deckleSizes',
            'paperGrades',
            'sourcingCities'
        ));
    }

    public function storeReel(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reel_number' => 'required|string|unique:paper_reels,reel_number',
            'raw_material_id' => 'required|exists:raw_materials,id',
            'supplier_id' => 'nullable|exists:parties,id',
            'paper_grade' => 'nullable|string|in:TESTLINER,SUPER_FLUTING,CMP_ULTRA',
            'deckle_size_inches' => 'nullable|numeric|min:15|max:50',
            'initial_weight_kg' => 'required|numeric|min:1',
        ]);

        DB::transaction(function () use ($validated) {
            PaperReel::create([
                'reel_number' => strtoupper(trim($validated['reel_number'])),
                'raw_material_id' => $validated['raw_material_id'],
                'supplier_id' => $validated['supplier_id'] ?? null,
                'paper_grade' => $validated['paper_grade'] ?? 'TESTLINER',
                'deckle_size_inches' => $validated['deckle_size_inches'] ?? 33.0,
                'initial_weight_kg' => $validated['initial_weight_kg'],
                'remaining_weight_kg' => $validated['initial_weight_kg'],
                'received_date' => now(),
                'status' => 'IN_STOCK',
            ]);

            RawMaterial::where('id', $validated['raw_material_id'])
                ->increment('total_stock_qty', $validated['initial_weight_kg']);
        });

        return redirect()->route('inventory.index', ['tab' => 'reels'])
            ->with('success', "Paper Reel {$validated['reel_number']} ({$validated['initial_weight_kg']} kg) added to stock in KG.");
    }

    public function updateReel(Request $request, PaperReel $reel): RedirectResponse
    {
        $validated = $request->validate([
            'reel_number' => 'required|string|unique:paper_reels,reel_number,' . $reel->id,
            'raw_material_id' => 'required|exists:raw_materials,id',
            'supplier_id' => 'nullable|exists:parties,id',
            'paper_grade' => 'nullable|string',
            'deckle_size_inches' => 'nullable|numeric|min:15|max:50',
            'initial_weight_kg' => 'required|numeric|min:1',
            'remaining_weight_kg' => 'required|numeric|min:0',
            'status' => 'required|string',
        ]);

        $diff = (float)$validated['remaining_weight_kg'] - (float)$reel->remaining_weight_kg;

        DB::transaction(function () use ($reel, $validated, $diff) {
            $reel->update([
                'reel_number' => strtoupper(trim($validated['reel_number'])),
                'raw_material_id' => $validated['raw_material_id'],
                'supplier_id' => $validated['supplier_id'] ?? null,
                'paper_grade' => $validated['paper_grade'] ?? $reel->paper_grade,
                'deckle_size_inches' => $validated['deckle_size_inches'] ?? $reel->deckle_size_inches,
                'initial_weight_kg' => $validated['initial_weight_kg'],
                'remaining_weight_kg' => $validated['remaining_weight_kg'],
                'status' => $validated['status'],
            ]);

            if ($diff != 0) {
                RawMaterial::where('id', $validated['raw_material_id'])->increment('total_stock_qty', $diff);
            }
        });

        return redirect()->route('inventory.index', ['tab' => 'reels'])
            ->with('success', "Paper Reel [{$reel->reel_number}] updated successfully.");
    }

    public function destroyReel(PaperReel $reel): RedirectResponse
    {
        $reelNum = $reel->reel_number;
        DB::transaction(function () use ($reel) {
            RawMaterial::where('id', $reel->raw_material_id)->decrement('total_stock_qty', min($reel->remaining_weight_kg, RawMaterial::find($reel->raw_material_id)->total_stock_qty ?? 0));
            $reel->delete();
        });

        return redirect()->route('inventory.index', ['tab' => 'reels'])
            ->with('success', "Paper Reel [{$reelNum}] deleted.");
    }

    public function storeCarton(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:parties,id',
            'box_title' => 'required|string|max:255',
            'ply_type' => 'required|string',
            'flute_type' => 'required|string',
            'unit_selling_price' => 'required|numeric|min:0',
            'stock_pieces' => 'nullable|integer|min:0',
        ]);

        $sku = 'CTN-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['box_title']), 0, 5)) . '-' . rand(100, 999);

        CartonProduct::create([
            'sku' => $sku,
            'client_id' => $validated['client_id'],
            'box_title' => $validated['box_title'],
            'ply_type' => $validated['ply_type'],
            'flute_type' => $validated['flute_type'],
            'unit_selling_price' => $validated['unit_selling_price'],
            'stock_pieces' => $validated['stock_pieces'] ?? 0,
        ]);

        Cache::forget('all_cartons_with_client');

        return redirect()->route('inventory.index', ['tab' => 'cartons'])
            ->with('success', "New carton specification '{$validated['box_title']}' created.");
    }

    public function updateCarton(Request $request, CartonProduct $carton): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:parties,id',
            'box_title' => 'required|string|max:255',
            'ply_type' => 'required|string',
            'flute_type' => 'required|string',
            'unit_selling_price' => 'required|numeric|min:0',
            'stock_pieces' => 'required|integer|min:0',
        ]);

        $carton->update([
            'client_id' => $validated['client_id'],
            'box_title' => $validated['box_title'],
            'ply_type' => $validated['ply_type'],
            'flute_type' => $validated['flute_type'],
            'unit_selling_price' => $validated['unit_selling_price'],
            'stock_pieces' => $validated['stock_pieces'],
        ]);

        Cache::forget('all_cartons_with_client');

        return redirect()->route('inventory.index', ['tab' => 'cartons'])
            ->with('success', "Carton spec '{$carton->box_title}' updated successfully.");
    }

    public function destroyCarton(CartonProduct $carton): RedirectResponse
    {
        $title = $carton->box_title;
        $carton->delete();

        Cache::forget('all_cartons_with_client');

        return redirect()->route('inventory.index', ['tab' => 'cartons'])
            ->with('success', "Carton spec '{$title}' deleted.");
    }

    public function storeTopSheet(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'carton_product_id' => 'nullable|exists:carton_products,id',
            'supplier_id' => 'nullable|exists:parties,id',
            'supplier_name' => 'nullable|string|max:150',
            'sourcing_city' => 'required|string|max:100',
            'stock_pieces' => 'required|integer|min:0',
            'minimum_threshold' => 'nullable|integer|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:100',
            'received_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $count = PrintedTopSheet::max('id') + 1;
        $code = 'TS-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);

        PrintedTopSheet::create([
            'item_code' => $code,
            'item_name' => trim($validated['item_name']),
            'carton_product_id' => $validated['carton_product_id'] ?? null,
            'supplier_id' => $validated['supplier_id'] ?? null,
            'supplier_name' => !empty($validated['supplier_name']) ? trim($validated['supplier_name']) : null,
            'sourcing_city' => $validated['sourcing_city'],
            'stock_pieces' => (int) $validated['stock_pieces'],
            'minimum_threshold' => $validated['minimum_threshold'] ?? 500,
            'unit_cost' => $validated['unit_cost'] ?? 0.00,
            'location' => $validated['location'] ?? 'Top Sheet Bay 1',
            'received_date' => $validated['received_date'] ?? now()->toDateString(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('inventory.index', ['tab' => 'top-sheets'])
            ->with('success', "Pre-Printed Top Sheet '{$validated['item_name']}' added ({$validated['stock_pieces']} Pcs).");
    }

    public function adjustTopSheetStock(Request $request, PrintedTopSheet $topSheet): RedirectResponse
    {
        $validated = $request->validate([
            'adjustment_type' => 'required|in:ADD,SUBTRACT,SET',
            'pieces' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $qty = (int) $validated['pieces'];
        $old = $topSheet->stock_pieces;
        $new = $old;

        if ($validated['adjustment_type'] === 'ADD') {
            $new = $old + $qty;
        } elseif ($validated['adjustment_type'] === 'SUBTRACT') {
            $new = max(0, $old - $qty);
        } else {
            $new = $qty;
        }

        $topSheet->update([
            'stock_pieces' => $new,
            'notes' => $validated['notes'] ? $topSheet->notes . ' | ' . $validated['notes'] : $topSheet->notes,
        ]);

        return redirect()->route('inventory.index', ['tab' => 'top-sheets'])
            ->with('success', "Top Sheet '{$topSheet->item_name}' stock updated: {$old} -> {$new} Pcs.");
    }
}
