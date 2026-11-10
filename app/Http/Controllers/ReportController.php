<?php

namespace App\Http\Controllers;

use App\Models\ConversionRun;
use App\Models\DailyAttendance;
use App\Models\GateRegister;
use App\Models\Party;
use App\Models\ProductionBatch;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->get('date', now()->toDateString());

        $batchesToday = ProductionBatch::with(['cartonProduct', 'client'])
            ->whereDate('updated_at', $date)
            ->get();

        $gatesToday = GateRegister::with('party')
            ->whereDate('time_in', $date)
            ->orderBy('time_in', 'desc')
            ->get();

        $vouchersToday = Voucher::with('party')
            ->whereDate('voucher_date', $date)
            ->get();

        $attendanceToday = DailyAttendance::with('worker')
            ->whereDate('attendance_date', $date)
            ->get();

        $conversionsToday = ConversionRun::with('cartonProduct')
            ->whereDate('run_date', $date)
            ->get();

        $summary = [
            'production_pcs' => $batchesToday->sum('produced_quantity'),
            'trucks_in' => $gatesToday->where('movement_type', 'INWARD_RAW_MATERIAL')->count(),
            'trucks_out' => $gatesToday->where('movement_type', 'OUTWARD_DISPATCH')->count(),
            'dispatched_pcs' => $gatesToday->where('movement_type', 'OUTWARD_DISPATCH')->sum('total_pieces'),
            'sales_invoiced' => $vouchersToday->where('voucher_type', 'SALES_INVOICE')->sum('total_amount'),
            'cash_received' => $vouchersToday->where('voucher_type', 'RECEIPT_VOUCHER')->sum('total_amount'),
            'expenses_paid' => $vouchersToday->whereIn('voucher_type', ['EXPENSE_VOUCHER', 'PAYMENT_VOUCHER'])->sum('total_amount'),
            'workers_present' => $attendanceToday->where('status', 'PRESENT')->count(),
            'wages_accrued' => $attendanceToday->sum('daily_calculated_wage'),
            'total_receivables' => Party::where('type', 'CUSTOMER')->where('current_balance', '>', 0)->sum('current_balance'),
        ];

        return view('reports.index', compact(
            'date',
            'batchesToday',
            'gatesToday',
            'vouchersToday',
            'attendanceToday',
            'conversionsToday',
            'summary'
        ));
    }
}

