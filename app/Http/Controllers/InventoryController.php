<?php

namespace App\Http\Controllers;

use App\Models\CartonProduct;
use App\Models\PaperReel;
use App\Models\Party;
use App\Models\RawMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->get('tab', 'cartons');
        $cartons = CartonProduct::with('client')->orderBy('box_title')->get();
        $rawMaterials = RawMaterial::orderBy('name')->get();
        $paperReels = PaperReel::with(['rawMaterial', 'supplier'])->latest()->get();
        $customers = Party::where('type', 'CUSTOMER')->orderBy('name')->get();
        $suppliers = Party::where('type', 'SUPPLIER')->orderBy('name')->get();

        return view('inventory.index', compact(
            'tab',
            'cartons',
            'rawMaterials',
            'paperReels',
            'customers',
            'suppliers'
        ));
    }

    public function storeReel(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reel_number' => 'required|string|unique:paper_reels,reel_number',
            'raw_material_id' => 'required|exists:raw_materials,id',
            'supplier_id' => 'nullable|exists:parties,id',
            'initial_weight_kg' => 'required|numeric|min:1',
        ]);

        DB::transaction(function () use ($validated) {
            PaperReel::create([
                'reel_number' => strtoupper($validated['reel_number']),
                'raw_material_id' => $validated['raw_material_id'],
                'supplier_id' => $validated['supplier_id'] ?? null,
                'initial_weight_kg' => $validated['initial_weight_kg'],
                'remaining_weight_kg' => $validated['initial_weight_kg'],
                'received_date' => now(),
                'status' => 'IN_STOCK',
            ]);

            RawMaterial::where('id', $validated['raw_material_id'])
                ->increment('total_stock_qty', $validated['initial_weight_kg']);
        });

        return redirect()->route('inventory.index', ['tab' => 'reels'])
            ->with('success', "Paper Reel {$validated['reel_number']} ({$validated['initial_weight_kg']} kg) added to stock.");
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

        return redirect()->route('inventory.index', ['tab' => 'cartons'])
            ->with('success', "New carton specification '{$validated['box_title']}' created.");
    }
}
