@extends('layouts.app')

@section('title', $party->name . ' — Khata')
@section('breadcrumb', 'Party Khata / ' . $party->name)

@section('header_actions')
    <div class="flex items-center gap-2 no-print">
        @php
            $waSummary = urlencode("*BK PACKAGES - KHATA STATEMENT*\nParty: {$party->name}\nClosing Balance: Rs. " . number_format(abs($party->current_balance)) . ($party->current_balance >= 0 ? ' (Receivable)' : ' (Payable)') . "\nTotal Debits: Rs. " . number_format($totalDebit) . "\nTotal Credits: Rs. " . number_format($totalCredit) . "\n\nSent by Mirza Sohail (BK Packages Okara)");
        @endphp
        <a href="{{ route('documents.sales_invoice', ['party_name' => $party->name, 'party_address' => $party->address, 'party_phone' => $party->phone]) }}" target="_blank"
           class="px-3.5 py-2 rounded-lg bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 inline-flex items-center gap-1.5 shadow-xs">
            <span class="material-symbols-outlined text-[16px]">receipt</span>
            Print Bill / Receipt
        </a>
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
                        <th class="py-3.5 px-5 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($entries as $entry)
                        <tr class="hover:bg-slate-50/70">
                            <td class="py-4 px-5 text-slate-600 num-tabular whitespace-nowrap">{{ $entry->entry_date->format('d M Y') }}</td>
                            <td class="py-4 px-5 font-semibold text-blue-600 whitespace-nowrap">
                                @if($entry->voucher_id)
                                    <a href="{{ route('documents.sales_invoice', $entry->voucher_id) }}" target="_blank" class="hover:underline inline-flex items-center gap-1">
                                        <span>{{ $entry->voucher->voucher_number }}</span>
                                        <span class="material-symbols-outlined text-[13px]">open_in_new</span>
                                    </a>
                                @else
                                    <span>{{ $entry->voucher->voucher_number ?? 'OPENING' }}</span>
                                @endif
                            </td>
                            <td class="py-4 px-5 text-slate-800">
                                <div class="flex items-center justify-between gap-2">
                                    <span>{{ $entry->narration }}</span>
                                    @if(!empty($entry->voucher?->receipt_photo_path))
                                        <a href="{{ asset($entry->voucher->receipt_photo_path) }}" target="_blank"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 text-[10px] font-semibold whitespace-nowrap shadow-xs"
                                            title="View Receipt Photo">
                                            <span class="material-symbols-outlined text-[13px]">image</span>
                                            <span>Slip</span>
                                        </a>
                                    @endif
                                </div>
                            </td>
                            <td class="py-4 px-5 text-right num-tabular font-medium text-slate-900">
                                {{ $entry->debit > 0 ? 'Rs. ' . number_format($entry->debit) : '-' }}
                            </td>
                            <td class="py-4 px-5 text-right num-tabular font-medium text-emerald-600">
                                {{ $entry->credit > 0 ? 'Rs. ' . number_format($entry->credit) : '-' }}
                            </td>
                            <td class="py-4 px-5 text-right num-tabular font-bold text-slate-900 whitespace-nowrap">
                                Rs. {{ number_format(abs($entry->running_balance)) }} {{ $entry->running_balance >= 0 ? 'Dr' : 'Cr' }}
                            </td>
                            <td class="py-4 px-5 text-center whitespace-nowrap">
                                @if($entry->voucher && !auth()->user()?->isReadOnly())
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" onclick="openEditVoucherModal({{ json_encode($entry->voucher) }})"
                                            class="p-1.5 rounded-lg border border-slate-300 bg-white text-slate-600 hover:text-blue-600 hover:bg-slate-50 transition-colors shadow-xs" title="Edit Voucher">
                                            <span class="material-symbols-outlined text-[15px]">edit</span>
                                        </button>
                                        <form action="{{ route('khata.voucher.destroy', $entry->voucher->id) }}" method="POST" onsubmit="return confirm('Delete voucher {{ $entry->voucher->voucher_number }} (Rs. {{ number_format($entry->voucher->total_amount) }})? This will safely reverse the party balance.');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg border border-slate-300 bg-white text-slate-600 hover:text-red-600 hover:bg-slate-50 transition-colors shadow-xs" title="Delete Voucher">
                                                <span class="material-symbols-outlined text-[15px]">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">No ledger transactions recorded for this party yet.</td>
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
            <form action="{{ route('khata.voucher.store') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
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

                <!-- Option C: In-Form Media Photo Capture (Fail-safe single file input) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cheque / Bank Slip / Bill Photo (Optional)</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center justify-center gap-1.5 px-3 py-2 border-2 border-dashed border-blue-200 bg-blue-50/40 hover:bg-blue-50 rounded-xl cursor-pointer text-xs font-semibold text-blue-700 transition-colors">
                            <span class="material-symbols-outlined text-base">photo_camera</span>
                            <span>Camera Snap</span>
                            <input type="file" id="party_voucher_file" name="receipt_photo" accept="image/*" capture="environment" class="hidden" onchange="previewShowPhoto(this)" />
                        </label>
                        <button type="button" onclick="const fi = document.getElementById('party_voucher_file'); fi.removeAttribute('capture'); fi.click();" class="flex items-center justify-center gap-1.5 px-3 py-2 border border-slate-200 bg-slate-50 hover:bg-slate-100 rounded-xl text-xs font-medium text-slate-700 transition-colors">
                            <span class="material-symbols-outlined text-base">upload_file</span>
                            <span>Upload File</span>
                        </button>
                    </div>
                    <div id="show-preview-box" class="hidden mt-2 text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-2 items-center justify-between">
                        <div class="flex items-center gap-1.5 truncate">
                            <span class="material-symbols-outlined text-sm text-emerald-600">check_circle</span>
                            <span id="show-preview-filename" class="font-medium truncate"></span>
                        </div>
                        <button type="button" onclick="clearShowPhoto()" class="text-red-500 hover:text-red-700 text-sm font-bold ml-2">&times;</button>
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-party-voucher')" class="px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-sm font-medium">Post to Khata</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Voucher / Transaction -->
    <div id="modal-edit-voucher" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-2xl my-8">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-600">edit_note</span>
                        <span>Edit Voucher (<span id="edit-voucher-num-label"></span>)</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Correct amount, type, description, or attach new slip</p>
                </div>
                <button onclick="closeModal('modal-edit-voucher')" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="edit-voucher-form" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="party_id" value="{{ $party->id }}" />
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Transaction Type</label>
                    <select name="voucher_type" id="edit-voucher-type" class="w-full rounded-lg border-slate-300 text-sm font-semibold">
                        <option value="RECEIPT_VOUCHER">Payment Received (Cash / Cheque)</option>
                        <option value="SALES_INVOICE">Manual Sales Bill / Invoice (Debit)</option>
                        <option value="PAYMENT_VOUCHER">Payment Made to Supplier (Debit)</option>
                        <option value="PURCHASE_BILL">Paper / Material Purchase Bill (Credit)</option>
                        <option value="EXPENSE_VOUCHER">General Expense</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Amount (Rs.)</label>
                        <input type="number" name="total_amount" id="edit-voucher-amount" min="1" step="0.01" required class="w-full rounded-lg border-slate-300 text-sm font-bold" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Mode</label>
                        <select name="payment_mode" id="edit-voucher-mode" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="CASH">Cash</option>
                            <option value="CHEQUE">Cheque</option>
                            <option value="ONLINE_BANK">Online Bank</option>
                            <option value="CREDIT">Credit Bill</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Voucher Date</label>
                        <input type="date" name="voucher_date" id="edit-voucher-date" class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Reference / Cheque #</label>
                        <input type="text" name="reference_number" id="edit-voucher-ref" class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Narration / Description</label>
                    <input type="text" name="description" id="edit-voucher-desc" required class="w-full rounded-lg border-slate-300 text-sm" />
                </div>

                <!-- Replace / Attach Photo -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Replace / Attach Slip Photo (Optional)</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center justify-center gap-1.5 px-3 py-2 border-2 border-dashed border-blue-200 bg-blue-50/40 hover:bg-blue-50 rounded-xl cursor-pointer text-xs font-semibold text-blue-700 transition-colors">
                            <span class="material-symbols-outlined text-base">photo_camera</span>
                            <span>Camera Snap</span>
                            <input type="file" id="edit_voucher_file" name="receipt_photo" accept="image/*" capture="environment" class="hidden" onchange="previewEditVoucherPhoto(this)" />
                        </label>
                        <button type="button" onclick="const fi = document.getElementById('edit_voucher_file'); fi.removeAttribute('capture'); fi.click();" class="flex items-center justify-center gap-1.5 px-3 py-2 border border-slate-200 bg-slate-50 hover:bg-slate-100 rounded-xl text-xs font-medium text-slate-700 transition-colors">
                            <span class="material-symbols-outlined text-base">upload_file</span>
                            <span>Upload File</span>
                        </button>
                    </div>
                    <div id="edit-voucher-preview-box" class="hidden mt-2 text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-2 items-center justify-between">
                        <div class="flex items-center gap-1.5 truncate">
                            <span class="material-symbols-outlined text-sm text-emerald-600">check_circle</span>
                            <span id="edit-voucher-preview-filename" class="font-medium truncate"></span>
                        </div>
                        <button type="button" onclick="clearEditVoucherPhoto()" class="text-red-500 hover:text-red-700 text-sm font-bold ml-2">&times;</button>
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-edit-voucher')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function previewShowPhoto(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const box = document.getElementById('show-preview-box');
        const filename = document.getElementById('show-preview-filename');
        if (box && filename) {
            filename.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
            box.classList.remove('hidden');
            box.classList.add('flex');
        }
    }
}

