@extends('layouts.app')

@section('title', 'Worker Attendance & Daily Wages')
@section('breadcrumb', 'Labor & Wages')

@section('header_actions')
    <div class="flex items-center gap-2">
        <a href="{{ route('attendance.weekly') }}" class="px-3.5 py-2 rounded-lg bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 flex items-center gap-1.5 shadow-xs transition-colors">
            <span class="material-symbols-outlined text-[16px]">calendar_view_week</span>
            <span>Thursday Weekly Wages</span>
        </a>
        <button onclick="openModal('modal-advance')" class="px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50 transition-colors">
            + Give Advance
        </button>
        <button onclick="openModal('modal-add-worker')" class="px-3.5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 transition-colors">
            + Add Worker
        </button>
    </div>
@endsection

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Worker Attendance & Daily Wages</h1>
            <p class="text-sm text-slate-500 mt-1">Record daily presence, overtime, piece-rate stitching, and view Thursday payouts</p>
        </div>

        <div class="flex items-center gap-3">
            <form method="GET" action="{{ route('attendance.index') }}" class="flex items-center gap-2">
                <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()"
                       class="rounded-xl border-slate-300 text-sm bg-white font-medium" />
            </form>
        </div>
    </div>

    <!-- Clean 3-Card Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
            <div class="text-xs font-semibold uppercase text-slate-500">Total Factory Staff</div>
            <div class="text-2xl font-bold text-slate-900 mt-2 num-tabular">{{ $workers->count() }} Workers</div>
            <div class="text-xs text-slate-400 mt-1">Managed dynamically with custom roles & rates</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
            <div class="text-xs font-semibold uppercase text-slate-500">Present on {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</div>
            <div class="text-2xl font-bold text-emerald-600 mt-2 num-tabular">{{ $presentCount }} Present</div>
            <div class="text-xs text-slate-400 mt-1">{{ $workers->count() - $presentCount }} Absent / On Leave</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
            <div class="text-xs font-semibold uppercase text-slate-500">Calculated Wages Today</div>
            <div class="text-2xl font-bold text-navy-900 mt-2 num-tabular">Rs. {{ number_format($totalWageToday) }}</div>
            <div class="text-xs text-slate-400 mt-1">Includes regular shifts + overtime</div>
        </div>
    </div>

    <!-- Attendance Roll Call Form -->
    <form action="{{ route('attendance.save') }}" method="POST" class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        @csrf
        <input type="hidden" name="attendance_date" value="{{ $date }}" />

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-5">Worker Name & Role</th>
                        <th class="py-3.5 px-5">Wage Rate & Type</th>
                        <th class="py-3.5 px-5">Status</th>
                        <th class="py-3.5 px-5">Overtime / Stitched Pcs</th>
                        <th class="py-3.5 px-5 text-right">Today's Wage</th>
                        <th class="py-3.5 px-5 text-center">Manage</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @foreach($workers as $i => $w)
                        @php
                            $att = $w->attendances->first();
                            $status = $att->status ?? 'PRESENT';
                            $ot = $att->overtime_hours ?? 0;
                            $pcs = $att->pieces_completed ?? 0;
                            $wage = $att->daily_calculated_wage ?? 0;
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-4 px-5">
                                <input type="hidden" name="records[{{ $i }}][worker_id]" value="{{ $w->id }}" />
                                <div class="font-semibold text-slate-900">{{ $w->full_name }}</div>
                                <div class="text-xs text-slate-500">{{ $w->role }} · <span class="font-mono">{{ $w->worker_code }}</span>
                                    @if(($w->unpaid_advances ?? 0) > 0)
                                        <span class="text-amber-600 font-medium">· Adv: Rs. {{ number_format($w->unpaid_advances) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-4 px-5 text-slate-700 num-tabular">
                                @if($w->wage_type === 'PIECE_RATE_STITCHING')
                                    Rs. {{ number_format($w->rate_amount) }} / 1k boxes
                                @elseif($w->wage_type === 'WEEKLY_WAGE')
                                    Rs. {{ number_format($w->rate_amount) }} / week (Thursday)
                                @elseif($w->wage_type === 'HOURLY_WAGE')
                                    Rs. {{ number_format($w->rate_amount) }} / hour
                                @elseif($w->wage_type === 'DAILY_WAGE')
                                    Rs. {{ number_format($w->rate_amount) }} / day
                                @else
                                    Rs. {{ number_format($w->rate_amount) }} / mo
                                @endif
                            </td>
                            <td class="py-4 px-5">
                                <select name="records[{{ $i }}][status]" class="rounded-lg border-slate-300 text-xs py-1.5 focus:border-blue-600 focus:ring-blue-600">
                                    <option value="PRESENT" {{ $status === 'PRESENT' ? 'selected' : '' }}>Present</option>
                                    <option value="HALF_DAY" {{ $status === 'HALF_DAY' ? 'selected' : '' }}>Half Day</option>
                                    <option value="ABSENT" {{ $status === 'ABSENT' ? 'selected' : '' }}>Absent</option>
                                    <option value="LEAVE" {{ $status === 'LEAVE' ? 'selected' : '' }}>Leave</option>
                                </select>
                            </td>
                            <td class="py-4 px-5">
                                @if($w->wage_type === 'PIECE_RATE_STITCHING')
                                    <div class="flex items-center gap-2">
                                        <input type="number" name="records[{{ $i }}][pieces_completed]" value="{{ $pcs }}" min="0"
                                               class="w-28 rounded-lg border-slate-300 text-xs py-1.5 focus:border-blue-600 focus:ring-blue-600" />
                                        <span class="text-xs text-slate-400">Boxes</span>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2">
                                        <input type="number" step="0.5" name="records[{{ $i }}][overtime_hours]" value="{{ $ot }}" min="0" max="24"
                                               class="w-20 rounded-lg border-slate-300 text-xs py-1.5 focus:border-blue-600 focus:ring-blue-600" />
                                        <span class="text-xs text-slate-400">OT hrs</span>
                                    </div>
                                @endif
                            </td>
                            <td class="py-4 px-5 text-right font-bold num-tabular text-slate-900">
                                Rs. {{ number_format($wage, 0) }}
                            </td>
                            <td class="py-4 px-5 text-center">
                                <button type="button" onclick="openWorkerEditModal({{ json_encode($w) }})"
                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                    Edit
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-navy-900 text-white text-sm font-semibold hover:bg-navy-800 shadow-sm transition-colors">
                Save & Calculate Today's Wages
            </button>
        </div>
    </form>
@endsection

@section('modals')
    <!-- Add Worker Modal -->
    <div id="modal-add-worker" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Add Factory Worker Profile</h3>
                <button onclick="closeModal('modal-add-worker')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('attendance.worker.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name *</label>
                    <input type="text" name="full_name" required placeholder="e.g. Muhammad Nadeem" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Role / Designation *</label>
                        <input type="text" name="role" required placeholder="e.g. Corrugator Operator / Helper" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Phone Number</label>
                        <input type="text" name="phone" placeholder="0300-1234567" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Wage Type *</label>
                        <select name="wage_type" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                            <option value="WEEKLY_WAGE">Weekly Wage (Thursday Payout)</option>
                            <option value="DAILY_WAGE">Daily Wage (Dihari)</option>
                            <option value="MONTHLY_SALARIED">Monthly Salaried</option>
                            <option value="HOURLY_WAGE">Hourly Wage</option>
                            <option value="PIECE_RATE_STITCHING">Piece Rate (Per 1,000 Boxes)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Rate Amount (Rs.) *</label>
                        <input type="number" step="0.01" name="rate_amount" min="1" required placeholder="e.g. 5000" class="w-full rounded-lg border-slate-300 text-sm font-bold focus:border-blue-600 focus:ring-blue-600 font-mono" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Standard Shift Hours</label>
                        <input type="number" step="0.5" name="standard_hours_per_day" value="8" min="1" max="24" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600 font-mono" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Overtime Rate / Hr (Rs.)</label>
                        <input type="number" step="0.5" name="overtime_rate_per_hour" placeholder="Auto if blank" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600 font-mono" />
                    </div>
                </div>
                <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-add-worker')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs">Add Worker</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Worker Modal -->
    <div id="modal-edit-worker" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl">
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

                <div class="pt-2 flex justify-end gap-2 border-t border-slate-100">
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

    <!-- Give Worker Advance Modal -->
    <div id="modal-advance" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Record Worker Advance (Peshgi)</h3>
                <button onclick="closeModal('modal-advance')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('attendance.advance.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Select Worker</label>
                    <select name="worker_id" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                        @foreach($workers as $w)
                            <option value="{{ $w->id }}">{{ $w->full_name }} ({{ $w->role }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Advance Amount (Rs.)</label>
                    <input type="number" name="amount" min="100" required class="w-full rounded-lg border-slate-300 text-sm font-bold focus:border-blue-600 focus:ring-blue-600 font-mono" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reason / Note</label>
                    <input type="text" name="reason" value="Cash Advance" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
                </div>
                <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-advance')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs">Save Advance</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
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
