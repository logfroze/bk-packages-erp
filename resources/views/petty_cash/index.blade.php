@extends('layouts.app')

@section('title', 'Petty Cash & Daily Safe Management')

@section('header_actions')
    <a href="{{ route('petty_cash.print') }}" target="_blank"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50 shadow-xs transition-colors">
        <span class="material-symbols-outlined text-base">print</span>
        <span>Daily Sheet</span>
    </a>
    @if(auth()->user()?->canAddExpenses())
        <button onclick="openModal('modal-cash-in')"
            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 shadow-xs transition-colors">
            <span class="material-symbols-outlined text-base">add_circle</span>
            <span>Cash In / Counter Sale</span>
        </button>
        <button onclick="openModal('modal-cash-out')"
            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700 shadow-xs transition-colors">
            <span class="material-symbols-outlined text-base">remove_circle</span>
            <span>Record Expense (Kharch)</span>
        </button>
    @endif
@endsection

@section('content')
<div class="space-y-6">

    <!-- KPI Balance Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Live Drawer Safe Balance -->
        <div class="bg-navy-900 rounded-2xl p-5 text-white shadow-sm flex flex-col justify-between border border-navy-800">
            <div>
                <div class="flex items-center justify-between opacity-80">
                    <span class="text-xs font-medium uppercase tracking-wider">Current Safe Balance</span>
                    <span class="material-symbols-outlined text-emerald-400">lock</span>
                </div>
                <div class="text-3xl font-extrabold tracking-tight mt-2 num-tabular">
                    Rs. {{ number_format($currentBalance, 2) }}
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-navy-800/80 flex items-center justify-between text-[11px] opacity-75">
                <span>Real-time Cash in Drawer</span>
                <span class="font-bold text-emerald-400">Live</span>
            </div>
        </div>

        <!-- Today's Inflow -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-slate-500">
                    <span class="text-xs font-medium uppercase tracking-wider">Today's Inflow</span>
                    <span class="material-symbols-outlined text-emerald-600">arrow_downward</span>
                </div>
                <div class="text-2xl font-bold text-emerald-600 mt-2 num-tabular">
                    + Rs. {{ number_format($todayIn, 2) }}
                </div>
            </div>
            <div class="mt-3 text-[11px] text-slate-400">
                Opening floats & counter cash sales
            </div>
        </div>

        <!-- Today's Outflow (Expenses) -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-slate-500">
                    <span class="text-xs font-medium uppercase tracking-wider">Today's Expenses</span>
                    <span class="material-symbols-outlined text-red-600">arrow_upward</span>
                </div>
                <div class="text-2xl font-bold text-red-600 mt-2 num-tabular">
                    - Rs. {{ number_format($todayOut, 2) }}
                </div>
            </div>
            <div class="mt-3 text-[11px] text-slate-400">
                Daily shop, tea, petrol & minor purchases
            </div>
        </div>

        <!-- Today's Net Position -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-slate-500">
                    <span class="text-xs font-medium uppercase tracking-wider">Today's Net Movement</span>
                    <span class="material-symbols-outlined text-blue-600">swap_vert</span>
                </div>
                @php $todayNet = $todayIn - $todayOut; @endphp
                <div class="text-2xl font-bold {{ $todayNet >= 0 ? 'text-emerald-600' : 'text-red-600' }} mt-2 num-tabular">
                    {{ $todayNet >= 0 ? '+ ' : '- ' }}Rs. {{ number_format(abs($todayNet), 2) }}
                </div>
            </div>
            <div class="mt-3 text-[11px] text-slate-400">
                Calculated for {{ today()->format('d M Y') }}
            </div>
        </div>
    </div>

    <!-- Filter Pills Bar -->
    <div class="bg-white rounded-xl border border-slate-200 p-2.5 flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-1.5 overflow-x-auto">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider px-2">Filter:</span>
            <a href="{{ route('petty_cash.index', ['type' => 'ALL']) }}"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ $type === 'ALL' ? 'bg-navy-900 text-white font-semibold' : 'text-slate-600 hover:text-navy-900' }}">
                All Transactions
            </a>
            <a href="{{ route('petty_cash.index', ['type' => 'CASH_OUT']) }}"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ $type === 'CASH_OUT' ? 'bg-red-600 text-white font-semibold' : 'text-slate-600 hover:text-red-600' }}">
                Expenses Only (Kharch)
            </a>
            <a href="{{ route('petty_cash.index', ['type' => 'CASH_IN']) }}"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ $type === 'CASH_IN' ? 'bg-emerald-600 text-white font-semibold' : 'text-slate-600 hover:text-emerald-600' }}">
                Cash Deposits / Float
            </a>
            <a href="{{ route('petty_cash.index', ['type' => 'COUNTER_SALE']) }}"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ $type === 'COUNTER_SALE' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-600 hover:text-blue-600' }}">
                Counter Spot Sales
            </a>
        </div>
        <div class="text-xs text-slate-500 font-medium hidden sm:block">
            {{ $transactions->total() }} Total Entries
        </div>
    </div>

    <!-- Transactions Ledger Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Cash Register & Safe Ledger</h2>
                <p class="text-xs text-slate-500 mt-0.5">Chronological record of petty cash receipts, factory expenses, and physical slips</p>
            </div>
            <span class="text-xs text-slate-500">{{ $transactions->total() }} total entries</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-700 font-semibold border-b-2 border-slate-300">
                    <tr>
                        <th class="py-3 px-4">Date & Time</th>
                        <th class="py-3 px-4">Type</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4">Description</th>
                        <th class="py-3 px-3 text-center">Slip / Photo</th>
                        <th class="py-3 px-4">Person</th>
                        <th class="py-3 px-4 text-right">Inflow</th>
                        <th class="py-3 px-4 text-right">Outflow (Kharch)</th>
                        <th class="py-3 px-4 text-right">Running Balance</th>
                        <th class="py-3 px-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transactions as $txn)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3 px-4 text-slate-500 whitespace-nowrap">
                                <span class="font-semibold text-slate-800">{{ $txn->transaction_date ? $txn->transaction_date->format('d M Y') : $txn->created_at->format('d M Y') }}</span>
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
                            <td class="py-3 px-3 text-center whitespace-nowrap">
                                @if(!empty($txn->receipt_photo_path))
                                    @php
                                        $photoUrl = str_starts_with($txn->receipt_photo_path, 'data:') ? $txn->receipt_photo_path : asset($txn->receipt_photo_path);
                                    @endphp
                                    <button type="button" onclick="viewPettyImage('{{ $photoUrl }}', '{{ addslashes($txn->description) }}', 'Rs. {{ number_format($txn->amount) }}', '{{ $categories[$txn->category] ?? $txn->category }}')"
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 text-[11px] font-semibold transition-colors shadow-xs">
                                        <span class="material-symbols-outlined text-xs">receipt_long</span>
                                        <span>Slip</span>
                                    </button>
                                @else
                                    <span class="text-slate-300 text-xs">—</span>
                                @endif
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
                                Rs. {{ number_format($txn->running_cash_balance, 2) }}
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <!-- Edit Button (✏️) -->
                                    <button type="button" onclick="openEditPettyModal({{ json_encode($txn) }})"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors" title="Edit Transaction">
                                        <span class="material-symbols-outlined text-[17px]">edit</span>
                                    </button>

                                    <!-- Delete Button (🗑️) -->
                                    <form action="{{ route('petty_cash.destroy', $txn->id) }}" method="POST" onsubmit="return confirm('Delete this petty cash entry?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Delete Entry">
                                            <span class="material-symbols-outlined text-[17px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-12 text-center text-slate-400">
                                <span class="material-symbols-outlined text-4xl block mb-2 text-slate-300">receipt_long</span>
                                No petty cash transactions recorded yet. Click above to record cash in or daily expense.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@section('modals')
