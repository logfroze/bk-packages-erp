<?php

namespace App\Http\Controllers;

use App\Models\CartonProduct;
use App\Models\ConversionRun;
use App\Models\ConversionSheetCut;
use App\Models\PaperReel;
use App\Models\Party;
use App\Models\RawMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ConversionController extends Controller
{
    public function index(): View
    {
        $runs = ConversionRun::with([
            'client',
            'cartonProduct',
            'reel1.rawMaterial',
            'reel2.rawMaterial',
            'sheetCuts',
        ])->latest()->get();

        $summary = [
            'reels_used_kg' => $runs->sum('reel_1_used_kg') + $runs->sum('reel_2_used_kg'),
            'rolls_produced' => $runs->sum('total_rolls_produced'),
            'sheets_cut' => $runs->sum('actual_sheets_cut'),
            'cartons_made' => $runs->sum('actual_cartons_produced'),
        ];

        $paperReels = PaperReel::with(['rawMaterial', 'supplier'])
            ->where('remaining_weight_kg', '>', 0)
            ->orderBy('reel_number')
            ->get();

        $cartonProducts = CartonProduct::with('client')->orderBy('box_title')->get();
        $customers = Party::where('type', 'CUSTOMER')->orderBy('name')->get();

        return view('production.conversion', compact(
            'runs',
            'summary',
            'paperReels',
            'cartonProducts',
            'customers'
        ));
    }

    public function storeRun(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'machine_name' => 'required|string|max:100',
            'carton_product_id' => 'nullable|exists:carton_products,id',
            'client_id' => 'nullable|exists:parties,id',
            'reel_1_id' => 'required|exists:paper_reels,id',
            'reel_1_used_kg' => 'required|numeric|min:0.5',
            'reel_1_rolls_produced' => 'required|integer|min:1',
            'reel_2_id' => 'nullable|exists:paper_reels,id',
            'reel_2_used_kg' => 'nullable|numeric|min:0',
            'reel_2_rolls_produced' => 'nullable|integer|min:0',
            'stage1_loss_kg' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($validated) {
            $deductReel = function (?int $reelId, float $usedKg): void {
                if (! $reelId || $usedKg <= 0) {
                    return;
                }
                $reel = PaperReel::lockForUpdate()->findOrFail($reelId);
                $actualDeduct = min((float) $reel->remaining_weight_kg, $usedKg);
                $newRem = max(0, (float) $reel->remaining_weight_kg - $actualDeduct);

                $reel->update([
                    'remaining_weight_kg' => $newRem,
                    'status' => $newRem <= 0 ? 'CONSUMED' : 'ON_CORRUGATOR',
                ]);

                if ($reel->raw_material_id && $actualDeduct > 0) {
                    RawMaterial::where('id', $reel->raw_material_id)
                        ->decrement('total_stock_qty', $actualDeduct);
                }
            };

            $r1Kg = (float) $validated['reel_1_used_kg'];
            $r1Rolls = (int) $validated['reel_1_rolls_produced'];
            $r2Id = !empty($validated['reel_2_id']) ? (int) $validated['reel_2_id'] : null;
            $r2Kg = $r2Id ? (float) ($validated['reel_2_used_kg'] ?? 0) : 0.0;
            $r2Rolls = $r2Id ? (int) ($validated['reel_2_rolls_produced'] ?? 0) : 0;

            $deductReel((int) $validated['reel_1_id'], $r1Kg);
            if ($r2Id) {
                $deductReel($r2Id, $r2Kg);
            }

            $clientId = $validated['client_id'] ?? null;
            if (! $clientId && !empty($validated['carton_product_id'])) {
                $clientId = CartonProduct::find($validated['carton_product_id'])?->client_id;
            }

            $totalRolls = $r1Rolls + $r2Rolls;
            $lastId = ConversionRun::max('id') ?? 0;
            $runCode = 'CONV-' . (1001 + $lastId);

            ConversionRun::create([
                'run_code' => $runCode,
                'run_date' => now()->toDateString(),
                'machine_name' => $validated['machine_name'],
                'client_id' => $clientId,
                'carton_product_id' => $validated['carton_product_id'] ?? null,
                'reel_1_id' => $validated['reel_1_id'],
                'reel_1_used_kg' => $r1Kg,
                'reel_1_rolls_produced' => $r1Rolls,
                'reel_2_id' => $r2Id,
                'reel_2_used_kg' => $r2Kg,
                'reel_2_rolls_produced' => $r2Rolls,
                'total_rolls_produced' => $totalRolls,
                'rolls_remaining' => $totalRolls,
                'stage1_loss_kg' => (float) ($validated['stage1_loss_kg'] ?? 0),
                'status' => 'ROLLS_TO_SHEETS',
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return back()->with('success', 'Stage 1 (Reel → Roll) recorded and raw paper reel weights updated.');
    }

    public function storeSheetCut(Request $request, ConversionRun $run): RedirectResponse
    {
        $validated = $request->validate([
            'rolls_consumed' => 'required|integer|min:1',
            'sheet_size_label' => 'required|string|max:120',
            'cut_length_inches' => 'nullable|numeric|min:1',
            'cut_width_inches' => 'nullable|numeric|min:1',
            'sheets_per_roll_formula' => 'nullable|numeric|min:1',
            'actual_sheets_cut' => 'required|integer|min:1',
            'waste_sheets' => 'nullable|integer|min:0',
        ]);

        if ($validated['rolls_consumed'] > $run->rolls_remaining) {
            return back()->withErrors([
                'rolls_consumed' => "Only {$run->rolls_remaining} rolls remaining in {$run->run_code}.",
            ]);
        }

        DB::transaction(function () use ($validated, $run) {
            $rollsConsumed = (int) $validated['rolls_consumed'];
            $actualSheets = (int) $validated['actual_sheets_cut'];
            $formula = !empty($validated['sheets_per_roll_formula'])
                ? (float) $validated['sheets_per_roll_formula']
                : null;

            $expected = $formula ? (int) round($rollsConsumed * $formula) : $actualSheets;
            $waste = isset($validated['waste_sheets'])
                ? (int) $validated['waste_sheets']
                : max(0, $expected - $actualSheets);

            ConversionSheetCut::create([
                'conversion_run_id' => $run->id,
                'rolls_consumed' => $rollsConsumed,
                'sheet_size_label' => $validated['sheet_size_label'],
                'cut_length_inches' => $validated['cut_length_inches'] ?? null,
                'cut_width_inches' => $validated['cut_width_inches'] ?? null,
                'sheets_per_roll_formula' => $formula,
                'expected_sheets' => $expected,
                'actual_sheets_cut' => $actualSheets,
                'waste_sheets' => $waste,
            ]);

            $run->rolls_remaining -= $rollsConsumed;
            $run->rolls_used_for_sheets += $rollsConsumed;
            $run->expected_sheets += $expected;
            $run->actual_sheets_cut += $actualSheets;
            $run->sheets_remaining += $actualSheets;
            $run->stage2_loss_sheets += $waste;
            $run->status = 'SHEETS_TO_CARTONS';
            $run->save();
        });

        return back()->with('success', "Stage 2 (Roll → Sheet) cut recorded for {$run->run_code}.");
    }

    public function storeCartonOutput(Request $request, ConversionRun $run): RedirectResponse
    {
        $validated = $request->validate([
            'sheets_used' => 'required|integer|min:1',
            'sheets_per_carton_rule' => 'required|numeric|min:0.1',
            'actual_cartons_produced' => 'required|integer|min:1',
            'mark_completed' => 'nullable|boolean',
        ]);

        if ($validated['sheets_used'] > $run->sheets_remaining) {
            return back()->withErrors([
                'sheets_used' => "Only {$run->sheets_remaining} sheets remaining in {$run->run_code}.",
            ]);
        }

        DB::transaction(function () use ($validated, $run, $request) {
            $sheetsUsed = (int) $validated['sheets_used'];
            $rule = (float) $validated['sheets_per_carton_rule'];
            $actualCartons = (int) $validated['actual_cartons_produced'];

            $expectedCartons = (int) floor($sheetsUsed / $rule);
            $lossCartons = max(0, $expectedCartons - $actualCartons);

            $run->sheets_used_for_cartons += $sheetsUsed;
            $run->sheets_remaining -= $sheetsUsed;
            $run->sheets_per_carton_rule = $rule;
            $run->expected_cartons += $expectedCartons;
            $run->actual_cartons_produced += $actualCartons;
            $run->stage3_loss_cartons += $lossCartons;

            if ($run->sheets_remaining <= 0 || $request->boolean('mark_completed')) {
                $run->status = 'COMPLETED';
            }
            $run->save();

            // Automatically add manufactured cartons to Finished Godown Stock
            if ($run->carton_product_id) {
                CartonProduct::where('id', $run->carton_product_id)
                    ->increment('stock_pieces', $actualCartons);
            }
        });

        return back()->with('success', "Stage 3 (Sheet → Carton) recorded: +{$validated['actual_cartons_produced']} cartons added to Godown stock!");
    }

    public function report(ConversionRun $run): View
    {
        $run->load(['client', 'cartonProduct', 'reel1.rawMaterial', 'reel2.rawMaterial', 'sheetCuts']);
        return view('production.conversion_report', compact('run'));
    }
}

