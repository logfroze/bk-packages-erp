@extends('layouts.app')

@section('title', 'Day-End Closing & Progress Conclusion (Roznamcha)')
@section('breadcrumb', 'Day-End Reports')

@section('header_actions')
    <div class="flex flex-wrap items-center gap-2 no-print">
        <!-- Date Selector Form -->
        <form method="GET" action="{{ route('reports.index') }}" class="flex items-center gap-1.5 bg-white border border-slate-300 rounded-lg px-2 py-1 shadow-xs">
            <span class="material-symbols-outlined text-slate-400 text-[18px]">calendar_today</span>
            <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="border-0 p-0 text-xs font-semibold text-slate-700 focus:ring-0 cursor-pointer" />
        </form>

        <!-- Quick Shortcuts -->
        <a href="{{ route('reports.index', ['date' => now()->toDateString()]) }}" 
           class="px-2.5 py-1.5 rounded-lg text-xs font-medium border {{ $date === now()->toDateString() ? 'bg-blue-50 border-blue-200 text-blue-700 font-semibold' : 'bg-white border-slate-300 text-slate-700 hover:bg-slate-50' }}">
            Today
        </a>
        <a href="{{ route('reports.index', ['date' => now()->subDay()->toDateString()]) }}" 
           class="px-2.5 py-1.5 rounded-lg text-xs font-medium border {{ $date === now()->subDay()->toDateString() ? 'bg-blue-50 border-blue-200 text-blue-700 font-semibold' : 'bg-white border-slate-300 text-slate-700 hover:bg-slate-50' }}">
            Yesterday
        </a>

        <!-- View Compact Printable Slip -->
        <a href="{{ route('reports.receipt', ['date' => $date]) }}" target="_blank"
           class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 shadow-xs inline-flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px]">receipt_long</span>
            Receipt / Slip View
        </a>

        <!-- Export CSV / Excel -->
        <a href="{{ route('reports.export_csv', ['date' => $date]) }}" 
           class="px-3 py-1.5 rounded-lg bg-slate-800 text-white text-xs font-semibold hover:bg-slate-700 shadow-xs inline-flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px]">table_chart</span>
            Export CSV
        </a>

        <!-- Share to Factory Team Chat -->
        <form method="POST" action="{{ route('reports.share_chat') }}" class="inline">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}" />
            <button type="submit" onclick="return confirm('Post this full day conclusion to the factory team chat?')"
                    class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 shadow-xs inline-flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">chat</span>
                Post to Chat
            </button>
        </form>

        <!-- Copy WhatsApp Summary -->
        <button onclick="copyWhatsAppSummary()" type="button"
                class="px-3 py-1.5 rounded-lg bg-emerald-700 text-white text-xs font-semibold hover:bg-emerald-800 shadow-xs inline-flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px]">content_copy</span>
            Copy WhatsApp
        </button>

        <!-- Print Full Report -->
        <button onclick="window.print()" type="button"
                class="px-3 py-1.5 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs inline-flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px]">print</span>
            Print Roznamcha
        </button>
    </div>
@endsection