<!-- Modal 1: Record Expense (Kharch) -->
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

        <form action="{{ route('petty_cash.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 mt-4">
            @csrf
            <input type="hidden" name="transaction_type" value="CASH_OUT" />

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Expense Category *</label>
                    <select name="category" required class="w-full rounded-lg border-slate-300 text-xs py-2 focus:border-blue-600 focus:ring-blue-600">
                        <option value="TEA_REFRESHMENT">Tea & Refreshments (Chai/Khana)</option>
                        <option value="PETROL_TRANSPORT">Petrol & Fuel (Gaadi/Generator)</option>
                        <option value="HARDWARE_PURCHASE">Hardware & Small Tools (Nuts/Bolts/Tools)</option>
                        <option value="SPARE_PARTS">Machine Spare Parts (Chhotay Parts)</option>
                        <option value="OFFICE_SUPPLIES">Office Supplies & Stationery</option>
                        <option value="MISCELLANEOUS">Miscellaneous Factory Expense</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Expense Date *</label>
                    <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" required
                        class="w-full rounded-lg border-slate-300 text-xs py-2" />
                </div>
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

            <!-- Single Safe File Upload with Dual Action Buttons -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Receipt / Bill / Grocery Photo (Optional)</label>
                <input type="file" id="cash-out-receipt-file" name="receipt_photo" accept="image/*,.pdf" class="hidden"
                    onchange="previewPettyPhoto(this, 'cash-out-preview')" />
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" onclick="triggerFileWithCapture('cash-out-receipt-file', true)"
                        class="flex items-center justify-center gap-1.5 px-3 py-2 border-2 border-dashed border-red-200 bg-red-50/40 hover:bg-red-50 rounded-xl cursor-pointer text-xs font-semibold text-red-700 transition-colors">
                        <span class="material-symbols-outlined text-base">photo_camera</span>
                        <span>Camera Snap</span>
                    </button>
                    <button type="button" onclick="triggerFileWithCapture('cash-out-receipt-file', false)"
                        class="flex items-center justify-center gap-1.5 px-3 py-2 border border-slate-200 bg-slate-50 hover:bg-slate-100 rounded-xl cursor-pointer text-xs font-medium text-slate-700 transition-colors">
                        <span class="material-symbols-outlined text-base">upload_file</span>
                        <span>Upload File</span>
                    </button>
                </div>
                <div id="cash-out-preview" class="hidden mt-2 text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-2 flex items-center justify-between">
                    <div class="flex items-center gap-1.5 truncate">
                        <span class="material-symbols-outlined text-sm text-emerald-600">check_circle</span>
                        <span class="preview-filename font-medium truncate"></span>
                    </div>
                    <button type="button" onclick="clearPettyPhoto('cash-out-preview', 'cash-out-receipt-file')" class="text-red-500 hover:text-red-700 text-sm font-bold ml-2">&times;</button>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Snaps directly on mobile camera. Auto-mirrored into the Media Vault.</p>
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

