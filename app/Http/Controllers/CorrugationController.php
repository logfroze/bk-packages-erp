<?php

namespace App\Http\Controllers;

use App\Models\CorrugationRun;
use App\Models\CustomErpCard;
use App\Models\PaperReel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CorrugationController extends Controller
{
    public function index(): View
    {
        $runs = CorrugationRun::with([
            'plainPaperReel1',
            'flutingWavyReel1',
            'plainPaperReel2',
            'flutingWavyReel2',
            'topPlainPaperReel',
        ])->latest()->paginate(25);

        // Summary Metrics
        $totalNalisProduced = CorrugationRun::sum('nalis_produced');
        $totalSheetsProduced = CorrugationRun::sum('completed_sheets_produced');
        $doubleWallSheets = CorrugationRun::where('board_type', 'DOUBLE_WALL')->sum('completed_sheets_produced');
        $singleWallSheets = CorrugationRun::where('board_type', 'SINGLE_WALL')->sum('completed_sheets_produced');
        $tripleWallSheets = CorrugationRun::where('board_type', 'TRIPLE_WALL')->sum('completed_sheets_produced');
        $totalPaperWeightKg = CorrugationRun::sum('total_paper_weight_kg');
        $totalWasteTrimKg = CorrugationRun::sum('waste_trim_kg');

        // Available Reels by Grade
        $plainReels = PaperReel::whereIn('paper_grade', ['TESTLINER', 'CMP_ULTRA'])
            ->where('status', 'AVAILABLE')
            ->orderBy('deckle_size_inches')
            ->get();

        $flutingReels = PaperReel::whereIn('paper_grade', ['SUPER_FLUTING', 'CMP_ULTRA'])
            ->where('status', 'AVAILABLE')
            ->orderBy('deckle_size_inches')
            ->get();

        $allReels = PaperReel::where('status', 'AVAILABLE')->orderBy('deckle_size_inches')->get();

        // 71 Standard Deckle Sizes (15.0" to 50.0" in 0.5" increments)
        $deckleSizes = [];
        for ($s = 15.0; $s <= 50.0; $s += 0.5) {
            $deckleSizes[] = number_format($s, 1);
        }

        // Custom ERP Cards for Corrugations
        $customCards = CustomErpCard::forPage('corrugations');

        $nextCode = 'COR-'.str_pad((string) (CorrugationRun::count() + 1), 4, '0', STR_PAD_LEFT);

        return view('production.corrugations', compact(
            'runs',
            'totalNalisProduced',
            'totalSheetsProduced',
            'doubleWallSheets',
            'singleWallSheets',
            'tripleWallSheets',
            'totalPaperWeightKg',
            'totalWasteTrimKg',
            'plainReels',
            'flutingReels',
            'allReels',
            'deckleSizes',
            'customCards',
            'nextCode'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'run_code' => 'required|string|max:50|unique:corrugation_runs,run_code',
            'board_type' => 'required|in:SINGLE_FACE,SINGLE_WALL,DOUBLE_WALL,TRIPLE_WALL',
            'deckle_size_inches' => 'required|numeric|min:15|max:50',
            'sheet_size' => 'nullable|string|max:100',
            'nalis_produced' => 'nullable|integer|min:0',
            'completed_sheets_produced' => 'nullable|integer|min:0',
            'plain_paper_reel_1_id' => 'nullable|exists:paper_reels,id',
            'fluting_wavy_reel_1_id' => 'nullable|exists:paper_reels,id',
            'plain_paper_reel_2_id' => 'nullable|exists:paper_reels,id',
            'fluting_wavy_reel_2_id' => 'nullable|exists:paper_reels,id',
            'top_plain_paper_reel_id' => 'nullable|exists:paper_reels,id',
            'total_paper_weight_kg' => 'nullable|numeric|min:0',
            'waste_trim_kg' => 'nullable|numeric|min:0',
            'operator_name' => 'nullable|string|max:100',
            'shift' => 'required|in:DAY,NIGHT',
            'notes' => 'nullable|string',
            'custom_fields' => 'nullable|array',
            'custom_grid' => 'nullable|array',
        ]);

        $boardType = $validated['board_type'];
        $sheets = (int) ($validated['completed_sheets_produced'] ?? 0);
        $nalis = (int) ($validated['nalis_produced'] ?? 0);

        // Auto-calculate Nalis if user entered sheets and left Nalis empty
        if ($nalis === 0 && $sheets > 0) {
            $nalis = match ($boardType) {
                'SINGLE_FACE' => $sheets,
                'SINGLE_WALL' => $sheets, // 1 Nali + 1 Plain = 3-ply
                'DOUBLE_WALL' => $sheets * 2, // 2 Nalis + 1 Plain = 5-ply (User's rule: 5 sets = 10 Nalis)
                'TRIPLE_WALL' => $sheets * 3, // 3 Nalis + 1 Plain = 7-ply
                default => $sheets,
            };
        }

        $plyMap = [
            'SINGLE_FACE' => 2,
            'SINGLE_WALL' => 3,
            'DOUBLE_WALL' => 5,
            'TRIPLE_WALL' => 7,
        ];

        CorrugationRun::create([
            'run_code' => $validated['run_code'],
            'board_type' => $boardType,
            'flute_ply_count' => $plyMap[$boardType] ?? 5,
            'plain_paper_reel_1_id' => $validated['plain_paper_reel_1_id'] ?? null,
            'fluting_wavy_reel_1_id' => $validated['fluting_wavy_reel_1_id'] ?? null,
            'plain_paper_reel_2_id' => $validated['plain_paper_reel_2_id'] ?? null,
            'fluting_wavy_reel_2_id' => $validated['fluting_wavy_reel_2_id'] ?? null,
            'top_plain_paper_reel_id' => $validated['top_plain_paper_reel_id'] ?? null,
            'deckle_size_inches' => (float) $validated['deckle_size_inches'],
            'total_paper_weight_kg' => (float) ($validated['total_paper_weight_kg'] ?? 0.0),
            'waste_trim_kg' => (float) ($validated['waste_trim_kg'] ?? 0.0),
            'nalis_produced' => $nalis,
            'completed_sheets_produced' => $sheets,
            'sheet_size' => $validated['sheet_size'] ?? null,
            'operator_name' => $validated['operator_name'] ?: 'Master Corrugator',
            'shift' => $validated['shift'],
            'status' => 'COMPLETED',
            'notes' => $validated['notes'] ?? null,
            'custom_fields' => $validated['custom_fields'] ?? null,
            'custom_grid' => $validated['custom_grid'] ?? null,
        ]);

        return redirect()->route('production.corrugations')->with('success', "Corrugation Run {$validated['run_code']} recorded successfully ({$nalis} Nalis, {$sheets} Complete Sheets).");
    }

    public function destroy(int $id): RedirectResponse
    {
        $run = CorrugationRun::findOrFail($id);
        $code = $run->run_code;
        $run->delete();

        return redirect()->route('production.corrugations')->with('success', "Corrugation Run {$code} deleted successfully.");
    }
}
