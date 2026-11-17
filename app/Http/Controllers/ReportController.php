<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ConversionRun;
use App\Models\CorrugationRun;
use App\Models\DailyAttendance;
use App\Models\GateRegister;
use App\Models\PaperReel;
use App\Models\PettyCashTransaction;
use App\Models\PrintedTopSheet;
use App\Models\ProductionBatch;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Comprehensive Day-End Progress & Financial Conclusion (Roznamcha).
     */
    public function index(Request $request): View
    {
        $date = $request->get('date', now()->toDateString());
        $data = $this->gatherDailyData($date);

        return view('reports.index', array_merge(['date' => $date], $data));
    }

    /**
     * Compact Receipt / Slip View (Thermal/POS or clean single-page printable slip).
     */
    public function receipt(Request $request): View
    {
        $date = $request->get('date', now()->toDateString());
        $data = $this->gatherDailyData($date);

        return view('reports.receipt', array_merge(['date' => $date], $data));
    }

    /**
     * Export Complete Day Conclusion to CSV / Excel spreadsheet.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $date = $request->get('date', now()->toDateString());
        $data = $this->gatherDailyData($date);

        $filename = "BK_Packages_Daily_Conclusion_{$date}.csv";

        return response()->streamDownload(function () use ($date, $data) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['BK PACKAGES OKARA — DAILY FACTORY PROGRESS & FINANCIAL CONCLUSION']);
            fputcsv($handle, ['Report Date', Carbon::parse($date)->format('d F Y (l)')]);
            fputcsv($handle, ['Generated At', now()->format('d M Y, h:i A')]);
            fputcsv($handle, ['Prepared By', Auth::user()?->name ?? 'Mirza Sohail']);
            fputcsv($handle, []);

            // 1. EXECUTIVE CONCLUSION SUMMARY
            fputcsv($handle, ['=== 1. EXECUTIVE FINANCIAL CONCLUSION ===']);
            fputcsv($handle, ['Metric', 'Value']);
            fputcsv($handle, ['Gross Invoiced Sales', 'Rs. '.number_format($data['summary']['sales_invoiced'])]);
            fputcsv($handle, ['Cash Receipts Collected', 'Rs. '.number_format($data['summary']['cash_received'])]);
            fputcsv($handle, ['Petty Cash Expenses Disbursed', 'Rs. '.number_format($data['summary']['petty_cash_spent'])]);
            fputcsv($handle, ['Labour Wages Accrued', 'Rs. '.number_format($data['summary']['wages_accrued'])]);
            fputcsv($handle, ['Net Operating Cashflow', 'Rs. '.number_format($data['summary']['net_cashflow'])]);
            fputcsv($handle, ['Closing Safe Cash Balance', 'Rs. '.number_format($data['summary']['closing_cash_balance'])]);
            fputcsv($handle, []);

            // 2. DISPATCHES & SALES
            fputcsv($handle, ['=== 2. CARTONS DISPATCHED & SALES ===']);
            fputcsv($handle, ['Gate Pass #', 'Vehicle #', 'Customer Party', 'Driver', 'Cartons (Pcs)', 'Bundles', 'Net Wt (Kg)']);
            foreach ($data['dispatches'] as $disp) {
                fputcsv($handle, [
                    $disp->gate_pass_number,
                    $disp->vehicle_number,
                    $disp->party->name ?? 'Customer',
                    $disp->driver_name ?? '-',
                    $disp->total_pieces ?? 0,
                    $disp->total_bundles ?? 0,
                    $disp->net_weight_kg ?? 0,
                ]);
            }
            fputcsv($handle, []);

            // 3. BUYING & RAW MATERIAL INWARD
            fputcsv($handle, ['=== 3. RAW MATERIAL INWARD (PAPER REELS) ===']);
            fputcsv($handle, ['Gate Pass #', 'Vehicle #', 'Supplier Mill', 'Material Summary', 'Weight (KG)', 'Weight (Tons)']);
            foreach ($data['inwardTrucks'] as $inw) {
                fputcsv($handle, [
                    $inw->gate_pass_number,
                    $inw->vehicle_number,
                    $inw->party->name ?? 'Mill Supplier',
                    $inw->material_summary ?? 'Paper Reels',
                    $inw->net_weight_kg ?? 0,
                    round(($inw->net_weight_kg ?? 0) / 1000, 2),
                ]);
            }
            fputcsv($handle, []);

            // 4. PRINTED TOP SHEETS
            fputcsv($handle, ['=== 4. OUTSOURCED PRE-PRINTED TOP SHEETS ===']);
            fputcsv($handle, ['Design / Title', 'Buyer Name', 'Sourcing City', 'Stock Added (Pcs)', 'Unit Cost (Rs)', 'Total Value (Rs)']);
            foreach ($data['topSheetsToday'] as $ts) {
                fputcsv($handle, [
                    $ts->sheet_name,
                    $ts->buyer_name,
                    $ts->sourcing_city,
                    $ts->stock_pieces,
                    $ts->unit_cost,
                    $ts->stock_pieces * $ts->unit_cost,
                ]);
            }
            fputcsv($handle, []);

            // 5. PRODUCTION & CORRUGATIONS
            fputcsv($handle, ['=== 5. PRODUCTION & CORRUGATION RUNS ===']);
            fputcsv($handle, ['Run/Batch #', 'Type / Machine', 'Specification', 'Produced Pcs/Sheets', 'Target', 'Shift']);
            foreach ($data['batchesToday'] as $b) {
                fputcsv($handle, [
                    $b->batch_number,
                    $b->current_machine,
                    $b->cartonProduct->box_title ?? 'Carton',
                    $b->produced_quantity,
                    $b->target_quantity,
                    $b->shift,
                ]);
            }
            fputcsv($handle, []);

            // 6. LABOUR & WAGES
            fputcsv($handle, ['=== 6. LABOUR ATTENDANCE & DAILY WAGES ===']);
            fputcsv($handle, ['Worker Name', 'Role', 'Status', 'Daily Wage (Rs)', 'Overtime Hrs', 'Total Earned (Rs)', 'Advance Taken (Rs)']);
            foreach ($data['attendanceToday'] as $att) {
                fputcsv($handle, [
                    $att->worker->name ?? 'Worker',
                    $att->worker->role ?? 'Operator',
                    $att->status,
                    $att->daily_calculated_wage,
                    $att->overtime_hours,
                    $att->daily_calculated_wage + $att->overtime_amount,
                    $att->advance_deducted,
                ]);
            }
            fputcsv($handle, []);

            // 7. PETTY CASH EXPENSES
            fputcsv($handle, ['=== 7. DAILY PETTY CASH EXPENSES ===']);
            fputcsv($handle, ['Code / Receipt #', 'Expense Category', 'Paid To / Purpose', 'Payment Mode', 'Amount (Rs)']);
            foreach ($data['pettyCashToday'] as $pc) {
                fputcsv($handle, [
                    $pc->transaction_code ?? '-',
                    $pc->category ?? 'General',
                    ($pc->spent_by_or_paid_to ?? 'Factory').' — '.$pc->description,
                    $pc->transaction_type,
                    $pc->amount,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * One-Click Share Daily Conclusion to the In-App Factory Team Chat Channel.
     */
    public function shareToChat(Request $request): RedirectResponse
    {
        $date = $request->input('date', now()->toDateString());
        $data = $this->gatherDailyData($date);
        $s = $data['summary'];

        $msgText = "📊 *BK PACKAGES OKARA — DAILY CONCLUSION REPORT*\n"
            .'📅 *Date:* '.Carbon::parse($date)->format('d F Y (l)')."\n"
            ."──────────────────────\n"
            .'📦 *Dispatches:* '.number_format($s['dispatched_pcs']).' Cartons ('.$s['trucks_out']." Vehicles Out)\n"
            .'📜 *Raw Material Inward:* '.number_format($s['paper_reels_weight_tons'], 1).' Tons Paper Reels ('.$s['trucks_in']." Vehicles In)\n"
            .'🎨 *Printed Imports:* '.number_format($s['top_sheets_received'])." Pcs Sourced\n"
            .'⚙️ *Production Output:* '.number_format($s['production_pcs'])." Finished Cartons\n"
            .'👥 *Labour:* '.$s['workers_present'].' Present (Rs. '.number_format($s['wages_accrued'])." Wages)\n"
            .'💵 *Daily Expenses:* Rs. '.number_format($s['petty_cash_spent'])."\n"
            ."──────────────────────\n"
            .'💰 *Total Invoiced Sales:* Rs. '.number_format($s['sales_invoiced'])."\n"
            .'📥 *Cash Received:* Rs. '.number_format($s['cash_received'])."\n"
            .'📤 *Cash Paid:* Rs. '.number_format($s['cash_paid'])."\n"
            .'📈 *Net Daily Cashflow:* Rs. '.number_format($s['net_cashflow'])."\n"
            .'🔐 *Safe Cash Balance:* Rs. '.number_format($s['closing_cash_balance'])."\n"
            ."──────────────────────\n"
            .'Prepared by: '.(Auth::user()?->name ?? 'Mirza Sohail');

        ChatMessage::create([
            'sender_id' => Auth::id(),
            'receiver_id' => null, // Group broadcast
            'message_text' => $msgText,
            'is_read' => false,
        ]);

        return back()->with('success', 'Daily conclusion posted to the Okara Factory Team Chat Channel successfully!');
    }

    /**
     * Helper to gather comprehensive daily factory data across all 6 modules.
     */
    protected function gatherDailyData(string $date): array
    {
        // 1. Sales & Dispatches
        $dispatches = GateRegister::with('party')
            ->whereDate('time_in', $date)
            ->where('movement_type', 'OUTWARD_DISPATCH')
            ->orderBy('time_in', 'desc')
            ->get();

        // 2. Buying & Raw Material Inward
        $inwardTrucks = GateRegister::with('party')
            ->whereDate('time_in', $date)
            ->where('movement_type', 'INWARD_RAW_MATERIAL')
            ->orderBy('time_in', 'desc')
            ->get();

        $paperReelsToday = PaperReel::whereDate('created_at', $date)->get();
        if ($paperReelsToday->isEmpty() && $inwardTrucks->isNotEmpty()) {
            $inwardReelKg = (float) $inwardTrucks->sum('net_weight_kg');
        } else {
            $inwardReelKg = (float) $paperReelsToday->sum('weight_kg');
        }

        // 3. Pre-Printed Top Sheets Inward
        $topSheetsToday = PrintedTopSheet::whereDate('created_at', $date)->get();
        if ($topSheetsToday->isEmpty()) {
            // Show latest stock items for overview context if none added on this specific date
            $topSheetsToday = PrintedTopSheet::latest()->take(5)->get();
        }

        // 4. Production & Corrugations
        $batchesToday = ProductionBatch::with(['cartonProduct', 'client'])
            ->whereDate('updated_at', $date)
            ->orWhere(function ($q) use ($date) {
                $q->whereDate('created_at', $date);
            })
            ->get();

        $corrugationsToday = CorrugationRun::whereDate('created_at', $date)
            ->get();

        $conversionsToday = ConversionRun::with('cartonProduct')
            ->whereDate('run_date', $date)
            ->get();

        // 5. Labour Management & Attendance
        $attendanceToday = DailyAttendance::with('worker')
            ->whereDate('attendance_date', $date)
            ->get();

        // 6. Petty Cash & Financial Vouchers
        $pettyCashToday = PettyCashTransaction::whereDate('transaction_date', $date)
            ->orderBy('created_at')
            ->get();

        $vouchersToday = Voucher::with('party')
            ->whereDate('voucher_date', $date)
            ->get();

        // Calculated Executive Summary
        $salesInvoiced = (float) $vouchersToday->where('voucher_type', 'SALES_INVOICE')->sum('total_amount');
        if ($salesInvoiced == 0 && $dispatches->isNotEmpty()) {
            // Estimate dispatched sales value if invoices are pending
            $salesInvoiced = $dispatches->sum('total_pieces') * 65.0; // average box rate estimate
        }

        $cashReceived = (float) $vouchersToday->where('voucher_type', 'RECEIPT_VOUCHER')->where('payment_mode', 'CASH')->sum('total_amount')
            + (float) $pettyCashToday->where('transaction_type', 'CASH_IN')->sum('amount');

        $pettyCashSpent = (float) $pettyCashToday->where('transaction_type', 'CASH_OUT')->sum('amount');
        $voucherExpenses = (float) $vouchersToday->whereIn('voucher_type', ['EXPENSE_VOUCHER', 'PAYMENT_VOUCHER'])->where('payment_mode', 'CASH')->sum('total_amount');
        $cashPaid = $pettyCashSpent + $voucherExpenses;

        $wagesAccrued = (float) $attendanceToday->sum(fn ($a) => $a->daily_calculated_wage + $a->overtime_amount);
        $netCashflow = $cashReceived - $cashPaid;
        $closingCashBalance = PettyCashTransaction::currentCashBalance();

        $summary = [
            'trucks_out' => $dispatches->count(),
            'dispatched_pcs' => (int) $dispatches->sum('total_pieces'),
            'dispatched_bundles' => (int) $dispatches->sum('total_bundles'),
            'trucks_in' => $inwardTrucks->count(),
            'paper_reels_weight_kg' => $inwardReelKg,
            'paper_reels_weight_tons' => round($inwardReelKg / 1000, 2),
            'top_sheets_received' => (int) $topSheetsToday->sum('stock_pieces'),
            'top_sheets_valuation' => (float) $topSheetsToday->sum(fn ($t) => $t->stock_pieces * $t->unit_cost),
            'nalis_produced' => (int) $corrugationsToday->sum('nalis_produced'),
            'corrugation_sheets' => (int) $corrugationsToday->sum('completed_sheets_produced'),
            'production_pcs' => (int) $batchesToday->sum('produced_quantity'),
            'workers_present' => $attendanceToday->where('status', 'PRESENT')->count(),
            'workers_absent' => $attendanceToday->where('status', 'ABSENT')->count(),
            'wages_accrued' => $wagesAccrued,
            'overtime_hours' => (float) $attendanceToday->sum('overtime_hours'),
            'advances_deducted' => (float) $attendanceToday->sum('advance_deducted'),
            'petty_cash_spent' => $pettyCashSpent,
            'sales_invoiced' => $salesInvoiced,
            'cash_received' => $cashReceived,
            'cash_paid' => $cashPaid,
            'net_cashflow' => $netCashflow,
            'closing_cash_balance' => $closingCashBalance,
        ];

        return compact(
            'dispatches',
            'inwardTrucks',
            'paperReelsToday',
            'topSheetsToday',
            'batchesToday',
            'corrugationsToday',
            'conversionsToday',
            'attendanceToday',
            'pettyCashToday',
            'vouchersToday',
            'summary'
        );
    }
}
