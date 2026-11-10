@extends('layouts.app')

@section('title', $party->name . ' — Khata')
@section('breadcrumb', 'Party Khata / ' . $party->name)

@section('header_actions')
    <div class="flex items-center gap-2 no-print">
        @php
            $waSummary = urlencode("*BK PACKAGES - KHATA STATEMENT*\nParty: {$party->name}\nClosing Balance: Rs. " . number_format(abs($party->current_balance)) . ($party->current_balance >= 0 ? ' (Receivable)' : ' (Payable)') . "\nTotal Debits: Rs. " . number_format($totalDebit) . "\nTotal Credits: Rs. " . number_format($totalCredit) . "\n\nSent by Mirza Sohail (BK Packages Okara)");
        @endphp
        <a href="https://wa.me/?text={{ $waSummary }}" target="_blank"
           class="px-3.5 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 inline-flex items-center gap-1.5">
            WhatsApp Statement
        </a>
        <button onclick="window.print()"
                class="px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50 inline-flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px]">print</span>
            Print
        </button>
        <button onclick="openModal('modal-party-voucher')"
                class="px-3.5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800">
            + Add Entry
        </button>
    </div>
@endsection

@section('content')
    <div class="mb-4 no-print">
        <a href="{{ route('khata.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-900 inline-flex items-center gap-1">
            ← Back to All Parties
        </a>
    </div>

    <!-- Party Header & Net Baqaya Card -->
    <div class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-50 text-blue-700">{{ $party->type }}</span>
                <span class="text-xs text-slate-400">{{ $party->code }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $party->name }}</h1>
            <p class="text-sm text-slate-500 mt-1">{{ $party->address }} · {{ $party->city }} · Ph: {{ $party->phone }}</p>
        </div>

        <div class="flex items-center gap-8 border-t md:border-t-0 pt-4 md:pt-0 border-slate-100">
            <div>
                <span class="text-xs uppercase font-semibold text-slate-400 block">Total Invoiced (Dr)</span>
                <span class="text-base font-semibold text-slate-800 num-tabular">Rs. {{ number_format($totalDebit) }}</span>
            </div>
            <div>
                <span class="text-xs uppercase font-semibold text-slate-400 block">Total Paid (Cr)</span>
                <span class="text-base font-semibold text-emerald-700 num-tabular">Rs. {{ number_format($totalCredit) }}</span>
            </div>
            <div>
                <span class="text-xs uppercase font-semibold text-slate-400 block">Net Closing Baqaya</span>
                <span class="text-2xl font-bold num-tabular {{ $party->current_balance >= 0 ? 'text-slate-900' : 'text-red-600' }}">
                    Rs. {{ number_format(abs($party->current_balance)) }}
                    <span class="text-xs font-semibold">{{ $party->current_balance >= 0 ? 'Dr' : 'Cr' }}</span>
                </span>
            </div>
        </div>
    </div>

    <!-- Clean Ledger Statement Table -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-5">Date</th>
                        <th class="py-3.5 px-5">Voucher #</th>
                        <th class="py-3.5 px-5">Details / Narration</th>
                        <th class="py-3.5 px-5 text-right">Debit (Dr)</th>
                        <th class="py-3.5 px-5 text-right">Credit (Cr)</th>
                        <th class="py-3.5 px-5 text-right">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($entries as $entry)
                        <tr class="hover:bg-slate-50/70">
                            <td class="py-4 px-5 text-slate-600 num-tabular whitespace-nowrap">{{ $entry->entry_date->format('d M Y') }}</td>
                            <td class="py-4 px-5 font-semibold text-blue-600 whitespace-nowrap">
                                {{ $entry->voucher->voucher_number ?? 'OPENING' }}
                            </td>
                            <td class="py-4 px-5 text-slate-800">{{ $entry->narration }}</td>
                            <td class="py-4 px-5 text-right num-tabular font-medium text-slate-900">
                                {{ $entry->debit > 0 ? 'Rs. ' . number_format($entry->debit) : '-' }}
                            </td>
                            <td class="py-4 px-5 text-right num-tabular font-medium text-emerald-600">
                                {{ $entry->credit > 0 ? 'Rs. ' . number_format($entry->credit) : '-' }}
                            </td>
                            <td class="py-4 px-5 text-right num-tabular font-bold text-slate-900 whitespace-nowrap">
                                Rs. {{ number_format(abs($entry->running_balance)) }} {{ $entry->running_balance >= 0 ? 'Dr' : 'Cr' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500">No ledger transactions recorded for this party yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('modals')
    <div id="modal-party-voucher" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-lg font-semibold text-slate-900">New Ledger Entry — {{ $party->name }}</h3>
                <button onclick="closeModal('modal-party-voucher')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('khata.voucher.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="party_id" value="{{ $party->id }}" />
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Transaction Type</label>
                    <select name="voucher_type" class="w-full rounded-lg border-slate-300 text-sm">
                        @if($party->type === 'CUSTOMER')
                            <option value="RECEIPT_VOUCHER">Payment Received (Cash / Cheque)</option>
                            <option value="SALES_INVOICE">Manual Sales Bill / Invoice (Debit)</option>
                        @else
                            <option value="PAYMENT_VOUCHER">Payment Made to Supplier (Debit)</option>
                            <option value="PURCHASE_BILL">Paper / Material Purchase Bill (Credit)</option>
                        @endif
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Amount (Rs.)</label>
                        <input type="number" name="total_amount" min="1" step="0.01" required class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Mode</label>
                        <select name="payment_mode" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="CASH">Cash</option>
                            <option value="CHEQUE">Cheque</option>
                            <option value="ONLINE_BANK">Online Bank</option>
                            <option value="CREDIT">Credit Bill</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cheque / Reference # (Optional)</label>
                    <input type="text" name="reference_number" class="w-full rounded-lg border-slate-300 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Narration / Description</label>
                    <input type="text" name="description" required placeholder="Enter transaction note..." class="w-full rounded-lg border-slate-300 text-sm" />
                </div>
                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-party-voucher')" class="px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-sm font-medium">Post to Khata</button>
                </div>
            </form>
        </div>
    </div>
@endsection