<!-- Modal 2: Record Inflow / Counter Sale -->
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

        <form action="{{ route('petty_cash.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 mt-4">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Transaction Nature *</label>
                    <select name="transaction_type" required class="w-full rounded-lg border-slate-300 text-xs py-2 focus:border-blue-600 focus:ring-blue-600">
                        <option value="CASH_IN">Cash Deposit / Opening Safe Float</option>
                        <option value="COUNTER_SALE">Minor Spot Cash Sale (Chemical / Rolls / Waste)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Date *</label>
                    <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" required
                        class="w-full rounded-lg border-slate-300 text-xs py-2" />
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Category *</label>
                <select name="category" required class="w-full rounded-lg border-slate-300 text-xs py-2 focus:border-blue-600 focus:ring-blue-600">
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

            <!-- Single Safe File Upload with Dual Action Buttons -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Receipt / Bank Slip / Photo (Optional)</label>
                <input type="file" id="cash-in-receipt-file" name="receipt_photo" accept="image/*,.pdf" class="hidden"
                    onchange="previewPettyPhoto(this, 'cash-in-preview')" />
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" onclick="triggerFileWithCapture('cash-in-receipt-file', true)"
                        class="flex items-center justify-center gap-1.5 px-3 py-2 border-2 border-dashed border-emerald-200 bg-emerald-50/40 hover:bg-emerald-50 rounded-xl cursor-pointer text-xs font-semibold text-emerald-700 transition-colors">
                        <span class="material-symbols-outlined text-base">photo_camera</span>
                        <span>Camera Snap</span>
                    </button>
                    <button type="button" onclick="triggerFileWithCapture('cash-in-receipt-file', false)"
                        class="flex items-center justify-center gap-1.5 px-3 py-2 border border-slate-200 bg-slate-50 hover:bg-slate-100 rounded-xl cursor-pointer text-xs font-medium text-slate-700 transition-colors">
                        <span class="material-symbols-outlined text-base">upload_file</span>
                        <span>Upload File</span>
                    </button>
                </div>
                <div id="cash-in-preview" class="hidden mt-2 text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-2 flex items-center justify-between">
                    <div class="flex items-center gap-1.5 truncate">
                        <span class="material-symbols-outlined text-sm text-emerald-600">check_circle</span>
                        <span class="preview-filename font-medium truncate"></span>
                    </div>
                    <button type="button" onclick="clearPettyPhoto('cash-in-preview', 'cash-in-receipt-file')" class="text-red-500 hover:text-red-700 text-sm font-bold ml-2">&times;</button>
                </div>
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

