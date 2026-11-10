@extends('layouts.app')

@section('title', 'Day-End Closing Report (Roznamcha)')
@section('breadcrumb', 'Day-End Reports')

@section('header_actions')
    <div class="flex items-center gap-3 no-print">
        <form method="GET" action="{{ route('reports.index') }}">
            <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-xs py-1.5" />
        </form>
        <button onclick="window.print()" class="px-4 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 inline-flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px]">print</span>
            Print Daily Closing
        </button>
    </div>
@endsection

@section('content')
    <!-- Printable Header -->
    <div class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-200 pb-6 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">BK PACKAGES — Daily Closing Report (Roznamcha)</h1>
                <p class="text-sm text-slate-500 mt-1">3-KM Depalpur Road, Okara · Prepared by Mirza Sohail</p>
            </div>
            <div class="mt-3 sm:mt-0 text-left sm:text-right">
                <span class="text-xs uppercase font-semibold text-slate-400 block">Report Date</span>
                <span class="text-lg font-bold text-slate-900">{{ \Carbon\Carbon::parse($date)->format('l, d M Y') }}</span>
            </div>
        </div>

        <!-- Executive Summary Numbers -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-xs font-semibold text-slate-500 uppercase">Production Output</span>
                <div class="text-2xl font-bold text-slate-900 mt-1 num-tabular">{{ number_format($summary['production_pcs']) }} Pcs</div>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-xs font-semibold text-slate-500 uppercase">Trucks Dispatched</span>
                <div class="text-2xl font-bold text-slate-900 mt-1 num-tabular">{{ $summary['trucks_out'] }} Out / {{ $summary['trucks_in'] }} In</div>
                <div class="text-xs text-slate-500 mt-0.5">{{ number_format($summary['dispatched_pcs']) }} Boxes</div>
            </div>
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200">
                <span class="text-xs font-semibold text-emerald-700 uppercase">Cash Received Today</span>
                <div class="text-2xl font-bold text-emerald-800 mt-1 num-tabular">Rs. {{ number_format($summary['cash_received']) }}</div>
            </div>
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-xs font-semibold text-slate-500 uppercase">Expenses & Wages</span>
                <div class="text-2xl font-bold text-slate-900 mt-1 num-tabular">Rs. {{ number_format($summary['expenses_paid'] + $summary['wages_accrued']) }}</div>
                <div class="text-xs text-slate-500 mt-0.5">{{ $summary['workers_present'] }} Workers Present</div>
            </div>
        </div>
    </div>

    <!-- Clean Two-Column Daily Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Financial Vouchers Today -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 font-semibold text-slate-900 text-sm">
                Today's Financial Vouchers & Invoices
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($vouchersToday as $v)
                    <div class="p-5 flex items-center justify-between gap-4 text-sm">
                        <div>
                            <div class="font-semibold text-slate-900">{{ $v->voucher_number }} — {{ $v->party->name ?? 'Factory Expense' }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">{{ $v->description }} ({{ $v->payment_mode }})</div>
                        </div>
                        <div class="text-right font-bold num-tabular {{ $v->voucher_type === 'RECEIPT_VOUCHER' ? 'text-emerald-600' : 'text-slate-900' }}">
                            Rs. {{ number_format($v->total_amount) }}
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-sm text-slate-500">No financial vouchers logged on this date.</div>
                @endforelse
            </div>
        </div>

        <!-- Gate Movements Today -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 font-semibold text-slate-900 text-sm">
                Today's Vehicle & Gate Movements
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($gatesToday as $g)
                    <div class="p-5 flex items-center justify-between gap-4 text-sm">
                        <div>
                            <div class="font-semibold text-slate-900">{{ $g->gate_pass_number }} · {{ $g->vehicle_number }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">{{ $g->party->name ?? '' }} — {{ $g->material_summary }}</div>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-0.5 rounded text-[11px] font-semibold {{ $g->movement_type === 'INWARD_RAW_MATERIAL' ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700' }}">
                                {{ $g->movement_type === 'INWARD_RAW_MATERIAL' ? 'INWARD' : 'OUTWARD' }}
                            </span>
                            <div class="text-xs text-slate-500 mt-1 num-tabular">
                                {{ $g->total_pieces ? number_format($g->total_pieces) . ' Pcs' : number_format($g->net_weight_kg ?? 0) . ' kg' }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-sm text-slate-500">No vehicle gate entries on this date.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Multi-Stage Material Conversion Summary (Reel -> Roll -> Sheet -> Carton) -->
    <div class="mt-8 bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <span class="font-semibold text-slate-900 text-sm">Material Conversion Summary (Reels → Rolls → Sheets → Cartons)</span>
            <a href="{{ route('production.conversion') }}" class="text-xs text-blue-600 font-medium hover:underline no-print">Open Conversion Tracker →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase text-slate-500">
                        <th class="py-3 px-5">Run #</th>
                        <th class="py-3 px-5">Reels Fed</th>
                        <th class="py-3 px-5">Rolls Produced</th>
                        <th class="py-3 px-5">Sheets Cut</th>
                        <th class="py-3 px-5">Cartons Made</th>
                        <th class="py-3 px-5 text-right">Waste / Remaining</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($conversionsToday as $conv)
                        <tr>
                            <td class="py-3.5 px-5 font-semibold text-blue-600">
                                {{ $conv->run_code }}
                                <div class="text-xs font-normal text-slate-500">{{ $conv->cartonProduct->box_title ?? $conv->machine_name }}</div>
                            </td>
                            <td class="py-3.5 px-5 num-tabular">{{ number_format($conv->reel_1_used_kg + $conv->reel_2_used_kg) }} kg</td>
                            <td class="py-3.5 px-5 num-tabular">{{ $conv->total_rolls_produced }} Rolls</td>
                            <td class="py-3.5 px-5 num-tabular">{{ number_format($conv->actual_sheets_cut) }} Sheets</td>
                            <td class="py-3.5 px-5 font-bold text-emerald-700 num-tabular">{{ number_format($conv->actual_cartons_produced) }} Pcs</td>
                            <td class="py-3.5 px-5 text-right text-xs text-slate-600">
                                {{ $conv->rolls_remaining }} Rolls · {{ number_format($conv->sheets_remaining) }} Sheets left
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">No conversion runs recorded on this date.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

