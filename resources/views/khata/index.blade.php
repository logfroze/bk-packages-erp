@extends('layouts.app')

@section('title', 'Party Khata & Accounts')
@section('breadcrumb', 'Party Khata')

@section('header_actions')
    <div class="flex items-center gap-2">
        <button onclick="openModal('modal-new-party')" class="px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50">
            + Add Party
        </button>
        <button onclick="openModal('modal-expense')" class="px-3.5 py-2 rounded-lg bg-navy-900 text-white text-sm font-medium hover:bg-navy-800">
            + Log Cash / Expense
        </button>
    </div>
@endsection

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Party Khata & Ledgers</h1>
            <p class="text-sm text-slate-500 mt-1">Select a customer or paper mill to view their ledger statement and record payments</p>
        </div>

        <div class="inline-flex rounded-xl bg-white border border-slate-200 p-1">
            @foreach(['CUSTOMER' => 'Customers (Buyers)', 'SUPPLIER' => 'Paper Mills (Suppliers)', 'ALL' => 'All Parties'] as $k => $v)
                <a href="{{ route('khata.index', ['type' => $k]) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-semibold {{ $type === $k ? 'bg-navy-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                    {{ $v }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Clean 3-Card Financial Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Receivables (Lena Hai)</div>
            <div class="text-2xl font-bold text-slate-900 mt-2 num-tabular">Rs. {{ number_format($totalReceivables) }}</div>
            <div class="text-xs text-slate-500 mt-1">Pending from Carton Buyers</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Payables (Dena Hai)</div>
            <div class="text-2xl font-bold text-slate-900 mt-2 num-tabular">Rs. {{ number_format($totalPayables) }}</div>
            <div class="text-xs text-slate-500 mt-1">Payable to Paper & Board Mills</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Cash in Hand (Office Till)</div>
            <div class="text-2xl font-bold text-emerald-600 mt-2 num-tabular">Rs. {{ number_format($cashInHand) }}</div>
            <div class="text-xs text-slate-500 mt-1">Available Cash Balance</div>
        </div>
    </div>

    <!-- Search Bar -->
    <form method="GET" action="{{ route('khata.index') }}" class="mb-6">
        <input type="hidden" name="type" value="{{ $type }}" />
        <div class="flex gap-2 max-w-md">
            <input type="text" name="q" value="{{ $search }}" placeholder="Search party name, city, or phone..."
                   class="flex-1 rounded-xl border-slate-300 text-sm bg-white" />
            <button type="submit" class="px-4 py-2 rounded-xl bg-white border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Search
            </button>
        </div>
    </form>

    <!-- Clean Party Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($parties as $party)
            @php
                $bal = $party->current_balance;
                $isReceivable = $bal > 0;
            @endphp
            <a href="{{ route('khata.show', $party) }}"
               class="bg-white rounded-xl border border-slate-200 p-6 hover:border-blue-400 hover:shadow-sm transition-all flex flex-col justify-between gap-4 group">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold {{ $party->type === 'CUSTOMER' ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700' }}">
                            {{ $party->type }}
                        </span>
                        <span class="text-xs text-slate-400">{{ $party->city }}</span>
                    </div>
                    <h3 class="text-base font-semibold text-slate-900 group-hover:text-blue-600 transition-colors">{{ $party->name }}</h3>
                    <p class="text-xs text-slate-500 mt-1">{{ $party->phone }} {{ $party->contact_person ? '· ' . $party->contact_person : '' }}</p>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] uppercase font-semibold text-slate-400 block">
                            {{ $isReceivable ? 'Receivable (Dr)' : ($bal < 0 ? 'Payable (Cr)' : 'Settled') }}
                        </span>
                        <span class="text-lg font-bold num-tabular {{ $isReceivable ? 'text-slate-900' : ($bal < 0 ? 'text-red-600' : 'text-emerald-600') }}">
                            Rs. {{ number_format(abs($bal)) }}
                        </span>
                    </div>
                    <span class="text-xs font-semibold text-blue-600 group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">
                        Open Khata →
                    </span>
                </div>
            </a>
        @empty
            <div class="col-span-full bg-white rounded-xl border border-slate-200 p-12 text-center text-slate-500">
                No party accounts matched your filter.
            </div>
        @endforelse
    </div>
@endsection

@section('modals')
    <!-- Add New Party Modal -->
    <div id="modal-new-party" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-lg font-semibold text-slate-900">Add New Customer or Mill</h3>
                <button onclick="closeModal('modal-new-party')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('khata.party.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Party / Company Name</label>
                    <input type="text" name="name" required placeholder="e.g. Momin Ghee Mills Pvt Ltd" class="w-full rounded-lg border-slate-300 text-sm" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Account Type</label>
                        <select name="type" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="CUSTOMER">Customer (Buyer)</option>
                            <option value="SUPPLIER">Supplier (Paper Mill)</option>
                            <option value="TRANSPORTER">Transporter</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">City</label>
                        <input type="text" name="city" value="Okara" required class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Phone Number</label>
                        <input type="text" name="phone" required placeholder="0300-XXXXXXX" class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Opening Baqaya (Rs.)</label>
                        <input type="number" name="opening_balance" value="0" step="0.01" class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                </div>
                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-new-party')" class="px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-sm font-medium">Create Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Log General Factory Expense / Voucher -->
    <div id="modal-expense" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-lg font-semibold text-slate-900">Record Factory Expense / Cash Payment</h3>
                <button onclick="closeModal('modal-expense')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('khata.voucher.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="voucher_type" value="EXPENSE_VOUCHER" />
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Expense Description</label>
                    <input type="text" name="description" required placeholder="e.g. Diesel for Generator / Machine Repair / Tea" class="w-full rounded-lg border-slate-300 text-sm" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Amount (Rs.)</label>
                        <input type="number" name="total_amount" min="1" step="0.01" required class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Mode</label>
                        <select name="payment_mode" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="CASH">Cash (Office Till)</option>
                            <option value="ONLINE_BANK">Bank Transfer</option>
                        </select>
                    </div>
                </div>
                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-expense')" class="px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-sm font-medium">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
@endsection

