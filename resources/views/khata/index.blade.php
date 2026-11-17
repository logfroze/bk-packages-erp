@extends('layouts.app')

@section('title', 'Party Khata & Local Vendor Ledgers')
@section('breadcrumb', 'Party Khata')

@section('header_actions')
    <div class="flex items-center gap-2 flex-wrap">
        <button onclick="openModal('modal-new-party')" class="px-3.5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs transition-colors">
            + Add Party / Local Vendor
        </button>
        <button onclick="openModal('modal-voucher')" class="px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50 shadow-xs transition-colors">
            + Record Voucher / Payment
        </button>
    </div>
@endsection

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Party Khata & Vendor Management</h1>
            <p class="text-xs text-slate-500 mt-1">Manage corporate carton buyers, paper mills, and local Okara vendor shops (hardware, spare parts, fuel)</p>
        </div>

        <!-- Filter Classification Tabs -->
        <div class="inline-flex rounded-xl bg-white border border-slate-200 p-1 shadow-xs flex-wrap">
            <a href="{{ route('khata.index', ['type' => 'CUSTOMER', 'classification' => 'ALL']) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $type === 'CUSTOMER' && $classification === 'ALL' ? 'bg-navy-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                Carton Buyers
            </a>
            <a href="{{ route('khata.index', ['type' => 'ALL', 'classification' => 'LOCAL_VENDOR_SHOP']) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $classification === 'LOCAL_VENDOR_SHOP' ? 'bg-navy-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                Local Vendor Shops
            </a>
            <a href="{{ route('khata.index', ['type' => 'SUPPLIER', 'classification' => 'PAPER_MILL']) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $classification === 'PAPER_MILL' ? 'bg-navy-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                Paper Mills
            </a>
            <a href="{{ route('khata.index', ['type' => 'ALL', 'classification' => 'ALL']) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $type === 'ALL' && $classification === 'ALL' ? 'bg-navy-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                All Accounts
            </a>
        </div>
    </div>

    <!-- 4 Financial Summary Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Receivables (Lena)</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">south_west</span>
                </div>
            </div>
            <div class="mt-2 text-2xl font-bold text-navy-900 num-tabular">
                Rs. {{ number_format($totalReceivables) }}
            </div>
            <div class="text-xs text-slate-400 mt-1">Pending from carton buyers</div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Payables (Dena)</span>
                <div class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">north_east</span>
                </div>
            </div>
            <div class="mt-2 text-2xl font-bold text-red-600 num-tabular">
                Rs. {{ number_format($totalPayables) }}
            </div>
            <div class="text-xs text-slate-400 mt-1">Paper mills & local vendors</div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Local Vendor Dena</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">store</span>
                </div>
            </div>
            <div class="mt-2 text-2xl font-bold text-amber-700 num-tabular">
                Rs. {{ number_format($localVendorPayables) }}
            </div>
            <div class="text-xs text-slate-400 mt-1">Hardware, fuel & spare shops</div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Cash in Hand / Till</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">payments</span>
                </div>
            </div>
            <div class="mt-2 text-2xl font-bold text-emerald-600 num-tabular">
                Rs. {{ number_format($cashInHand) }}
            </div>
            <div class="text-xs text-slate-400 mt-1">Available office cash</div>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <form method="GET" action="{{ route('khata.index') }}" class="flex gap-2 max-w-lg">
        <input type="hidden" name="type" value="{{ $type }}" />
        <input type="hidden" name="classification" value="{{ $classification }}" />
        <input type="text" name="q" value="{{ $search }}" placeholder="Search shop name, owner, city, or phone..."
               class="flex-1 rounded-xl border-slate-300 text-xs py-2 px-3 bg-white focus:border-blue-600 focus:ring-blue-600" />
        <button type="submit" class="px-4 py-2 rounded-xl bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs">
            Search
        </button>
        @if($search)
            <a href="{{ route('khata.index', ['type' => $type, 'classification' => $classification]) }}"
               class="px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium text-slate-600 hover:bg-slate-50 flex items-center">
                Clear
            </a>
        @endif
    </form>

    <!-- Party Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($parties as $party)
            @php
                $bal = $party->current_balance;
                $isReceivable = $bal > 0;
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs hover:border-blue-400 transition-all flex flex-col justify-between gap-4 group">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-1.5">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                {{ $party->classification === 'LOCAL_VENDOR_SHOP' ? 'bg-amber-100 text-amber-800' : ($party->type === 'CUSTOMER' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700') }}">
                                {{ str_replace('_', ' ', $party->classification ?: $party->type) }}
                            </span>
                            @if($party->vendor_category)
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600">
                                    {{ $party->vendor_category }}
                                </span>
                            @endif
                        </div>
                        <span class="text-xs text-slate-400 font-medium">{{ $party->city }}</span>
                    </div>

                    <a href="{{ route('khata.show', $party) }}" class="block">
                        <h3 class="text-base font-bold text-slate-900 group-hover:text-blue-600 transition-colors">{{ $party->name }}</h3>
                        <p class="text-xs text-slate-500 mt-1">{{ $party->phone }} {{ $party->contact_person ? '· ' . $party->contact_person : '' }}</p>
                        @if($party->address)
                            <p class="text-[11px] text-slate-400 mt-0.5 truncate">{{ $party->address }}</p>
                        @endif
                    </a>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">
                            {{ $isReceivable ? 'Receivable (Lena / Dr)' : ($bal < 0 ? 'Payable (Dena / Cr)' : 'Zero Settled') }}
                        </span>
                        <span class="text-lg font-extrabold num-tabular {{ $isReceivable ? 'text-navy-900' : ($bal < 0 ? 'text-red-600' : 'text-emerald-600') }}">
                            Rs. {{ number_format(abs($bal)) }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" onclick="openAdjustModal({{ json_encode($party) }})"
                            title="Audit Balance Adjustment"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                            <span class="material-symbols-outlined text-[18px]">tune</span>
                        </button>
                        <a href="{{ route('khata.show', $party) }}"
                            class="px-3 py-1.5 rounded-lg bg-slate-50 hover:bg-blue-50 text-blue-600 text-xs font-semibold inline-flex items-center gap-1 transition-colors">
                            <span>Khata</span>
                            <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-400">
                <span class="material-symbols-outlined text-4xl block mb-2 text-slate-300">account_balance_wallet</span>
                No party or vendor accounts matched your filter. Click "+ Add Party / Local Vendor" above.
            </div>
        @endforelse
    </div>
</div>
@endsection

@section('modals')
<!-- Add New Party / Local Vendor Modal -->
<div id="modal-new-party" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Add Customer, Mill or Local Vendor Shop</h3>
            <button onclick="closeModal('modal-new-party')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form action="{{ route('khata.party.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Shop / Business / Party Name *</label>
                <input type="text" name="name" required placeholder="e.g. Al-Madina Hardware & Tools / Irfan Pharma"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Account Classification *</label>
                    <select name="classification" id="party-classification-select" required onchange="toggleVendorCategory(this.value)"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                        <option value="LOCAL_VENDOR_SHOP">Local Vendor Shop (Hardware/Parts)</option>
                        <option value="CORPORATE_CLIENT">Corporate Client (Carton Buyer)</option>
                        <option value="PAPER_MILL">Paper Mill (Kraft Supplier)</option>
                        <option value="TRANSPORTER">Transporter / Logistics</option>
                        <option value="GENERAL_SUPPLIER">General Supplier</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Account Type *</label>
                    <select name="type" id="party-type-select" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                        <option value="SUPPLIER">Supplier (Vendor / Kharch)</option>
                        <option value="CUSTOMER">Customer (Buyer)</option>
                        <option value="TRANSPORTER">Transporter</option>
                    </select>
                </div>
            </div>

            <div id="vendor-category-wrap">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Vendor Category</label>
                <select name="vendor_category" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                    <option value="Hardware & Tools">Hardware & Tools (Nuts, Bolts, Blades)</option>
                    <option value="Diesel & Petrol Pump">Diesel & Petrol Pump (Fuel)</option>
                    <option value="Electric Motors & Rewinding">Electric Motors & Rewinding</option>
                    <option value="Bearings & Belts">Bearings, Belts & Pulleys</option>
                    <option value="Printing Plates & Blocks">Printing Plates & Stereo Blocks</option>
                    <option value="General Spare Parts">General Machine Spare Parts</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Contact Person / Owner</label>
                    <input type="text" name="contact_person" placeholder="e.g. Haji Muhammad Aslam"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Phone Number *</label>
                    <input type="text" name="phone" required placeholder="0300-1234567"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">City *</label>
                    <input type="text" name="city" value="Okara" required
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Address / Shop Location</label>
                    <input type="text" name="address" placeholder="e.g. Railway Road, Okara"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
                </div>
            </div>

            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                <div class="text-xs font-bold text-slate-800 mb-2">Opening Khata Balance (Purana Baqaya)</div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Amount (Rs.)</label>
                        <input type="number" step="0.01" min="0" name="opening_balance" value="0"
                            class="w-full rounded-lg border-slate-300 text-sm font-bold font-mono" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Balance Type</label>
                        <select name="balance_type" class="w-full rounded-lg border-slate-300 text-xs">
                            <option value="PAYABLE">Payable (Dena Hai / Credit)</option>
                            <option value="RECEIVABLE">Receivable (Lena Hai / Debit)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-new-party')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs">Save Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Record Accounting Voucher / Payment -->
<div id="modal-voucher" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Record Accounting Voucher / Payment</h3>
            <button onclick="closeModal('modal-voucher')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form action="{{ route('khata.voucher.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Voucher Type *</label>
                <select name="voucher_type" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                    <option value="PAYMENT_VOUCHER">Payment Voucher (PV — Cash/Bank Paid to Vendor)</option>
                    <option value="RECEIPT_VOUCHER">Receipt Voucher (CR — Cash/Bank Received from Buyer)</option>
                    <option value="PURCHASE_BILL">Purchase Bill (PB — Inbound Goods / Raw Paper Bill)</option>
                    <option value="SALES_INVOICE">Sales Invoice (INV — Outbound Finished Carton Bill)</option>
                    <option value="EXPENSE_VOUCHER">Expense Voucher (EXP — General Overhead)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Party / Vendor *</label>
                <select name="party_id" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                    <option value="">-- Select Party / Shop --</option>
                    @foreach($parties as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->city }}) — Bal: Rs. {{ number_format($p->current_balance) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Amount (Rs.) *</label>
                    <input type="number" step="0.01" min="1" name="total_amount" required placeholder="e.g. 25000"
                        class="w-full rounded-lg border-slate-300 text-sm font-bold font-mono focus:border-blue-600 focus:ring-blue-600" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Mode *</label>
                    <select name="payment_mode" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                        <option value="CASH">Cash (Office Drawer)</option>
                        <option value="ONLINE_BANK">Online Bank Transfer</option>
                        <option value="CHEQUE">Bank Cheque</option>
                        <option value="CREDIT">On Credit (Khata Udhaar)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ref / Bill / Cheque #</label>
                    <input type="text" name="reference_number" placeholder="e.g. Bill # 402 / Chq 8921"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Voucher Date</label>
                    <input type="date" name="voucher_date" value="{{ now()->toDateString() }}"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Description / Narration *</label>
                <input type="text" name="description" required placeholder="e.g. Paid for machine spare parts / Cash receipt"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
            </div>

            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-voucher')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs">Post Voucher</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Balance Adjustment (Audit-Safe, Never Deletes History) -->
<div id="modal-adjust-balance" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900" id="adjust-party-name">Adjust Khata Balance</h3>
            <button onclick="closeModal('modal-adjust-balance')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <p class="text-xs text-slate-500 mt-2">
            <strong>Reversal & Audit Principle:</strong> Creates an official ledger entry so financial history remains uncorrupted.
        </p>
        <form id="adjust-party-form" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Adjustment Action *</label>
                <select name="adjustment_type" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                    <option value="DEBIT_INCREASE">Debit (+) Increase Receivable / Pay to Vendor</option>
                    <option value="CREDIT_DECREASE">Credit (-) Receive from Client / Discount Given</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Amount (Rs.) *</label>
                <input type="number" step="0.01" min="0.01" name="amount" required placeholder="e.g. 1500"
                    class="w-full rounded-lg border-slate-300 text-sm font-bold font-mono focus:border-blue-600 focus:ring-blue-600" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Reason / Audit Justification *</label>
                <input type="text" name="reason" required placeholder="e.g. Year-end rounding off / settled via mutual discount"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
            </div>
            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-adjust-balance')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs">Post Audit Adjustment</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleVendorCategory(classification) {
    const wrap = document.getElementById('vendor-category-wrap');
    const typeSelect = document.getElementById('party-type-select');
    if (classification === 'LOCAL_VENDOR_SHOP') {
        wrap.classList.remove('hidden');
        typeSelect.value = 'SUPPLIER';
    } else if (classification === 'CORPORATE_CLIENT') {
        wrap.classList.add('hidden');
        typeSelect.value = 'CUSTOMER';
    } else {
        wrap.classList.add('hidden');
    }
}

function openAdjustModal(party) {
    document.getElementById('adjust-party-name').textContent = 'Audit Adjust: ' + party.name;
    document.getElementById('adjust-party-form').action = '/khata/party/' + party.id + '/adjust';
    openModal('modal-adjust-balance');
}
</script>
@endpush