function clearShowPhoto() {
    const box = document.getElementById('show-preview-box');
    if (box) {
        box.classList.add('hidden');
        box.classList.remove('flex');
        const form = box.closest('form');
        if (form) {
            const inputs = form.querySelectorAll('input[type="file"]');
            inputs.forEach(i => i.value = '');
        }
    }
}

function openEditVoucherModal(v) {
    document.getElementById('edit-voucher-form').action = '/khata/voucher/' + v.id;
    document.getElementById('edit-voucher-num-label').innerText = v.voucher_number || '';
    document.getElementById('edit-voucher-type').value = v.voucher_type || 'RECEIPT_VOUCHER';
    document.getElementById('edit-voucher-amount').value = v.total_amount || '';
    document.getElementById('edit-voucher-mode').value = v.payment_mode || 'CASH';
    document.getElementById('edit-voucher-date').value = v.voucher_date ? v.voucher_date.substring(0, 10) : '';
    document.getElementById('edit-voucher-ref').value = v.reference_number || '';
    document.getElementById('edit-voucher-desc').value = v.description || '';
    openModal('modal-edit-voucher');
}

function previewEditVoucherPhoto(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const box = document.getElementById('edit-voucher-preview-box');
        const filename = document.getElementById('edit-voucher-preview-filename');
        if (box && filename) {
            filename.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
            box.classList.remove('hidden');
            box.classList.add('flex');
        }
    }
}

function clearEditVoucherPhoto() {
    const box = document.getElementById('edit-voucher-preview-box');
    if (box) {
        box.classList.add('hidden');
        box.classList.remove('flex');
        const fi = document.getElementById('edit_voucher_file');
        if (fi) fi.value = '';
    }
}
</script>
@endpush

