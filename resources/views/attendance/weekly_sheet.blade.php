@extends('layouts.app')

@section('title', 'Weekly Thursday Labor Payroll & Wages')
@section('breadcrumb', 'Weekly Thursday Wages')

@section('header_actions')
    <a href="{{ route('attendance.weekly.print', ['thursday' => $cycleEnd]) }}" target="_blank"
        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-slate-300 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-xs transition-colors">
        <span class="material-symbols-outlined text-[16px]">print</span>
        <span>Print Thursday Pay Slips</span>
    </a>
    <a href="{{ route('attendance.index') }}"
        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-slate-300 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-xs transition-colors">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        <span>Daily Attendance</span>
    </a>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Payout Header & Cycle Picker -->
    <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-600">payments</span>
                <h1 class="text-lg font-bold text-slate-900">Weekly Labor Payout (Thursday Cycle)</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Cycle: <strong class="text-slate-800">{{ \Carbon\Carbon::parse($cycleStart)->format('D, d M') }}</strong> to <strong class="text-slate-800">{{ \Carbon\Carbon::parse($cycleEnd)->format('D, d M Y') }}</strong> (Saturday to Thursday, 6 Days)
            </p>
        </div>

        <form action="{{ route('attendance.weekly') }}" method="GET" class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-600 whitespace-nowrap">Payout Thursday:</label>
            <input type="date" name="thursday" value="{{ $cycleEnd }}" onchange="this.form.submit()"
                class="rounded-lg border-slate-300 text-xs py-1.5 px-3 focus:border-blue-600 focus:ring-blue-600 font-semibold" />
            <button type="submit" class="px-3 py-1.5 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800">
                View Cycle
            </button>
        </form>
    </div>

    <!-- Summary Statistics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Gross Calculated Wages</span>
            <div class="mt-2 text-2xl font-bold text-navy-900 num-tabular">
                Rs. {{ number_format($totalGross, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">6-day base wages + overtime</div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Advances to Deduct</span>
            <div class="mt-2 text-2xl font-bold text-amber-600 num-tabular">
                Rs. {{ number_format($totalAdvances, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Unsettled cash loans taken this week</div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Net Payout to Workers</span>
            <div class="mt-2 text-2xl font-bold text-emerald-600 num-tabular">
                Rs. {{ number_format($totalNet, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Total cash required for Thursday distribution</div>
        </div>
    </div>

    <!-- Workers Weekly Wages Breakdown Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-sm text-slate-900">Worker Wage & Overtime Roster</h3>
            <span class="text-xs text-slate-500">{{ $summary->count() }} active workers</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 uppercase font-semibold">
                    <tr>
                        <th class="py-3 px-4">Worker Code & Name</th>
                        <th class="py-3 px-4">Role / Machine</th>
                        <th class="py-3 px-4">Wage Type & Rate</th>
                        <th class="py-3 px-4 text-center">Present / Half / Absent</th>
                        <th class="py-3 px-4 text-center">Overtime (Hrs)</th>
                        <th class="py-3 px-4 text-right">Gross Wage</th>
                        <th class="py-3 px-4 text-right">Advance Deduct</th>
                        <th class="py-3 px-4 text-right">Net Payable</th>
                        <th class="py-3 px-4 text-center no-print">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($summary as $row)
                        @php $w = $row['worker']; @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900">{{ $w->full_name }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $w->worker_code }}</div>
                            </td>
                            <td class="py-3 px-4 text-slate-600 font-medium">
                                {{ $w->role }}
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                <div class="font-semibold text-slate-800">Rs. {{ number_format($w->rate_amount) }}</div>
                                <div class="text-[10px] text-slate-400">{{ str_replace('_', ' ', $w->wage_type) }}</div>
                            </td>
                            <td class="py-3 px-4 text-center num-tabular">
                                <span class="inline-block px-2 py-0.5 rounded font-bold bg-emerald-50 text-emerald-700">{{ $row['present_days'] }}P</span>
                                <span class="inline-block px-1.5 py-0.5 rounded font-medium bg-amber-50 text-amber-700">{{ $row['half_days'] }}H</span>
                                <span class="inline-block px-1.5 py-0.5 rounded font-medium bg-red-50 text-red-700">{{ $row['absent_days'] }}A</span>
                            </td>
                            <td class="py-3 px-4 text-center font-bold text-blue-600 num-tabular">
                                {{ $row['overtime_hours'] > 0 ? $row['overtime_hours'] . ' hrs' : '—' }}
                            </td>
                            <td class="py-3 px-4 text-right font-bold text-slate-800 num-tabular">
                                Rs. {{ number_format($row['gross_wage'], 2) }}
                            </td>
                            <td class="py-3 px-4 text-right font-bold text-red-600 num-tabular">
                                {{ $row['unpaid_advances'] > 0 ? '-Rs. ' . number_format($row['unpaid_advances'], 2) : '—' }}
                            </td>
                            <td class="py-3 px-4 text-right font-extrabold text-emerald-700 text-sm num-tabular">
                                Rs. {{ number_format($row['net_payable'], 2) }}
                            </td>
                            <td class="py-3 px-4 text-center no-print">
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button" onclick="openWorkerEditModal({{ json_encode($w) }})"
                                        class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                        Edit
                                    </button>
                                    @if(auth()->user()?->isFullAdmin())
                                        <button type="button" onclick="confirmDeleteWorker({{ $w->id }}, @js($w->full_name))"
                                                class="text-xs text-red-500 hover:text-red-700 font-medium" title="Delete Worker">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 text-center text-slate-400">
                                No worker records found for this cycle.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($summary->isNotEmpty())
                    <tfoot class="bg-slate-50 font-bold border-t border-slate-200">
                        <tr>
                            <td colspan="5" class="py-3 px-4 text-right uppercase text-[11px] text-slate-600">Cycle Totals:</td>
                            <td class="py-3 px-4 text-right num-tabular text-slate-900">Rs. {{ number_format($totalGross, 2) }}</td>
                            <td class="py-3 px-4 text-right num-tabular text-red-600">-Rs. {{ number_format($totalAdvances, 2) }}</td>
                            <td class="py-3 px-4 text-right num-tabular text-emerald-700 text-base">Rs. {{ number_format($totalNet, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection

@section('modals')
<!-- Modal: Edit Worker Profile & Timings -->
<div id="modal-edit-worker" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900" id="edit-worker-title">Edit Worker Profile</h3>
            <button onclick="closeModal('modal-edit-worker')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="edit-worker-form" method="POST" class="space-y-4 mt-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name *</label>
                <input type="text" id="edit-worker-name" name="full_name" required
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Role / Designation *</label>
                    <input type="text" id="edit-worker-role" name="role" required
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Phone Number</label>
                    <input type="text" id="edit-worker-phone" name="phone"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Salary / Wage Type *</label>
                    <select id="edit-worker-wage-type" name="wage_type" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                        <option value="WEEKLY_WAGE">Weekly Wage (Thursday Cycle)</option>
                        <option value="DAILY_WAGE">Daily Wage (Dihari)</option>
                        <option value="MONTHLY_SALARIED">Monthly Salaried</option>
                        <option value="HOURLY_WAGE">Hourly Wage</option>
                        <option value="PIECE_RATE_STITCHING">Piece Rate (Stitching / 1000 Pcs)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Rate Amount (Rs.) *</label>
                    <input type="number" step="0.01" min="1" id="edit-worker-rate" name="rate_amount" required
                        class="w-full rounded-lg border-slate-300 text-sm font-bold focus:border-blue-600 focus:ring-blue-600 font-mono" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Standard Shift Hours</label>
                    <input type="number" step="0.5" min="1" max="24" id="edit-worker-hours" name="standard_hours_per_day"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600 font-mono" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Overtime Rate / Hour (Rs.)</label>
                    <input type="number" step="0.5" min="0" id="edit-worker-ot" name="overtime_rate_per_hour" placeholder="Auto if blank"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600 font-mono" />
                </div>
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="closeModal('modal-edit-worker')"
                    class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Cancel</button>
                <button type="submit"
                    class="px-5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Standalone Worker Delete Form (Prevents HTML Form Nesting) -->
<form id="standalone-delete-worker-form" method="POST" class="hidden">
    @csrf
    @method('DELETE')
    <input type="hidden" name="worker_id" id="standalone-delete-worker-id" value="">
</form>
@endsection

@push('scripts')
<script>
function confirmDeleteWorker(id, name) {
    if (confirm('Delete worker "' + name + '" from factory payroll?')) {
        const form = document.getElementById('standalone-delete-worker-form');
        form.action = '/attendance/worker/' + id;
        document.getElementById('standalone-delete-worker-id').value = id;
        form.submit();
    }
}

function openWorkerEditModal(worker) {
    document.getElementById('edit-worker-title').textContent = 'Edit Worker: ' + worker.full_name + ' (' + worker.worker_code + ')';
    document.getElementById('edit-worker-form').action = '/attendance/worker/' + worker.id + '/update';
    document.getElementById('edit-worker-name').value = worker.full_name;
    document.getElementById('edit-worker-role').value = worker.role;
    document.getElementById('edit-worker-phone').value = worker.phone || '';
    document.getElementById('edit-worker-wage-type').value = worker.wage_type;
    document.getElementById('edit-worker-rate').value = worker.rate_amount;
    document.getElementById('edit-worker-hours').value = worker.standard_hours_per_day || 8;
    document.getElementById('edit-worker-ot').value = worker.overtime_rate_per_hour || '';
    openModal('modal-edit-worker');
}
</script>
@endpush
