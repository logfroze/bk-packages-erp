<?php

namespace App\Http\Controllers;

use App\Models\CartonProduct;
use App\Models\DailyAttendance;
use App\Models\DocumentSequence;
use App\Models\GateRegister;
use App\Models\Party;
use App\Models\PettyCashTransaction;
use App\Models\ProductionBatch;
use App\Models\RawMaterial;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = Carbon::today();
        $cacheKey = 'dashboard_numerical_stats_' . $today->toDateString();

        // Cache heavy numerical aggregate calculations for 20 seconds
        $stats = Cache::remember($cacheKey, 20, function () use ($today) {
            $todayProduction = ProductionBatch::whereDate('updated_at', $today)->sum('produced_quantity');
            if ($todayProduction == 0) {
                $todayProduction = (float) ProductionBatch::where('status', 'RUNNING')->sum('produced_quantity');
            }

            // Consolidated single query for today's gate outward dispatch stats
            $gateStats = GateRegister::where('movement_type', 'OUTWARD_DISPATCH')
                ->whereDate('time_in', $today)
                ->selectRaw('COUNT(*) as trucks, COALESCE(SUM(total_pieces), 0) as pieces')
                ->first();

            $trucksDispatched = (int) ($gateStats->trucks ?? 0);
            $boxesDispatched = (int) ($gateStats->pieces ?? 0);

            // Consolidated single query for cash vouchers
            $cashStats = Voucher::where('payment_mode', 'CASH')
                ->selectRaw("
                    COALESCE(SUM(CASE WHEN voucher_type = 'RECEIPT_VOUCHER' THEN total_amount ELSE 0 END), 0) as receipts,
                    COALESCE(SUM(CASE WHEN voucher_type IN ('PAYMENT_VOUCHER', 'EXPENSE_VOUCHER') THEN total_amount ELSE 0 END), 0) as payments
                ")->first();

            $cashReceipts = (float) ($cashStats->receipts ?? 0);
            $cashPayments = (float) ($cashStats->payments ?? 0);
            $cashInHand = max(0, $cashReceipts - $cashPayments);

            $pendingOrdersCount = (int) ProductionBatch::whereIn('status', ['QUEUED', 'RUNNING'])->count();
            $totalFinishedStock = (int) CartonProduct::sum('stock_pieces');
            $totalRawMaterialKg = (float) RawMaterial::whereIn('category', ['KRAFT_LINER', 'FLUTING_MEDIUM', 'WHITE_TOP'])->sum('total_stock_qty');
            $totalRawMaterialTons = round($totalRawMaterialKg / 1000, 1);
            $pettyCashBalance = PettyCashTransaction::currentCashBalance();
            $presentWorkersCount = (int) DailyAttendance::whereDate('attendance_date', $today)->where('status', 'PRESENT')->count();

            return [
                'todayProduction' => (float) $todayProduction,
                'trucksDispatched' => $trucksDispatched,
                'boxesDispatched' => $boxesDispatched,
                'cashInHand' => $cashInHand,
                'pendingOrdersCount' => $pendingOrdersCount,
                'totalFinishedStock' => $totalFinishedStock,
                'totalRawMaterialKg' => $totalRawMaterialKg,
                'totalRawMaterialTons' => $totalRawMaterialTons,
                'pettyCashBalance' => $pettyCashBalance,
                'presentWorkersCount' => $presentWorkersCount,
            ];
        });

        $nextPendingBatch = ProductionBatch::with('client')->whereIn('status', ['QUEUED', 'RUNNING'])->first();

        $runningBatches = ProductionBatch::with(['cartonProduct', 'client'])
            ->where('status', 'RUNNING')
            ->get();

        $recentGates = GateRegister::with('party')
            ->latest('time_in')
            ->take(3)
            ->get()
            ->map(function ($g) {
                $direction = $g->movement_type === 'INWARD_RAW_MATERIAL' ? 'Unloaded at Gate' : 'Dispatched via Gate';
                $qtyText = $g->total_pieces ? number_format($g->total_pieces) . ' Boxes' : number_format($g->net_weight_kg / 1000, 1) . ' Tons';
                return [
                    'time' => $g->time_in->format('h:i A'),
                    'timestamp' => $g->time_in,
                    'title' => $g->movement_type === 'INWARD_RAW_MATERIAL' ? 'Raw Material Inward' : 'Vehicle Dispatch',
                    'color' => $g->movement_type === 'INWARD_RAW_MATERIAL' ? 'bg-blue-500' : 'bg-slate-800',
                    'description' => "{$g->vehicle_type} ({$g->vehicle_number}) — {$direction} ({$qtyText}) for " . ($g->party->name ?? 'Party'),
                ];
            });

        $recentVouchers = Voucher::with('party')
            ->latest('created_at')
            ->take(2)
            ->get()
            ->map(function ($v) {
                $typeLabel = $v->voucher_type === 'RECEIPT_VOUCHER' ? 'Payment Received' : ($v->voucher_type === 'SALES_INVOICE' ? 'Sales Invoice' : 'Voucher Logged');
                return [
                    'time' => $v->created_at->format('h:i A'),
                    'timestamp' => $v->created_at,
                    'title' => $typeLabel,
                    'color' => $v->voucher_type === 'RECEIPT_VOUCHER' ? 'bg-emerald-500' : 'bg-blue-500',
                    'description' => "Rs. " . number_format($v->total_amount) . " ({$v->payment_mode}) — " . ($v->party->name ?? $v->description),
                ];
            });

        $activityLog = $recentGates->concat($recentVouchers)
            ->sortByDesc('timestamp')
            ->take(5)
            ->values();

        $allParties = Party::orderBy('name')->get();
        $customers = $allParties->where('type', 'CUSTOMER')->values();
        $cartonProducts = CartonProduct::with('client')->orderBy('box_title')->get();

        // Dynamic Document sequence preview numbers (always fresh)
        $previewGatePass = DocumentSequence::previewNextNumber('GATE_PASS');
        $previewChallan = DocumentSequence::previewNextNumber('DELIVERY_CHALLAN');
        $previewInvoice = DocumentSequence::previewNextNumber('SALES_INVOICE');

        return view('dashboard', array_merge($stats, compact(
            'nextPendingBatch',
            'runningBatches',
            'activityLog',
            'customers',
            'allParties',
            'cartonProducts',
            'previewGatePass',
            'previewChallan',
            'previewInvoice'
        )));
    }
}