@section('content')
<div class="space-y-6">

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between no-print shadow-xs">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600">check_circle</span>
                <span>{{ session('success') }}</span>
            </div>
            <a href="{{ route('chat.index') }}" class="text-xs font-semibold text-emerald-700 hover:underline">Open Factory Chat →</a>
        </div>
    @endif

    <!-- 1. Executive Master Conclusion Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-200 pb-6 mb-6 gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <span class="w-3.5 h-3.5 rounded-full bg-emerald-500 ring-4 ring-emerald-50 animate-pulse"></span>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">BK PACKAGES OKARA — Daily Conclusion Roznamcha</h1>
                </div>
                <p class="text-sm text-slate-500 mt-1">3-KM Depalpur Road, Okara · Compiled & Verified by Mirza Sohail · Complete Factory Operations & Cashflow</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-left md:text-right">
                    <span class="text-xs uppercase font-semibold text-slate-400 block tracking-wider">Report For Date</span>
                    <span class="text-lg font-bold text-slate-900">{{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}</span>
                    <span class="text-xs text-slate-400 block mt-0.5">Updated: {{ now()->format('h:i A') }}</span>
                </div>
                <!-- Quick Document Links -->
                <div class="no-print flex flex-col gap-1.5 border-l border-slate-200 pl-4">
                    <a href="{{ route('documents.delivery_challan') }}" target="_blank" class="px-2.5 py-1 text-[11px] font-semibold rounded bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-700 inline-flex items-center gap-1 border border-slate-200">
                        <span class="material-symbols-outlined text-[14px]">local_shipping</span> Challan Format
                    </a>
                    <a href="{{ route('documents.sales_invoice') }}" target="_blank" class="px-2.5 py-1 text-[11px] font-semibold rounded bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-700 inline-flex items-center gap-1 border border-slate-200">
                        <span class="material-symbols-outlined text-[14px]">receipt</span> Bill / Invoice Format
                    </a>
                </div>
            </div>
        </div>

        <!-- 6 Main KPI Pillars of the Day -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <!-- Dispatches -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 hover:border-blue-300 transition-colors">
                <div class="flex items-center justify-between text-slate-500 mb-1">
                    <span class="text-xs font-semibold uppercase tracking-wider">Dispatches</span>
                    <span class="material-symbols-outlined text-[18px] text-blue-600">local_shipping</span>
                </div>
                <div class="text-xl font-bold text-slate-900 num-tabular">{{ number_format($summary['dispatched_pcs']) }} <span class="text-xs font-normal text-slate-500">Pcs</span></div>
                <div class="text-xs text-slate-500 mt-1 flex items-center justify-between">
                    <span>{{ $summary['trucks_out'] }} Vehicles</span>
                    <span class="font-medium text-slate-700">{{ number_format($summary['dispatched_bundles']) }} Bdls</span>
                </div>
            </div>

            <!-- Paper Reels Inward -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 hover:border-amber-300 transition-colors">
                <div class="flex items-center justify-between text-slate-500 mb-1">
                    <span class="text-xs font-semibold uppercase tracking-wider">Raw Material</span>
                    <span class="material-symbols-outlined text-[18px] text-amber-600">scale</span>
                </div>
                <div class="text-xl font-bold text-slate-900 num-tabular">{{ number_format($summary['paper_reels_weight_tons'], 2) }} <span class="text-xs font-normal text-slate-500">Tons</span></div>
                <div class="text-xs text-slate-500 mt-1 flex items-center justify-between">
                    <span>{{ $summary['trucks_in'] }} Inward</span>
                    <span class="font-medium text-slate-700">{{ number_format($summary['paper_reels_weight_kg']) }} kg</span>
                </div>
            </div>

            <!-- Printed Imports -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 hover:border-purple-300 transition-colors">
                <div class="flex items-center justify-between text-slate-500 mb-1">
                    <span class="text-xs font-semibold uppercase tracking-wider">Printed Sheets</span>
                    <span class="material-symbols-outlined text-[18px] text-purple-600">layers</span>
                </div>
                <div class="text-xl font-bold text-slate-900 num-tabular">{{ number_format($summary['top_sheets_received']) }} <span class="text-xs font-normal text-slate-500">Pcs</span></div>
                <div class="text-xs text-slate-500 mt-1 flex items-center justify-between">
                    <span>Outsourced</span>
                    <span class="font-medium text-slate-700">Rs. {{ number_format($summary['top_sheets_valuation']) }}</span>
                </div>
            </div>

            <!-- Corrugations & Production -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 hover:border-indigo-300 transition-colors">
                <div class="flex items-center justify-between text-slate-500 mb-1">
                    <span class="text-xs font-semibold uppercase tracking-wider">Production</span>
                    <span class="material-symbols-outlined text-[18px] text-indigo-600">view_quilt</span>
                </div>
                <div class="text-xl font-bold text-slate-900 num-tabular">{{ number_format($summary['production_pcs']) }} <span class="text-xs font-normal text-slate-500">Cartons</span></div>
                <div class="text-xs text-slate-500 mt-1 flex items-center justify-between">
                    <span>{{ number_format($summary['nalis_produced']) }} Nalis</span>
                    <span class="font-medium text-slate-700">{{ number_format($summary['corrugation_sheets']) }} Sheets</span>
                </div>
            </div>

            <!-- Labour Workforce -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 hover:border-emerald-300 transition-colors">
                <div class="flex items-center justify-between text-slate-500 mb-1">
                    <span class="text-xs font-semibold uppercase tracking-wider">Labour</span>
                    <span class="material-symbols-outlined text-[18px] text-emerald-600">badge</span>
                </div>
                <div class="text-xl font-bold text-slate-900 num-tabular">{{ $summary['workers_present'] }} <span class="text-xs font-normal text-slate-500">Present</span></div>
                <div class="text-xs text-slate-500 mt-1 flex items-center justify-between">
                    <span>OT: {{ $summary['overtime_hours'] }} hrs</span>
                    <span class="font-medium text-slate-700">Rs. {{ number_format($summary['wages_accrued']) }}</span>
                </div>
            </div>

            <!-- Net Daily Cashflow & Safe -->
            <div class="p-4 rounded-xl {{ $summary['net_cashflow'] >= 0 ? 'bg-emerald-50/80 border-emerald-200' : 'bg-rose-50/80 border-rose-200' }}">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold uppercase tracking-wider {{ $summary['net_cashflow'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">Net Cashflow</span>
                    <span class="material-symbols-outlined text-[18px] {{ $summary['net_cashflow'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                        {{ $summary['net_cashflow'] >= 0 ? 'trending_up' : 'trending_down' }}
                    </span>
                </div>
                <div class="text-xl font-bold num-tabular {{ $summary['net_cashflow'] >= 0 ? 'text-emerald-800' : 'text-rose-800' }}">
                    {{ $summary['net_cashflow'] >= 0 ? '+' : '' }}Rs. {{ number_format($summary['net_cashflow']) }}
                </div>
                <div class="text-xs mt-1 flex items-center justify-between {{ $summary['net_cashflow'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                    <span>Safe:</span>
                    <span class="font-bold">Rs. {{ number_format($summary['closing_cash_balance']) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Financial Roznamcha Summary Card (Cash Receipts vs Cash Outflows) -->
    <div class="bg-gradient-to-br from-slate-900 to-navy-900 text-white rounded-2xl p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 border-b border-slate-700 pb-6 mb-6">
            <div>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 uppercase tracking-wider">
                    Daily Financial Balance Sheet
                </span>
                <h2 class="text-xl font-bold text-white mt-2">Cash Roznamcha & Operating Position</h2>
                <p class="text-xs text-slate-400 mt-1">Real-time settlement of invoices, customer collections, raw material payments, petty cash disbursements & labour wages.</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-right">
                    <span class="text-[11px] uppercase tracking-wider text-slate-400 block">Total Invoiced Sales</span>
                    <span class="text-lg font-bold text-white num-tabular">Rs. {{ number_format($summary['sales_invoiced']) }}</span>
                </div>
                <div class="px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-right">
                    <span class="text-[11px] uppercase tracking-wider text-slate-400 block">Closing Cash in Safe</span>
                    <span class="text-lg font-bold text-emerald-400 num-tabular">Rs. {{ number_format($summary['closing_cash_balance']) }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Inflows -->
            <div class="p-5 rounded-xl bg-slate-800/60 border border-slate-700/80">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold uppercase text-emerald-400 tracking-wider flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">arrow_downward</span> Cash Inflows
                    </span>
                    <span class="text-xs font-bold text-emerald-400">Rs. {{ number_format($summary['cash_received']) }}</span>
                </div>
                <div class="space-y-2 text-xs text-slate-300">
                    <div class="flex justify-between py-1 border-b border-slate-700/50">
                        <span>Customer Cash Receipts:</span>
                        <span class="font-semibold text-white num-tabular">Rs. {{ number_format($vouchersToday->where('voucher_type', 'RECEIPT_VOUCHER')->where('payment_mode', 'CASH')->sum('total_amount')) }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span>Petty Cash Safe Replenishment:</span>
                        <span class="font-semibold text-white num-tabular">Rs. {{ number_format($pettyCashToday->where('transaction_type', 'CASH_IN')->sum('amount')) }}</span>
                    </div>
                </div>
            </div>

            <!-- Outflows -->
            <div class="p-5 rounded-xl bg-slate-800/60 border border-slate-700/80">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold uppercase text-rose-400 tracking-wider flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">arrow_upward</span> Cash Outflows
                    </span>
                    <span class="text-xs font-bold text-rose-400">Rs. {{ number_format($summary['cash_paid']) }}</span>
                </div>
                <div class="space-y-2 text-xs text-slate-300">
                    <div class="flex justify-between py-1 border-b border-slate-700/50">
                        <span>Petty Cash Expenses (Chai, Fuel, Repair):</span>
                        <span class="font-semibold text-white num-tabular">Rs. {{ number_format($summary['petty_cash_spent']) }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span>Vendor & Supplier Cash Payments:</span>
                        <span class="font-semibold text-white num-tabular">Rs. {{ number_format(max(0, $summary['cash_paid'] - $summary['petty_cash_spent'])) }}</span>
                    </div>
                </div>
            </div>

            <!-- Accrued / Liabilities -->
            <div class="p-5 rounded-xl bg-slate-800/60 border border-slate-700/80">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold uppercase text-amber-400 tracking-wider flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">pending_actions</span> Accruals & Liabilities
                    </span>
                    <span class="text-xs font-bold text-amber-400">Rs. {{ number_format($summary['wages_accrued']) }}</span>
                </div>
                <div class="space-y-2 text-xs text-slate-300">
                    <div class="flex justify-between py-1 border-b border-slate-700/50">
                        <span>Labour Wages Accrued:</span>
                        <span class="font-semibold text-white num-tabular">Rs. {{ number_format($summary['wages_accrued']) }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span>Worker Advances Disbursed:</span>
                        <span class="font-semibold text-white num-tabular">Rs. {{ number_format($summary['advances_deducted']) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Domain 1 & 2: Dispatches (Sales) and Raw Material Inward (Buying) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 3A. Dispatches & Cartons Outward -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-600 text-[20px]">local_shipping</span>
                    <h3 class="font-bold text-slate-900 text-sm">Dispatches & Gate Passes Outward</h3>
                </div>
                <span class="text-xs font-bold px-2 py-0.5 rounded bg-blue-100 text-blue-800">{{ $dispatches->count() }} Gate Passes</span>
            </div>
            <div class="p-4 flex-1">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-500 font-semibold uppercase text-[11px]">
                                <th class="pb-2.5">Pass / Challan</th>
                                <th class="pb-2.5">Vehicle</th>
                                <th class="pb-2.5">Customer Party</th>
                                <th class="pb-2.5 text-center">Bundles</th>
                                <th class="pb-2.5 text-right">Pcs</th>
                                <th class="pb-2.5 text-right no-print">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($dispatches as $d)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="py-2.5">
                                        <div class="font-bold text-blue-600">{{ $d->gate_pass_number }}</div>
                                        <div class="text-[10px] text-slate-400">Challan: {{ $d->bilti_challan_number ?: 'Auto' }}</div>
                                    </td>
                                    <td class="py-2.5">
                                        <div class="font-semibold text-slate-800">{{ $d->vehicle_number }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $d->driver_name ?? 'Driver' }}</div>
                                    </td>
                                    <td class="py-2.5">
                                        <div class="font-medium text-slate-900 truncate max-w-[140px]">{{ $d->party->name ?? 'Customer' }}</div>
                                        <div class="text-[10px] text-slate-400 truncate max-w-[140px]">{{ $d->material_summary }}</div>
                                    </td>
                                    <td class="py-2.5 text-center font-semibold text-slate-700 num-tabular">
                                        {{ $d->total_bundles ?: '-' }}
                                    </td>
                                    <td class="py-2.5 text-right font-bold text-slate-900 num-tabular">
                                        {{ number_format($d->total_pieces) }}
                                    </td>
                                    <td class="py-2.5 text-right no-print">
                                        <a href="{{ route('documents.delivery_challan', $d->id) }}" target="_blank"
                                           class="px-2 py-1 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 font-semibold text-[11px] inline-flex items-center gap-1 border border-blue-200">
                                            <span class="material-symbols-outlined text-[13px]">print</span> Challan
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-400">No carton dispatches recorded on this date.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($dispatches->isNotEmpty())
                            <tfoot>
                                <tr class="border-t-2 border-slate-300 font-bold text-slate-900 bg-slate-50">
                                    <td colspan="3" class="py-2.5">Total Outward</td>
                                    <td class="py-2.5 text-center num-tabular">{{ number_format($summary['dispatched_bundles']) }}</td>
                                    <td class="py-2.5 text-right num-tabular text-blue-700">{{ number_format($summary['dispatched_pcs']) }} Pcs</td>
                                    <td class="no-print"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <!-- 3B. Buying & Raw Material Inward (Paper Reels) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-600 text-[20px]">scale</span>
                    <h3 class="font-bold text-slate-900 text-sm">Buying & Raw Material Inward (Paper Reels)</h3>
                </div>
                <span class="text-xs font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-800">{{ $inwardTrucks->count() }} Inward Trucks</span>
            </div>
            <div class="p-4 flex-1">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-500 font-semibold uppercase text-[11px]">
                                <th class="pb-2.5">Gate Pass</th>
                                <th class="pb-2.5">Vehicle</th>
                                <th class="pb-2.5">Mill / Supplier</th>
                                <th class="pb-2.5">Material</th>
                                <th class="pb-2.5 text-right">Net Wt (Kg)</th>
                                <th class="pb-2.5 text-right">Tons</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($inwardTrucks as $inw)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="py-2.5 font-bold text-amber-700">{{ $inw->gate_pass_number }}</td>
                                    <td class="py-2.5">
                                        <div class="font-semibold text-slate-800">{{ $inw->vehicle_number }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $inw->driver_name ?? 'Driver' }}</div>
                                    </td>
                                    <td class="py-2.5 font-medium text-slate-900">{{ $inw->party->name ?? 'Paper Mill' }}</td>
                                    <td class="py-2.5 text-slate-600">{{ $inw->material_summary ?? 'Paper Reels' }}</td>
                                    <td class="py-2.5 text-right font-bold text-slate-900 num-tabular">{{ number_format($inw->net_weight_kg) }} kg</td>
                                    <td class="py-2.5 text-right font-bold text-amber-700 num-tabular">{{ number_format($inw->net_weight_kg / 1000, 2) }} T</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-400">No inward raw material trucks recorded on this date.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($inwardTrucks->isNotEmpty())
                            <tfoot>
                                <tr class="border-t-2 border-slate-300 font-bold text-slate-900 bg-slate-50">
                                    <td colspan="4" class="py-2.5">Total Inward Raw Material</td>
                                    <td class="py-2.5 text-right num-tabular">{{ number_format($summary['paper_reels_weight_kg']) }} kg</td>
                                    <td class="py-2.5 text-right num-tabular text-amber-700">{{ number_format($summary['paper_reels_weight_tons'], 2) }} Tons</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Domain 3 & 4: Printed Imports & Corrugation/Production Runs -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 4A. Outsourced Printed Top Sheets -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-purple-600 text-[20px]">layers</span>
                    <h3 class="font-bold text-slate-900 text-sm">Printed Imports (Outsourced Top Sheets)</h3>
                </div>
                <a href="{{ route('production.printed_imports') }}" class="text-xs text-purple-700 font-semibold hover:underline no-print">View Hub →</a>
            </div>
            <div class="p-4 flex-1">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-500 font-semibold uppercase text-[11px]">
                                <th class="pb-2.5">Design / Title</th>
                                <th class="pb-2.5">Buyer</th>
                                <th class="pb-2.5">City Sourced</th>
                                <th class="pb-2.5 text-right">Stock (Pcs)</th>
                                <th class="pb-2.5 text-right">Value (Rs)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($topSheetsToday as $ts)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="py-2.5 font-bold text-slate-800">{{ $ts->sheet_name }}</td>
                                    <td class="py-2.5 text-slate-700">{{ $ts->buyer_name }}</td>
                                    <td class="py-2.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                            {{ $ts->sourcing_city }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-right font-bold text-slate-900 num-tabular">{{ number_format($ts->stock_pieces) }}</td>
                                    <td class="py-2.5 text-right font-semibold text-purple-700 num-tabular">Rs. {{ number_format($ts->stock_pieces * $ts->unit_cost) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-400">No printed imports added on this date.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($topSheetsToday->isNotEmpty())
                            <tfoot>
                                <tr class="border-t-2 border-slate-300 font-bold text-slate-900 bg-slate-50">
                                    <td colspan="3" class="py-2.5">Total Printed Sheets</td>
                                    <td class="py-2.5 text-right num-tabular">{{ number_format($summary['top_sheets_received']) }} Pcs</td>
                                    <td class="py-2.5 text-right num-tabular text-purple-700">Rs. {{ number_format($summary['top_sheets_valuation']) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <!-- 4B. Corrugations (Nali) & Finishing Production -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-600 text-[20px]">view_quilt</span>
                    <h3 class="font-bold text-slate-900 text-sm">Corrugations (Nali) & Machine Output</h3>
                </div>
                <a href="{{ route('production.corrugations') }}" class="text-xs text-indigo-700 font-semibold hover:underline no-print">Corrugations Hub →</a>
            </div>
            <div class="p-4 flex-1">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-500 font-semibold uppercase text-[11px]">
                                <th class="pb-2.5">Run / Batch</th>
                                <th class="pb-2.5">Layer Type / Machine</th>
                                <th class="pb-2.5">Specification</th>
                                <th class="pb-2.5 text-center">Nalis</th>
                                <th class="pb-2.5 text-right">Output</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($corrugationsToday as $corr)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="py-2.5 font-bold text-indigo-700">{{ $corr->run_code }}</td>
                                    <td class="py-2.5">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            {{ str_replace('_', ' ', $corr->board_type ?? 'Single Wall') }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-slate-700">{{ $corr->sheet_size ?: 'Standard' }} · {{ $corr->deckle_size_inches }}″ Deckle</td>
                                    <td class="py-2.5 text-center font-bold text-slate-800 num-tabular">{{ number_format($corr->nalis_produced) }}</td>
                                    <td class="py-2.5 text-right font-bold text-emerald-700 num-tabular">{{ number_format($corr->completed_sheets_produced) }} Sheets</td>
                                </tr>
                            @empty
                            @endforelse

                            @forelse($batchesToday as $b)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="py-2.5 font-bold text-slate-800">{{ $b->batch_number }}</td>
                                    <td class="py-2.5 text-slate-600">{{ $b->current_machine }}</td>
                                    <td class="py-2.5 text-slate-700">{{ $b->cartonProduct->box_title ?? 'Carton' }}</td>
                                    <td class="py-2.5 text-center text-slate-400">-</td>
                                    <td class="py-2.5 text-right font-bold text-blue-700 num-tabular">{{ number_format($b->produced_quantity) }} Cartons</td>
                                </tr>
                            @empty
                            @endforelse

                            @if($corrugationsToday->isEmpty() && $batchesToday->isEmpty())
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-400">No corrugation or finishing runs recorded on this date.</td>
                                </tr>
                            @endif
                        </tbody>
                        @if($corrugationsToday->isNotEmpty() || $batchesToday->isNotEmpty())
                            <tfoot>
                                <tr class="border-t-2 border-slate-300 font-bold text-slate-900 bg-slate-50">
                                    <td colspan="3" class="py-2.5">Total Daily Production</td>
                                    <td class="py-2.5 text-center num-tabular text-indigo-700">{{ number_format($summary['nalis_produced']) }} Nalis</td>
                                    <td class="py-2.5 text-right num-tabular text-emerald-700">{{ number_format($summary['production_pcs']) }} Cartons</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Domain 5 & 6: Labour Attendance & Daily Petty Cash Expenses -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 5A. Labour Attendance & Wages Accrued -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-600 text-[20px]">badge</span>
                    <h3 class="font-bold text-slate-900 text-sm">Labour Attendance & Daily Wages</h3>
                </div>
                <div class="text-xs">
                    <span class="font-bold text-emerald-700">{{ $summary['workers_present'] }} Present</span>
                    <span class="text-slate-400 mx-1">·</span>
                    <span class="text-rose-600">{{ $summary['workers_absent'] }} Absent</span>
                </div>
            </div>
            <div class="p-4 flex-1">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-500 font-semibold uppercase text-[11px]">
                                <th class="pb-2.5">Worker</th>
                                <th class="pb-2.5">Role / Shift</th>
                                <th class="pb-2.5">Status</th>
                                <th class="pb-2.5 text-center">OT (hrs)</th>
                                <th class="pb-2.5 text-right">Wage (Rs)</th>
                                <th class="pb-2.5 text-right">Advance (Rs)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($attendanceToday as $att)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="py-2.5 font-bold text-slate-800">{{ $att->worker->name ?? 'Worker' }}</td>
                                    <td class="py-2.5 text-slate-600">{{ $att->worker->role ?? 'Operator' }} · {{ $att->shift }}</td>
                                    <td class="py-2.5">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $att->status === 'PRESENT' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                            {{ $att->status }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-center font-semibold text-slate-700 num-tabular">{{ $att->overtime_hours > 0 ? $att->overtime_hours : '-' }}</td>
                                    <td class="py-2.5 text-right font-bold text-slate-900 num-tabular">Rs. {{ number_format($att->daily_calculated_wage + $att->overtime_amount) }}</td>
                                    <td class="py-2.5 text-right font-semibold text-rose-600 num-tabular">{{ $att->advance_deducted > 0 ? 'Rs. ' . number_format($att->advance_deducted) : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-400">No attendance marked on this date.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($attendanceToday->isNotEmpty())
                            <tfoot>
                                <tr class="border-t-2 border-slate-300 font-bold text-slate-900 bg-slate-50">
                                    <td colspan="3" class="py-2.5">Total Daily Wages</td>
                                    <td class="py-2.5 text-center num-tabular">{{ $summary['overtime_hours'] }} hrs</td>
                                    <td class="py-2.5 text-right num-tabular text-emerald-700">Rs. {{ number_format($summary['wages_accrued']) }}</td>
                                    <td class="py-2.5 text-right num-tabular text-rose-600">Rs. {{ number_format($summary['advances_deducted']) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <!-- 5B. Daily Petty Cash Expenses & Cash Vouchers -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-rose-600 text-[20px]">payments</span>
                    <h3 class="font-bold text-slate-900 text-sm">Petty Cash Expenses & Factory Vouchers</h3>
                </div>
                <a href="{{ route('petty_cash.index') }}" class="text-xs text-rose-700 font-semibold hover:underline no-print">Petty Safe →</a>
            </div>
            <div class="p-4 flex-1">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-500 font-semibold uppercase text-[11px]">
                                <th class="pb-2.5">Receipt #</th>
                                <th class="pb-2.5">Category</th>
                                <th class="pb-2.5">Paid To / Detail</th>
                                <th class="pb-2.5">Type</th>
                                <th class="pb-2.5 text-right">Amount (Rs)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($pettyCashToday as $pc)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="py-2.5 font-bold text-slate-800">{{ $pc->transaction_code ?: '-' }}</td>
                                    <td class="py-2.5">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                            {{ $pc->category ?? 'General' }}
                                        </span>
                                    </td>
                                    <td class="py-2.5">
                                        <div class="font-semibold text-slate-800">{{ $pc->spent_by_or_paid_to ?? 'Factory Expense' }}</div>
                                        <div class="text-[10px] text-slate-400 truncate max-w-[180px]">{{ $pc->description }}</div>
                                    </td>
                                    <td class="py-2.5">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $pc->transaction_type === 'CASH_IN' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                            {{ $pc->transaction_type === 'CASH_IN' ? 'IN' : 'OUT' }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-right font-bold num-tabular {{ $pc->transaction_type === 'CASH_IN' ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ $pc->transaction_type === 'CASH_IN' ? '+' : '-' }}Rs. {{ number_format($pc->amount) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-400">No petty cash entries on this date.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($pettyCashToday->isNotEmpty())
                            <tfoot>
                                <tr class="border-t-2 border-slate-300 font-bold text-slate-900 bg-slate-50">
                                    <td colspan="4" class="py-2.5">Total Petty Cash Spent</td>
                                    <td class="py-2.5 text-right num-tabular text-rose-700">Rs. {{ number_format($summary['petty_cash_spent']) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 6. Bottom Signatures & Approvals for Physical Printing -->
    <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm print:border-none print:shadow-none">
        <div class="text-center font-bold text-slate-800 text-sm uppercase tracking-wider mb-8">
            Factory Daily Progress & Financial Verification Signatures
        </div>
        <div class="grid grid-cols-3 gap-8 text-center">
            <div class="border-t-2 border-slate-800 pt-3">
                <p class="font-bold text-sm text-slate-900">Mirza Sohail</p>
                <p class="text-xs text-slate-500">Prepared & Entered by (Admin)</p>
                <p class="text-[10px] text-slate-400 mt-1">Full ERP Authority</p>
            </div>
            <div class="border-t-2 border-slate-800 pt-3">
                <p class="font-bold text-sm text-slate-900">Dr. Ghulam Abbas</p>
                <p class="text-xs text-slate-500">Verified by (Manager / Son of Owner)</p>
                <p class="text-[10px] text-slate-400 mt-1">Factory Oversight</p>
            </div>
            <div class="border-t-2 border-slate-800 pt-3">
                <p class="font-bold text-sm text-slate-900">Company Owner</p>
                <p class="text-xs text-slate-500">Approved by (Executive Owner)</p>
                <p class="text-[10px] text-slate-400 mt-1">BK Packages Okara</p>
            </div>
        </div>
    </div>

</div>

<!-- JavaScript for One-Click WhatsApp Summary Copy -->
<script>
function copyWhatsAppSummary() {
    const summaryText = `*BK PACKAGES OKARA — DAILY CONCLUSION REPORT*
📅 *Date:* {{ \Carbon\Carbon::parse($date)->format('d F Y (l)') }}
──────────────────────
📦 *Dispatches:* {{ number_format($summary['dispatched_pcs']) }} Cartons ({{ $summary['trucks_out'] }} Vehicles Out)
📜 *Raw Material Inward:* {{ number_format($summary['paper_reels_weight_tons'], 1) }} Tons ({{ $summary['trucks_in'] }} Vehicles In)
🎨 *Printed Imports:* {{ number_format($summary['top_sheets_received']) }} Pcs Sourced (Rs. {{ number_format($summary['top_sheets_valuation']) }})
⚙️ *Production Output:* {{ number_format($summary['production_pcs']) }} Finished Cartons ({{ number_format($summary['nalis_produced']) }} Nalis)
👥 *Labour:* {{ $summary['workers_present'] }} Present | OT: {{ $summary['overtime_hours'] }} hrs (Rs. {{ number_format($summary['wages_accrued']) }})
💵 *Petty Cash Spent:* Rs. {{ number_format($summary['petty_cash_spent']) }}
──────────────────────
💰 *Invoiced Sales:* Rs. {{ number_format($summary['sales_invoiced']) }}
📥 *Cash Received:* Rs. {{ number_format($summary['cash_received']) }}
📤 *Cash Paid:* Rs. {{ number_format($summary['cash_paid']) }}
📈 *Net Cashflow:* Rs. {{ number_format($summary['net_cashflow']) }}
🔐 *Safe Balance:* Rs. {{ number_format($summary['closing_cash_balance']) }}
──────────────────────
Prepared & Verified: Mirza Sohail (Admin)`;

    navigator.clipboard.writeText(summaryText).then(() => {
        alert('Daily conclusion summary copied to clipboard! You can paste directly into WhatsApp.');
    }).catch(err => {
        console.error('Failed to copy: ', err);
        prompt('Copy your WhatsApp summary text below:', summaryText);
    });
}
</script>
@endsection
