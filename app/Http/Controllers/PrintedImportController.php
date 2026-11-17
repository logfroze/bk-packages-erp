<?php

namespace App\Http\Controllers;

use App\Models\CartonProduct;
use App\Models\CustomErpCard;
use App\Models\Party;
use App\Models\PrintedTopSheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintedImportController extends Controller
{
    public function index(Request $request): View
    {
        $cityFilter = $request->query('city');
        $query = PrintedTopSheet::with(['cartonProduct.client', 'supplier']);

        if ($cityFilter && $cityFilter !== 'ALL') {
            $query->where('sourcing_city', $cityFilter);
        }

        $topSheets = $query->orderBy('item_name')->paginate(25);

        // Summary Metrics
        $totalPiecesStock = PrintedTopSheet::sum('stock_pieces');
        $totalValuation = PrintedTopSheet::selectRaw('SUM(stock_pieces * unit_cost) as val')->value('val') ?? 0;
        $lowStockCount = PrintedTopSheet::whereRaw('stock_pieces <= minimum_threshold')->count();
        $uniqueCitiesCount = PrintedTopSheet::distinct('sourcing_city')->count('sourcing_city');

        $sourcingCities = [
            'Faisalabad',
            'Multan',
            'Lahore',
            'Rahim Yar Khan',
            'Jumber',
        ];

        $cartonProducts = CartonProduct::with('client')->orderBy('box_title')->get();
        $suppliers = Party::whereIn('classification', ['CORPORATE_CLIENT', 'LOCAL_VENDOR_SHOP'])
            ->orWhere('type', 'SUPPLIER')
            ->orderBy('name')
            ->get();
        $buyers = Party::where('type', 'CUSTOMER')->orderBy('name')->get();

        $customCards = CustomErpCard::forPage('printed_imports');

        $nextCode = 'PTS-'.str_pad((string) (PrintedTopSheet::count() + 1), 4, '0', STR_PAD_LEFT);

        return view('production.printed_imports', compact(
            'topSheets',
            'totalPiecesStock',
            'totalValuation',
            'lowStockCount',
            'uniqueCitiesCount',
            'sourcingCities',
            'cityFilter',
            'cartonProducts',
            'suppliers',
            'buyers',
            'customCards',
            'nextCode'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'item_code' => 'required|string|max:50|unique:printed_top_sheets,item_code',
            'item_name' => 'required|string|max:200',
            'carton_product_id' => 'nullable|exists:carton_products,id',
            'supplier_id' => 'nullable|exists:parties,id',
            'sourcing_city' => 'required|string|max:100',
            'stock_pieces' => 'required|integer|min:0',
            'minimum_threshold' => 'required|integer|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'location' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'custom_fields' => 'nullable|array',
            'custom_grid' => 'nullable|array',
        ]);

        PrintedTopSheet::create([
            'item_code' => strtoupper(trim($validated['item_code'])),
            'item_name' => trim($validated['item_name']),
            'carton_product_id' => $validated['carton_product_id'] ?? null,
            'supplier_id' => $validated['supplier_id'] ?? null,
            'sourcing_city' => trim($validated['sourcing_city']),
            'stock_pieces' => (int) $validated['stock_pieces'],
            'minimum_threshold' => (int) $validated['minimum_threshold'],
            'unit_cost' => (float) $validated['unit_cost'],
            'location' => $validated['location'] ? trim($validated['location']) : 'Top Sheet Bay 1',
            'notes' => $validated['notes'] ?? null,
            'custom_fields' => $validated['custom_fields'] ?? null,
            'custom_grid' => $validated['custom_grid'] ?? null,
        ]);

        return redirect()->route('production.printed_imports')->with('success', "Pre-Printed Top Sheet [{$validated['item_code']}] added successfully ({$validated['stock_pieces']} pcs from {$validated['sourcing_city']}).");
    }

    public function adjustStock(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'adjustment_type' => 'required|in:ADD,SUBTRACT,SET',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:200',
        ]);

        $sheet = PrintedTopSheet::findOrFail($id);
        $oldQty = $sheet->stock_pieces;
        $qty = (int) $validated['quantity'];

        if ($validated['adjustment_type'] === 'ADD') {
            $sheet->increment('stock_pieces', $qty);
        } elseif ($validated['adjustment_type'] === 'SUBTRACT') {
            $sheet->decrement('stock_pieces', min($qty, $sheet->stock_pieces));
        } else {
            $sheet->update(['stock_pieces' => $qty]);
        }

        return redirect()->route('production.printed_imports')->with('success', "Stock for {$sheet->item_name} adjusted from {$oldQty} to {$sheet->fresh()->stock_pieces} pcs.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $sheet = PrintedTopSheet::findOrFail($id);
        $name = $sheet->item_name;
        $sheet->delete();

        return redirect()->route('production.printed_imports')->with('success', "Top Sheet [{$name}] removed successfully.");
    }
}
