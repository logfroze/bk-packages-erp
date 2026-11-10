<?php

namespace App\Http\Controllers;

use App\Models\DailyAttendance;
use App\Models\Worker;
use App\Models\WorkerAdvance;
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

        return view('attendance.index', compact('workers', 'date', 'totalWageToday', 'presentCount'));
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

            $wage = 0.00;
            if ($status !== 'ABSENT' && $status !== 'LEAVE') {
                if ($worker->wage_type === 'PIECE_RATE_STITCHING') {
                    // Rate is per 1,000 boxes
                    $wage = ($pieces / 1000) * $worker->rate_amount;
                } elseif ($worker->wage_type === 'DAILY_WAGE') {
                    $base = $status === 'HALF_DAY' ? ($worker->rate_amount / 2) : $worker->rate_amount;
                    $hourly = $worker->rate_amount / 8;
                    $wage = $base + ($overtime * $hourly);
                } else {
                    // Monthly salaried prorated over 30 days
                    $daily = $worker->rate_amount / 30;
                    $base = $status === 'HALF_DAY' ? ($daily / 2) : $daily;
                    $hourly = $daily / 8;
                    $wage = $base + ($overtime * $hourly);
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
            'wage_type' => 'required|in:MONTHLY_SALARIED,DAILY_WAGE,PIECE_RATE_STITCHING',
            'rate_amount' => 'required|numeric|min:1',
            'phone' => 'nullable|string|max:30',
        ]);

        $count = Worker::count() + 1;
        $code = 'EMP-' . str_pad((string) $count, 2, '0', STR_PAD_LEFT);

        Worker::create([
            'worker_code' => $code,
            'full_name' => $validated['full_name'],
            'role' => $validated['role'],
            'wage_type' => $validated['wage_type'],
            'rate_amount' => $validated['rate_amount'],
            'phone' => $validated['phone'] ?? null,
        ]);

        return back()->with('success', "Worker '{$validated['full_name']}' added to factory payroll.");
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
        ]);

        return back()->with('success', 'Worker advance recorded.');
    }
}
