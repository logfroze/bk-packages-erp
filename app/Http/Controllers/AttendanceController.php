<?php

namespace App\Http\Controllers;

use App\Models\DailyAttendance;
use App\Models\Worker;
use App\Models\WorkerAdvance;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->get('date', now()->toDateString());

        $workers = Worker::where('is_active', true)
            ->with(['attendances' => fn ($q) => $q->whereDate('attendance_date', $date)])
            ->withSum(['advances as unpaid_advances' => fn ($q) => $q->where('is_settled', false)], 'amount')
            ->orderBy('full_name')
            ->get();

        $totalWageToday = DailyAttendance::whereDate('attendance_date', $date)->sum('daily_calculated_wage');
        $presentCount = DailyAttendance::whereDate('attendance_date', $date)->where('status', 'PRESENT')->count();

        // Calculate Thursday payout cycle reference
        $currentDate = Carbon::parse($date);
        $thursday = $currentDate->isThursday() ? $currentDate->copy() : $currentDate->copy()->next(Carbon::THURSDAY);
        $cycleStart = $thursday->copy()->subDays(5); // Saturday to Thursday

        return view('attendance.index', compact(
            'workers',
            'date',
            'totalWageToday',
            'presentCount',
            'thursday',
            'cycleStart'
        ));
    }

    public function weeklySheet(Request $request): View
    {
        $selectedThursday = $request->get('thursday', now()->isThursday() ? now()->toDateString() : now()->next(Carbon::THURSDAY)->toDateString());
        $cycleEnd = Carbon::parse($selectedThursday)->toDateString();
        $cycleStart = Carbon::parse($selectedThursday)->subDays(5)->toDateString(); // Saturday to Thursday (6 days)

        $workers = Worker::where('is_active', true)
            ->with(['attendances' => fn ($q) => $q->whereBetween('attendance_date', [$cycleStart, $cycleEnd])])
            ->with(['advances' => fn ($q) => $q->where('is_settled', false)->whereDate('advance_date', '<=', $cycleEnd)])
            ->orderBy('full_name')
            ->get();

        $summary = $workers->map(function ($worker) {
            $attendances = $worker->attendances;
            $presentDays = $attendances->where('status', 'PRESENT')->count();
            $halfDays = $attendances->where('status', 'HALF_DAY')->count();
            $absentDays = $attendances->where('status', 'ABSENT')->count();
            $overtimeHours = (float) $attendances->sum('overtime_hours');
            $grossWage = (float) $attendances->sum('daily_calculated_wage');
            $unpaidAdvances = (float) $worker->advances->sum('amount');
            $netPayable = max(0, $grossWage - $unpaidAdvances);

            return [
                'worker' => $worker,
                'present_days' => $presentDays,
                'half_days' => $halfDays,
                'absent_days' => $absentDays,
                'overtime_hours' => $overtimeHours,
                'gross_wage' => $grossWage,
                'unpaid_advances' => $unpaidAdvances,
                'net_payable' => $netPayable,
            ];
        });

        $totalGross = $summary->sum('gross_wage');
        $totalAdvances = $summary->sum('unpaid_advances');
        $totalNet = $summary->sum('net_payable');

        return view('attendance.weekly_sheet', compact(
            'cycleStart',
            'cycleEnd',
            'summary',
            'totalGross',
            'totalAdvances',
            'totalNet'
        ));
    }

    public function printWeeklySheet(Request $request): View
    {
        $selectedThursday = $request->get('thursday', now()->isThursday() ? now()->toDateString() : now()->next(Carbon::THURSDAY)->toDateString());
        $cycleEnd = Carbon::parse($selectedThursday)->toDateString();
        $cycleStart = Carbon::parse($selectedThursday)->subDays(5)->toDateString();

        $workers = Worker::where('is_active', true)
            ->with(['attendances' => fn ($q) => $q->whereBetween('attendance_date', [$cycleStart, $cycleEnd])])
            ->with(['advances' => fn ($q) => $q->where('is_settled', false)->whereDate('advance_date', '<=', $cycleEnd)])
            ->orderBy('full_name')
            ->get();

        $summary = $workers->map(function ($worker) {
            $attendances = $worker->attendances;
            $grossWage = (float) $attendances->sum('daily_calculated_wage');
            $unpaidAdvances = (float) $worker->advances->sum('amount');
            $netPayable = max(0, $grossWage - $unpaidAdvances);

            return [
                'worker' => $worker,
                'present_days' => $attendances->where('status', 'PRESENT')->count(),
                'half_days' => $attendances->where('status', 'HALF_DAY')->count(),
                'absent_days' => $attendances->where('status', 'ABSENT')->count(),
                'overtime_hours' => (float) $attendances->sum('overtime_hours'),
                'gross_wage' => $grossWage,
                'unpaid_advances' => $unpaidAdvances,
                'net_payable' => $netPayable,
            ];
        });

        $totalNet = $summary->sum('net_payable');

        return view('attendance.print_weekly_sheet', compact('cycleStart', 'cycleEnd', 'summary', 'totalNet'));
    }

    public function save(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'attendance_date' => 'required|date',
            'records' => 'required|array',
            'records.*.worker_id' => 'required|exists:workers,id',
            'records.*.status' => 'required|in:PRESENT,ABSENT,HALF_DAY,LEAVE',
            'records.*.overtime_hours' => 'nullable|numeric|min:0|max:24',
            'records.*.pieces_completed' => 'nullable|integer|min:0',
        ]);

        foreach ($validated['records'] as $rec) {
            $worker = Worker::findOrFail($rec['worker_id']);
            $status = $rec['status'];
            $overtime = (float) ($rec['overtime_hours'] ?? 0);
            $pieces = (int) ($rec['pieces_completed'] ?? 0);
            $stdHours = (float) ($worker->standard_hours_per_day ?: 8);
            $otRate = (float) ($worker->overtime_rate_per_hour ?: 0);

            $wage = 0.00;
            if ($status !== 'ABSENT' && $status !== 'LEAVE') {
                if ($worker->wage_type === 'PIECE_RATE_STITCHING') {
                    // Rate is per 1,000 boxes
                    $wage = ($pieces / 1000) * $worker->rate_amount;
                } elseif ($worker->wage_type === 'WEEKLY_WAGE') {
                    // 6-day work week (Saturday to Thursday)
                    $daily = $worker->rate_amount / 6;
                    $base = $status === 'HALF_DAY' ? ($daily / 2) : $daily;
                    $effectiveOtRate = $otRate > 0 ? $otRate : ($daily / $stdHours);
                    $wage = $base + ($overtime * $effectiveOtRate);
                } elseif ($worker->wage_type === 'HOURLY_WAGE') {
                    $hourly = (float) $worker->rate_amount;
                    $effectiveOtRate = $otRate > 0 ? $otRate : ($hourly * 1.5);
                    $regularHours = $status === 'HALF_DAY' ? ($stdHours / 2) : $stdHours;
                    $wage = ($regularHours * $hourly) + ($overtime * $effectiveOtRate);
                } elseif ($worker->wage_type === 'DAILY_WAGE') {
                    $base = $status === 'HALF_DAY' ? ($worker->rate_amount / 2) : $worker->rate_amount;
                    $effectiveOtRate = $otRate > 0 ? $otRate : ($worker->rate_amount / $stdHours);
                    $wage = $base + ($overtime * $effectiveOtRate);
                } else {
                    // Monthly salaried prorated over 30 days
                    $daily = $worker->rate_amount / 30;
                    $base = $status === 'HALF_DAY' ? ($daily / 2) : $daily;
                    $effectiveOtRate = $otRate > 0 ? $otRate : ($daily / $stdHours);
                    $wage = $base + ($overtime * $effectiveOtRate);
                }
            }

            $existing = DailyAttendance::where('worker_id', $worker->id)
                ->whereDate('attendance_date', $validated['attendance_date'])
                ->first();

            $payload = [
                'status' => $status,
                'overtime_hours' => $overtime,
                'pieces_completed' => $pieces,
                'daily_calculated_wage' => round($wage, 2),
            ];

            if ($existing) {
                $existing->update($payload);
            } else {
                DailyAttendance::create(array_merge([
                    'worker_id' => $worker->id,
                    'attendance_date' => $validated['attendance_date'],
                ], $payload));
            }
        }

        return back()->with('success', "Attendance & daily wages saved for {$validated['attendance_date']}.");
    }

    public function storeWorker(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:100',
            'role' => 'required|string|max:60',
            'wage_type' => 'required|in:MONTHLY_SALARIED,DAILY_WAGE,WEEKLY_WAGE,HOURLY_WAGE,PIECE_RATE_STITCHING',
            'rate_amount' => 'required|numeric|min:1',
            'phone' => 'nullable|string|max:30',
            'standard_hours_per_day' => 'nullable|numeric|min:1|max:24',
            'overtime_rate_per_hour' => 'nullable|numeric|min:0',
        ]);

        $count = Worker::count() + 1;
        $code = 'EMP-' . str_pad((string) $count, 2, '0', STR_PAD_LEFT);

        Worker::create([
            'worker_code' => $code,
            'full_name' => trim($validated['full_name']),
            'role' => trim($validated['role']),
            'wage_type' => $validated['wage_type'],
            'rate_amount' => $validated['rate_amount'],
            'phone' => $validated['phone'] ?? null,
            'standard_hours_per_day' => $validated['standard_hours_per_day'] ?? 8.0,
            'overtime_rate_per_hour' => $validated['overtime_rate_per_hour'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', "Worker '{$validated['full_name']}' added to factory payroll.");
    }

    public function updateWorker(Request $request, Worker $worker): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:100',
            'role' => 'required|string|max:60',
            'wage_type' => 'required|in:MONTHLY_SALARIED,DAILY_WAGE,WEEKLY_WAGE,HOURLY_WAGE,PIECE_RATE_STITCHING',
            'rate_amount' => 'required|numeric|min:1',
            'phone' => 'nullable|string|max:30',
            'standard_hours_per_day' => 'nullable|numeric|min:1|max:24',
            'overtime_rate_per_hour' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $worker->update([
            'full_name' => trim($validated['full_name']),
            'role' => trim($validated['role']),
            'wage_type' => $validated['wage_type'],
            'rate_amount' => $validated['rate_amount'],
            'phone' => $validated['phone'] ?? null,
            'standard_hours_per_day' => $validated['standard_hours_per_day'] ?? 8.0,
            'overtime_rate_per_hour' => $validated['overtime_rate_per_hour'] ?? null,
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', "Worker profile '{$worker->full_name}' updated successfully.");
    }

    public function storeAdvance(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'worker_id' => 'required|exists:workers,id',
            'amount' => 'required|numeric|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        WorkerAdvance::create([
            'worker_id' => $validated['worker_id'],
            'advance_date' => now()->toDateString(),
            'amount' => $validated['amount'],
            'reason' => $validated['reason'] ?? 'Cash Advance',
            'is_settled' => false,
        ]);

        return back()->with('success', 'Worker advance recorded.');
    }

    public function settleAdvance(WorkerAdvance $advance): RedirectResponse
    {
        $advance->update(['is_settled' => true]);
        return back()->with('success', "Advance of Rs. " . number_format($advance->amount) . " marked as settled.");
    }
}