<!-- Modal 3: Edit Petty Cash Entry (Full Customization & Correction) -->
<div id="modal-edit-petty" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">edit</span>
                </div>
                <h3 class="text-base font-bold text-slate-900">Edit Petty Cash Transaction</h3>
            </div>
            <button onclick="closeModal('modal-edit-petty')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="form-edit-petty" method="POST" enctype="multipart/form-data" class="space-y-4 mt-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Category *</label>
                    <select id="edit-petty-category" name="category" required class="w-full rounded-lg border-slate-300 text-xs py-2 focus:border-blue-600 focus:ring-blue-600">
                        @foreach($categories as $cKey => $cLabel)
                            <option value="{{ $cKey }}">{{ $cLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Date *</label>
                    <input type="date" id="edit-petty-date" name="transaction_date" required
                        class="w-full rounded-lg border-slate-300 text-xs py-2" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Amount (Rs.) *</label>
                    <input type="number" step="0.01" min="1" id="edit-petty-amount" name="amount" required
                        class="w-full rounded-lg border-slate-300 text-sm font-bold focus:border-blue-600 focus:ring-blue-600 font-mono" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Person (Paid / Received)</label>
                    <input type="text" id="edit-petty-person" name="spent_by_or_paid_to"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Description / Tafseel *</label>
                <input type="text" id="edit-petty-description" name="description" required
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Attach New Slip / Photo (Optional)</label>
                <input type="file" id="edit-petty-receipt-file" name="receipt_photo" accept="image/*,.pdf" class="hidden"
                    onchange="previewPettyPhoto(this, 'edit-petty-preview')" />
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" onclick="triggerFileWithCapture('edit-petty-receipt-file', true)"
                        class="flex items-center justify-center gap-1.5 px-3 py-2 border-2 border-dashed border-blue-200 bg-blue-50/40 hover:bg-blue-50 rounded-xl cursor-pointer text-xs font-semibold text-blue-700 transition-colors">
                        <span class="material-symbols-outlined text-base">photo_camera</span>
                        <span>Camera Snap</span>
                    </button>
                    <button type="button" onclick="triggerFileWithCapture('edit-petty-receipt-file', false)"
                        class="flex items-center justify-center gap-1.5 px-3 py-2 border border-slate-200 bg-slate-50 hover:bg-slate-100 rounded-xl cursor-pointer text-xs font-medium text-slate-700 transition-colors">
                        <span class="material-symbols-outlined text-base">upload_file</span>
                        <span>Upload File</span>
                    </button>
                </div>
                <div id="edit-petty-preview" class="hidden mt-2 text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-2 flex items-center justify-between">
                    <div class="flex items-center gap-1.5 truncate">
                        <span class="material-symbols-outlined text-sm text-emerald-600">check_circle</span>
                        <span class="preview-filename font-medium truncate"></span>
                    </div>
                    <button type="button" onclick="clearPettyPhoto('edit-petty-preview', 'edit-petty-receipt-file')" class="text-red-500 hover:text-red-700 text-sm font-bold ml-2">&times;</button>
                </div>
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="closeModal('modal-edit-petty')"
                    class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Cancel</button>
                <button type="submit"
                    class="btn-solid-primary px-5 py-2 rounded-lg text-xs font-semibold shadow-xs">
                    Update Entry
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 4: Fullscreen Slip / Receipt Viewer -->
<div id="modal-petty-viewer" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-2xl w-full p-5 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                </div>
                <div>
                    <h3 id="petty-modal-title" class="text-sm font-bold text-slate-900 truncate max-w-md">Physical Expense Receipt</h3>
                    <p id="petty-modal-sub" class="text-[11px] text-slate-400"></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a id="petty-modal-download" href="#" target="_blank" download
                    class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-slate-100" title="Open full size / Download">
                    <span class="material-symbols-outlined text-[18px]">open_in_new</span>
                </a>
                <button type="button" onclick="closeModal('modal-petty-viewer')" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>
        </div>

        <div class="py-4 flex-1 overflow-auto flex items-center justify-center bg-slate-950/5 rounded-xl my-2">
            <img id="petty-modal-img" src="" alt="Receipt Photo" class="max-h-[60vh] w-auto max-w-full rounded-lg shadow-sm object-contain" />
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <a href="{{ route('media_vault.index') }}" class="text-blue-600 hover:underline inline-flex items-center gap-1 font-medium">
                <span class="material-symbols-outlined text-xs">folder_open</span>
                <span>Open in Central Media Vault</span>
            </a>
            <button type="button" onclick="closeModal('modal-petty-viewer')" class="px-4 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Close</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function triggerFileWithCapture(inputId, isCamera) {
        const inp = document.getElementById(inputId);
        if (!inp) return;
        if (isCamera) {
            inp.setAttribute('capture', 'environment');
        } else {
            inp.removeAttribute('capture');
        }
        inp.click();
    }

    function previewPettyPhoto(input, previewId) {
        const preview = document.getElementById(previewId);
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const sizeKB = Math.round(file.size / 1024);
            const nameSpan = preview.querySelector('.preview-filename');
            if (nameSpan) {
                nameSpan.textContent = `${file.name} (${sizeKB} KB)`;
            }
            preview.classList.remove('hidden');
        }
    }

    function clearPettyPhoto(previewId, inputId) {
        const preview = document.getElementById(previewId);
        if (preview) {
            preview.classList.add('hidden');
        }
        if (inputId) {
            const input = document.getElementById(inputId);
            if (input) input.value = '';
        }
    }

    function viewPettyImage(url, desc, amount, category) {
        document.getElementById('petty-modal-img').src = url;
        document.getElementById('petty-modal-title').textContent = desc || 'Expense Slip';
        document.getElementById('petty-modal-sub').textContent = `${category} • ${amount}`;
        document.getElementById('petty-modal-download').href = url;
        openModal('modal-petty-viewer');
    }

    function openEditPettyModal(txn) {
        document.getElementById('form-edit-petty').action = '/petty-cash/' + txn.id;
        document.getElementById('edit-petty-category').value = txn.category || 'MISCELLANEOUS';
        document.getElementById('edit-petty-amount').value = parseFloat(txn.amount || 0).toFixed(2);
        document.getElementById('edit-petty-person').value = txn.spent_by_or_paid_to || '';
        document.getElementById('edit-petty-description').value = txn.description || '';

        let tDate = '';
        if (txn.transaction_date) {
            tDate = txn.transaction_date.substring(0, 10);
        } else if (txn.created_at) {
            tDate = txn.created_at.substring(0, 10);
        }
        document.getElementById('edit-petty-date').value = tDate || new Date().toISOString().substring(0, 10);

        clearPettyPhoto('edit-petty-preview', 'edit-petty-receipt-file');
        openModal('modal-edit-petty');
    }
</script>
@endpush
