<?php

namespace App\Http\Controllers;

use App\Models\CartonProduct;
use App\Models\DailyAttendance;
use App\Models\GateRegister;
use App\Models\Party;
use App\Models\ProductionBatch;
use App\Models\RawMaterial;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = Carbon::today();

        // Metrics
        $todayProduction = ProductionBatch::whereDate('updated_at', $today)->sum('produced_quantity');
        if ($todayProduction == 0) {
            $todayProduction = ProductionBatch::where('status', 'RUNNING')->sum('produced_quantity');
        }

        $trucksDispatched = GateRegister::where('movement_type', 'OUTWARD_DISPATCH')
            ->whereDate('time_in', $today)
            ->count();

        $boxesDispatched = GateRegister::where('movement_type', 'OUTWARD_DISPATCH')
            ->whereDate('time_in', $today)
            ->sum('total_pieces');

        $cashReceipts = Voucher::where('voucher_type', 'RECEIPT_VOUCHER')
            ->where('payment_mode', 'CASH')
            ->sum('total_amount');

        $cashPayments = Voucher::whereIn('voucher_type', ['PAYMENT_VOUCHER', 'EXPENSE_VOUCHER'])
            ->where('payment_mode', 'CASH')
            ->sum('total_amount');

        $cashInHand = max(0, $cashReceipts - $cashPayments);

        $pendingOrdersCount = ProductionBatch::whereIn('status', ['QUEUED', 'RUNNING'])->count();
        $nextPendingBatch = ProductionBatch::with('client')->whereIn('status', ['QUEUED', 'RUNNING'])->first();

        // Status Overview
        $runningBatches = ProductionBatch::with(['cartonProduct', 'client'])
            ->where('status', 'RUNNING')
            ->get();

        $totalFinishedStock = CartonProduct::sum('stock_pieces');
        $totalRawMaterialTons = round(RawMaterial::whereIn('category', ['KRAFT_LINER', 'FLUTING_MEDIUM', 'WHITE_TOP'])->sum('total_stock_qty') / 1000, 1);

        // Today's Activity Log (Combined clean timeline of latest 5 items)
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

        // Data for quick-action modals
        $customers = Party::where('type', 'CUSTOMER')->orderBy('name')->get();
        $allParties = Party::orderBy('name')->get();
        $cartonProducts = CartonProduct::with('client')->orderBy('box_title')->get();
        $presentWorkersCount = DailyAttendance::whereDate('attendance_date', $today)->where('status', 'PRESENT')->count();

        return view('dashboard', compact(
            'todayProduction',
            'trucksDispatched',
            'boxesDispatched',
            'cashInHand',
            'pendingOrdersCount',
            'nextPendingBatch',
            'runningBatches',
            'totalFinishedStock',
            'totalRawMaterialTons',
            'activityLog',
            'customers',
            'allParties',
            'cartonProducts',
            'presentWorkersCount'
        ));
    }
}

