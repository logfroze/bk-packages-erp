@extends('layouts.app')

@section('title', 'Petty Cash & Daily Factory Safe Ledger')
@section('breadcrumb', 'Petty Cash Management')

@section('header_actions')
    <a href="{{ route('petty_cash.print') }}" target="_blank"
        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-slate-300 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-xs transition-colors">
        <span class="material-symbols-outlined text-[16px]">print</span>
        <span>Print Today's Cash Sheet</span>
    </a>
    @if(auth()->user()?->canAddExpenses())
        <button onclick="openModal('modal-cash-in')"
            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 shadow-xs transition-colors">
            <span class="material-symbols-outlined text-[16px]">add_circle</span>
            <span>Cash In / Counter Sale</span>
        </button>
        <button onclick="openModal('modal-cash-out')"
            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700 shadow-xs transition-colors">
            <span class="material-symbols-outlined text-[16px]">remove_circle</span>
            <span>Record Expense (Kharch)</span>
        </button>
    @endif
@endsection

@section('content')
<div class="space-y-6">
    <!-- Top Financial Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Running Safe Balance -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Current Safe Balance</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">payments</span>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-navy-900 num-tabular">
                Rs. {{ number_format($currentBalance, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500 flex items-center gap-1">
                <span class="w-2 h-2 rounded-full {{ $currentBalance >= 0 ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                <span>Factory Cash Drawer / Safe</span>
            </div>
        </div>

        <!-- Today Inflow -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Today's Inflow (Aamad)</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">trending_up</span>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-emerald-600 num-tabular">
                Rs. {{ number_format($todayIn, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">
                Opening float & cash spot sales
            </div>
        </div>

        <!-- Today Outflow -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Today's Expenses (Kharch)</span>
                <div class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">trending_down</span>
                </div>
            </div>
            <div class="mt-3 text-2xl font-bold text-red-600 num-tabular">
                Rs. {{ number_format($todayOut, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">
                Chai, petrol, hardware, repairs
            </div>
        </div>

        <!-- Net Today -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Net Movement Today</span>
                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">account_balance</span>
                </div>
            </div>
            @php $netToday = $todayIn - $todayOut; @endphp
            <div class="mt-3 text-2xl font-bold {{ $netToday >= 0 ? 'text-navy-900' : 'text-amber-600' }} num-tabular">
                {{ $netToday >= 0 ? '+' : '' }}Rs. {{ number_format($netToday, 2) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">
                Today's net cash change
            </div>
        </div>
    </div>

    <!-- Filter Pills -->
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl">
            <a href="{{ route('petty_cash.index', ['type' => 'ALL']) }}"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ $type === 'ALL' ? 'bg-white text-navy-900 shadow-xs font-semibold' : 'text-slate-600 hover:text-navy-900' }}">
                All Transactions
            </a>
            <a href="{{ route('petty_cash.index', ['type' => 'CASH_OUT']) }}"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ $type === 'CASH_OUT' ? 'bg-white text-navy-900 shadow-xs font-semibold' : 'text-slate-600 hover:text-navy-900' }}">
                Expenses Only (Kharch)
            </a>
            <a href="{{ route('petty_cash.index', ['type' => 'CASH_IN']) }}"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ $type === 'CASH_IN' ? 'bg-white text-navy-900 shadow-xs font-semibold' : 'text-slate-600 hover:text-navy-900' }}">
                Float / Inflows (Aamad)
            </a>
            <a href="{{ route('petty_cash.index', ['type' => 'COUNTER_SALE']) }}"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ $type === 'COUNTER_SALE' ? 'bg-white text-navy-900 shadow-xs font-semibold' : 'text-slate-600 hover:text-navy-900' }}">
                Cash Spot Sales
            </a>
        </div>
    </div>

    <!-- Transactions Ledger Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <div class="font-bold text-sm text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-600">receipt</span>
                <span>Petty Cash Book & Expense Journal</span>
            </div>
            <span class="text-xs text-slate-500">{{ $transactions->total() }} total entries</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 uppercase font-semibold">
                    <tr>
                        <th class="py-3 px-4">Date & Time</th>
                        <th class="py-3 px-4">Type</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4">Description / Narration</th>
                        <th class="py-3 px-4">Handled By</th>
                        <th class="py-3 px-4 text-right">Inflow (Aamad)</th>
                        <th class="py-3 px-4 text-right">Outflow (Kharch)</th>
                        <th class="py-3 px-4 text-right">Running Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transactions as $txn)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3 px-4 text-slate-500 whitespace-nowrap">
                                <span class="font-semibold text-slate-800">{{ $txn->transaction_date->format('d M Y') }}</span>
                                <span class="text-[10px] text-slate-400 block">{{ $txn->created_at->format('h:i A') }}</span>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                @if($txn->transaction_type === 'CASH_OUT')
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-50 text-red-700 border border-red-200">
                                        Expense
                                    </span>
                                @elseif($txn->transaction_type === 'COUNTER_SALE')
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Spot Sale
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                        Inflow / Float
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-semibold text-slate-800 whitespace-nowrap">
                                {{ $categories[$txn->category] ?? str_replace('_', ' ', $txn->category) }}
                            </td>
                            <td class="py-3 px-4 text-slate-700 max-w-xs">
                                {{ $txn->description }}
                            </td>
                            <td class="py-3 px-4 text-slate-600 whitespace-nowrap">
                                {{ $txn->spent_by_or_paid_to ?: $txn->recorded_by }}
                            </td>
                            <td class="py-3 px-4 text-right font-bold text-emerald-600 num-tabular whitespace-nowrap">
                                {{ in_array($txn->transaction_type, ['CASH_IN', 'COUNTER_SALE']) ? 'Rs. ' . number_format($txn->amount, 2) : '—' }}
                            </td>
                            <td class="py-3 px-4 text-right font-bold text-red-600 num-tabular whitespace-nowrap">
                                {{ $txn->transaction_type === 'CASH_OUT' ? 'Rs. ' . number_format($txn->amount, 2) : '—' }}
                            </td>
                            <td class="py-3 px-4 text-right font-extrabold text-navy-900 num-tabular whitespace-nowrap">
                                Rs. {{ number_format($txn->running_balance, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <span class="material-symbols-outlined text-4xl block mb-2 text-slate-300">receipt_long</span>
                                No petty cash transactions recorded yet. Click above to record cash in or daily expense.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@section('modals')
<!-- Modal: Record Expense (Kharch) -->
<div id="modal-cash-out" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">remove_circle</span>
                </div>
                <h3 class="text-base font-bold text-slate-900">Record Cash Expense (Factory Kharch)</h3>
            </div>
            <button onclick="closeModal('modal-cash-out')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="{{ route('petty_cash.store') }}" method="POST" class="space-y-4 mt-4">
            @csrf
            <input type="hidden" name="transaction_type" value="CASH_OUT" />

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Expense Category *</label>
                <select name="category" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                    <option value="TEA_REFRESHMENT">Tea & Refreshments (Chai/Khana)</option>
                    <option value="PETROL_TRANSPORT">Petrol & Fuel (Gaadi/Generator)</option>
                    <option value="HARDWARE_PURCHASE">Hardware & Small Tools (Nuts/Bolts/Tools)</option>
                    <option value="SPARE_PARTS">Machine Spare Parts (Chhotay Parts)</option>
                    <option value="OFFICE_SUPPLIES">Office Supplies & Stationery</option>
                    <option value="MISCELLANEOUS">Miscellaneous Factory Expense</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Amount (Rs.) *</label>
                    <input type="number" step="0.01" min="1" name="amount" required placeholder="e.g. 350"
                        class="w-full rounded-lg border-slate-300 text-sm font-bold focus:border-blue-600 focus:ring-blue-600 font-mono" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Paid To / Spent By</label>
                    <input type="text" name="spent_by_or_paid_to" placeholder="e.g. Asif Driver / Canteen"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Description / Tafseel *</label>
                <input type="text" name="description" required placeholder="e.g. 5 Liters petrol for factory pickup loader"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="closeModal('modal-cash-out')"
                    class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Cancel</button>
                <button type="submit"
                    class="px-5 py-2 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700 shadow-xs">
                    Save Expense Entry
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Record Inflow / Counter Sale -->
<div id="modal-cash-in" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">add_circle</span>
                </div>
                <h3 class="text-base font-bold text-slate-900">Record Cash Inflow / Spot Sale / Float</h3>
            </div>
            <button onclick="closeModal('modal-cash-in')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="{{ route('petty_cash.store') }}" method="POST" class="space-y-4 mt-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Transaction Nature *</label>
                <select name="transaction_type" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                    <option value="CASH_IN">Cash Deposit / Opening Safe Float</option>
                    <option value="COUNTER_SALE">Minor Spot Cash Sale (Chemical / Rolls / Waste)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Category *</label>
                <select name="category" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                    <option value="OPENING_FLOAT">Opening Safe Float / Cash Addition</option>
                    <option value="CHEMICAL_SALE">Chemical Spot Cash Sale</option>
                    <option value="ROLL_SALE">Paper Roll Minor Spot Sale</option>
                    <option value="MISCELLANEOUS">Other Cash Inflow</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Amount (Rs.) *</label>
                    <input type="number" step="0.01" min="1" name="amount" required placeholder="e.g. 50000"
                        class="w-full rounded-lg border-slate-300 text-sm font-bold focus:border-blue-600 focus:ring-blue-600 font-mono" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Received From</label>
                    <input type="text" name="spent_by_or_paid_to" placeholder="e.g. Director / Counter Buyer"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Description / Narration *</label>
                <input type="text" name="description" required placeholder="e.g. Added to safe drawer for weekly operational expenses"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="closeModal('modal-cash-in')"
                    class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Cancel</button>
                <button type="submit"
                    class="px-5 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 shadow-xs">
                    Save Cash Inflow
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
