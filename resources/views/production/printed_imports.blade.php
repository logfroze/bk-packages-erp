@extends('layouts.app')

@section('title', 'Printed Imports — Outsourced Pre-Printed Sheets')
@section('breadcrumb', 'Printed Imports')

@section('header_actions')
    @if(!auth()->user()?->isReadOnly())
        <div class="flex items-center gap-2 flex-wrap">
            <button onclick="openModal('modal-custom-card')" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50 shadow-xs flex items-center gap-1.5 transition-colors">
                <span class="material-symbols-outlined text-[16px] text-blue-600">table_chart</span>
                + Custom Table / Card
            </button>
            <button onclick="openModal('modal-add-sheet')" class="px-3.5 py-1.5 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs flex items-center gap-1.5 transition-colors">
                <span class="material-symbols-outlined text-[16px]">add_circle</span>
                + Add Printed Import (Pcs)
            </button>
        </div>
    @endif
@endsection

@section('content')
<div class="space-y-6">

    <!-- Header & Sourcing City Pills -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider bg-purple-100 text-purple-800 border border-purple-200">
                        Outsourced Material
                    </span>
                    <span class="text-xs text-slate-500">Multan · Faisalabad · Lahore · Rahim Yar Khan · Jumber</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 mt-2">Custom Pre-Printed Top Sheets</h1>
                <p class="text-xs text-slate-500 mt-1 max-w-2xl">
                    Manage outsourced printed top sheets by buyer design and order quantity. All stock is tracked strictly in <strong class="text-slate-800">Pieces / Count</strong>.
                </p>
            </div>

            <!-- Sourcing Hub Filter Pills -->
            <div class="flex items-center gap-1.5 flex-wrap">
                <a href="{{ route('production.printed_imports') }}"
                    class="px-3 py-1 rounded-lg text-xs font-semibold transition-colors {{ !$cityFilter || $cityFilter === 'ALL' ? 'bg-navy-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    All Hubs
                </a>
                @foreach($sourcingCities as $city)
                    <a href="{{ route('production.printed_imports', ['city' => $city]) }}"
                        class="px-3 py-1 rounded-lg text-xs font-semibold transition-colors {{ $cityFilter === $city ? 'bg-navy-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        {{ $city }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Stock</span>
                <span class="w-8 h-8 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs">PCS</span>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2">{{ number_format($totalPiecesStock) }} <span class="text-xs text-slate-500 font-normal">pcs</span></div>
            <div class="text-[11px] text-slate-500 mt-1">Pre-printed sheets ready for pasting</div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Inventory Value</span>
                <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xs">PKR</span>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2">Rs. {{ number_format($totalValuation) }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Total valuation at landed unit cost</div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Low Stock Alerts</span>
                <span class="w-8 h-8 rounded-lg {{ $lowStockCount > 0 ? 'bg-red-100 text-red-800 animate-pulse' : 'bg-slate-100 text-slate-500' }} flex items-center justify-center font-bold text-xs">!</span>
            </div>
            <div class="text-2xl font-bold {{ $lowStockCount > 0 ? 'text-red-600' : 'text-slate-900' }} mt-2">{{ $lowStockCount }} Designs</div>
            <div class="text-[11px] text-slate-500 mt-1">Below minimum threshold limit</div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Sourcing Cities</span>
                <span class="w-8 h-8 rounded-lg bg-purple-100 text-purple-800 flex items-center justify-center font-bold text-xs">HUB</span>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2">{{ $uniqueCitiesCount }} Active</div>
            <div class="text-[11px] text-slate-500 mt-1">Suppliers from 5 major industrial hubs</div>
        </div>
    </div>

    <!-- Custom User-Defined Cards Section (Client Dynamic Customization) -->
    @if($customCards->isNotEmpty())
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-600 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-blue-600">dashboard_customize</span>
                    Custom Configured Cards & Grids ({{ $customCards->count() }})
                </h2>
                <span class="text-xs text-slate-400">Customized by User</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($customCards as $card)
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <h3 class="font-bold text-slate-900 text-sm">{{ $card->title }}</h3>
                                <div class="flex items-center gap-1.5">
                                    @if($card->badge_text)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            {{ $card->badge_text }}
                                        </span>
                                    @endif
                                    <form action="{{ route('custom_cards.destroy', $card->id) }}" method="POST" onsubmit="return confirm('Delete this custom card?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-slate-400 hover:text-red-600 p-1 rounded transition-colors" title="Delete Card">
                                            <span class="material-symbols-outlined text-[16px]">close</span>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            @if($card->subtitle)
                                <p class="text-xs text-slate-500 mb-3">{{ $card->subtitle }}</p>
                            @endif

                            @if($card->card_type === 'DYNAMIC_GRID' && is_array($card->grid_data))
                                <div class="overflow-x-auto rounded-lg border border-slate-100 my-2">
                                    <table class="w-full text-xs text-left">
                                        @if(is_array($card->column_headers) && count($card->column_headers) > 0)
                                            <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-100">
                                                <tr>
                                                    @foreach($card->column_headers as $hdr)
                                                        <th class="px-2.5 py-1.5">{{ $hdr }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                        @endif
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($card->grid_data as $row)
                                                <tr>
                                                    @foreach((array)$row as $cell)
                                                        <td class="px-2.5 py-1.5 text-slate-700">{{ $cell }}</td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @elseif($card->card_type === 'KEY_VALUE' && is_array($card->field_pairs))
                                <div class="space-y-1.5 my-2">
                                    @foreach($card->field_pairs as $field)
                                        <div class="flex items-center justify-between text-xs py-1 border-b border-slate-50">
                                            <span class="text-slate-500 font-medium">{{ $field['label'] ?? '' }}:</span>
                                            <span class="text-slate-900 font-semibold">{{ $field['value'] ?? '' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="text-[10px] text-slate-400 pt-2 border-t border-slate-100 mt-2 flex justify-between">
                            <span>Grid: {{ $card->rows_count }}R × {{ $card->columns_count }}C</span>
                            <span>{{ $card->created_at->format('d M h:i A') }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Printed Top Sheets Ledger Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-slate-900">Pre-Printed Top Sheet Designs</h2>
                <p class="text-xs text-slate-500 mt-0.5">Stock balance, unit landed price, and warehouse locations</p>
            </div>
            <div class="text-xs font-semibold text-slate-600 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200">
                Total Designs: {{ $topSheets->total() }}
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 text-slate-600 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Item Code</th>
                        <th class="px-4 py-3">Design Title / Buyer</th>
                        <th class="px-4 py-3">Sourcing Hub</th>
                        <th class="px-4 py-3 text-right">Stock (Pieces)</th>
                        <th class="px-4 py-3 text-right">Min Threshold</th>
                        <th class="px-4 py-3 text-right">Unit Landed Cost</th>
                        <th class="px-4 py-3">Location</th>
                        <th class="px-4 py-3">Custom Fields</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($topSheets as $sheet)
                        <tr class="hover:bg-slate-50/80 transition-colors {{ $sheet->stock_pieces <= $sheet->minimum_threshold ? 'bg-red-50/30' : '' }}">
                            <td class="px-4 py-3 font-mono font-bold text-blue-600">{{ $sheet->item_code }}</td>
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-900">{{ $sheet->item_name }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    Buyer: <strong class="text-slate-600">{{ $sheet->cartonProduct?->client?->name ?? 'Custom Buyer' }}</strong>
                                    @if($sheet->cartonProduct)
                                        · For: {{ $sheet->cartonProduct->box_title }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                    {{ $sheet->sourcing_city }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="font-bold text-sm {{ $sheet->stock_pieces <= $sheet->minimum_threshold ? 'text-red-600' : 'text-slate-900' }}">
                                    {{ number_format($sheet->stock_pieces) }}
                                </span>
                                <span class="text-[10px] text-slate-400 block">pcs</span>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-500 font-mono">{{ number_format($sheet->minimum_threshold) }} pcs</td>
                            <td class="px-4 py-3 text-right font-medium text-slate-800">Rs. {{ number_format($sheet->unit_cost, 2) }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $sheet->location }}</td>
                            <td class="px-4 py-3">
                                @if(is_array($sheet->custom_fields) && count($sheet->custom_fields) > 0)
                                    <span class="inline-flex items-center gap-1 text-[11px] text-blue-700 font-semibold bg-blue-50 px-2 py-0.5 rounded">
                                        <span class="material-symbols-outlined text-[13px]">tune</span>
                                        {{ count($sheet->custom_fields) }} custom fields
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button onclick="openAdjustModal({{ $sheet->id }}, '{{ addslashes($sheet->item_name) }}', {{ $sheet->stock_pieces }})"
                                        class="px-2 py-1 rounded bg-slate-100 text-slate-700 font-semibold text-[11px] hover:bg-slate-200 transition-colors" title="Adjust Quantity">
                                        Adjust
                                    </button>
                                    <form action="{{ route('production.printed_imports.destroy', $sheet->id) }}" method="POST" onsubmit="return confirm('Delete this printed sheet?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 rounded text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Delete">
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-slate-400">
                                <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">layers</span>
                                No pre-printed top sheets recorded. Click <strong class="text-slate-600">+ Add Printed Import (Pcs)</strong> to add sheet designs.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($topSheets->hasPages())
            <div class="px-6 py-3 border-t border-slate-100 bg-slate-50/50">
                {{ $topSheets->links() }}
            </div>
        @endif
    </div>

</div>

<!-- MODAL 1: ADD PRE-PRINTED TOP SHEET -->
<div id="modal-add-sheet" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 my-8">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-slate-900 text-lg">Add Outsourced Pre-Printed Top Sheet</h3>
                <p class="text-xs text-slate-500">Track incoming custom printed sheets in pieces from industrial suppliers</p>
            </div>
            <button onclick="closeModal('modal-add-sheet')" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="{{ route('production.printed_imports.store') }}" method="POST" class="mt-5 space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Item / Batch Code *</label>
                    <input type="text" name="item_code" value="{{ $nextCode }}" required
                        class="w-full rounded-xl border-slate-300 text-xs py-2 font-mono font-bold bg-slate-50" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sheet Design / Brand Name *</label>
                    <input type="text" name="item_name" placeholder="e.g. Master MoltyFoam 4-Color Top Sheet" required
                        class="w-full rounded-xl border-slate-300 text-xs py-2 font-medium" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sourcing Hub City *</label>
                    <select name="sourcing_city" required class="w-full rounded-xl border-slate-300 text-xs py-2 font-medium text-purple-900">
                        @foreach($sourcingCities as $city)
                            <option value="{{ $city }}" {{ $city === 'Faisalabad' ? 'selected' : '' }}>{{ $city }} Hub</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier / Printing Press</label>
                    <select name="supplier_id" class="w-full rounded-xl border-slate-300 text-xs py-2">
                        <option value="">— Select Supplier (Optional) —</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }} ({{ $sup->city ?? 'Supplier' }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Linked Carton Product</label>
                    <select name="carton_product_id" class="w-full rounded-xl border-slate-300 text-xs py-2">
                        <option value="">— Select Associated Box Design (Optional) —</option>
                        @foreach($cartonProducts as $product)
                            <option value="{{ $product->id }}">{{ $product->box_title }} ({{ $product->client->name ?? 'Client' }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Storage Location Bay</label>
                    <input type="text" name="location" value="Top Sheet Bay 1"
                        class="w-full rounded-xl border-slate-300 text-xs py-2" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Stock Quantity (Pieces) *</label>
                    <input type="number" name="stock_pieces" value="2500" min="0" required
                        class="w-full rounded-xl border-slate-300 text-xs py-2 font-bold" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reorder Minimum Threshold *</label>
                    <input type="number" name="minimum_threshold" value="500" min="0" required
                        class="w-full rounded-xl border-slate-300 text-xs py-2" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Unit Landed Cost (PKR) *</label>
                    <input type="number" name="unit_cost" step="0.01" value="12.50" min="0" required
                        class="w-full rounded-xl border-slate-300 text-xs py-2 font-mono" />
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Notes / Instructions</label>
                <textarea name="notes" rows="2" placeholder="e.g. 4-color offset printing, varnished surface, 150 GSM art paper"
                    class="w-full rounded-xl border-slate-300 text-xs py-2"></textarea>
            </div>

            <!-- Dynamic Custom Fields Section (CLIENT CUSTOMIZATION REQUIREMENT) -->
            <div class="border-t border-slate-200 pt-4">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider block">Custom Fields & Specifications</span>
                        <p class="text-[11px] text-slate-400">Add custom attributes (e.g. Sheet Size, GSM, Lamination Type, Die Cutter #)</p>
                    </div>
                    <button type="button" onclick="addCustomFieldRow('sheet-custom-fields-container')" class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 text-xs font-semibold hover:bg-blue-100 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[15px]">add</span>
                        + Add Custom Field
                    </button>
                </div>

                <div id="sheet-custom-fields-container" class="space-y-2 mt-2">
                    <!-- Dynamic fields added via JS -->
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-add-sheet')" class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">save</span>
                    Save Top Sheet
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: QUICK ADJUST STOCK (PIECES) -->
<div id="modal-adjust-stock" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Adjust Stock (Pieces)</h3>
                <p id="adjust-item-name" class="text-xs text-blue-600 font-medium"></p>
            </div>
            <button onclick="closeModal('modal-adjust-stock')" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="form-adjust-stock" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Adjustment Type *</label>
                <select name="adjustment_type" required class="w-full rounded-xl border-slate-300 text-xs py-2">
                    <option value="ADD">Add New Stock (+ Pcs)</option>
                    <option value="SUBTRACT">Consume / Use for Pasting (- Pcs)</option>
                    <option value="SET">Direct Stock Count Audit (= Exact Pcs)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Quantity (Pieces) *</label>
                <input type="number" name="quantity" min="1" required placeholder="e.g. 500"
                    class="w-full rounded-xl border-slate-300 text-xs py-2 font-bold" />
                <span id="adjust-current-stock" class="text-[11px] text-slate-500 mt-1 block"></span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Reason / Reference</label>
                <input type="text" name="reason" placeholder="e.g. Received from Multan transporter"
                    class="w-full rounded-xl border-slate-300 text-xs py-2" />
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-adjust-stock')" class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800">
                    Confirm Adjustment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 3: CREATE CUSTOM CARD / TABLE -->
<div id="modal-custom-card" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 my-8">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-slate-900 text-lg">Create Custom Table / Card</h3>
                <p class="text-xs text-slate-500">Add custom metrics, rows, and columns for top sheet tracking</p>
            </div>
            <button onclick="closeModal('modal-custom-card')" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="{{ route('custom_cards.store') }}" method="POST" class="mt-5 space-y-4">
            @csrf
            <input type="hidden" name="page_key" value="printed_imports" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Card / Table Title *</label>
                    <input type="text" name="title" placeholder="e.g. Faisalabad Supplier Rate Comparison" required
                        class="w-full rounded-xl border-slate-300 text-xs py-2" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Card Layout Type *</label>
                    <select id="custom_card_type" name="card_type" onchange="toggleCustomCardTypeView()" required
                        class="w-full rounded-xl border-slate-300 text-xs py-2">
                        <option value="DYNAMIC_GRID" selected>Dynamic Table (Custom Rows & Columns)</option>
                        <option value="KEY_VALUE">Field Card (Custom Key-Value Attributes)</option>
                        <option value="METRIC">Metric Counter Card</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Subtitle / Purpose</label>
                <input type="text" name="subtitle" placeholder="e.g. Compare quotes, lead times, and freight per thousand sheets"
                    class="w-full rounded-xl border-slate-300 text-xs py-2" />
            </div>

            <!-- Dynamic Table Builder Controls -->
            <div id="grid_builder_section" class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Grid Dimensions</span>
                    <button type="button" onclick="generateCustomGridInputs()" class="px-2.5 py-1 rounded bg-blue-600 text-white text-[11px] font-semibold hover:bg-blue-700">
                        Regenerate Table Cells
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 mb-1">Rows Count (e.g. 3, 5, 12)</label>
                        <input type="number" id="builder_rows" name="rows_count" value="3" min="1" max="25"
                            class="w-full rounded-xl border-slate-300 text-xs py-1.5" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 mb-1">Columns Count (e.g. 4, 5)</label>
                        <input type="number" id="builder_cols" name="columns_count" value="4" min="1" max="10"
                            class="w-full rounded-xl border-slate-300 text-xs py-1.5" />
                    </div>
                </div>

                <div class="pt-2">
                    <span class="text-[11px] font-semibold text-slate-700 block mb-1">Column Header Labels</span>
                    <div id="builder_headers_container" class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <input type="text" name="column_headers[]" value="Supplier Name" class="rounded-lg border-slate-300 text-xs py-1 px-2" />
                        <input type="text" name="column_headers[]" value="City" class="rounded-lg border-slate-300 text-xs py-1 px-2" />
                        <input type="text" name="column_headers[]" value="Rate / 1000 Pcs" class="rounded-lg border-slate-300 text-xs py-1 px-2" />
                        <input type="text" name="column_headers[]" value="Lead Time" class="rounded-lg border-slate-300 text-xs py-1 px-2" />
                    </div>
                </div>

                <div class="pt-2">
                    <span class="text-[11px] font-semibold text-slate-700 block mb-1">Initial Data Cells</span>
                    <div id="builder_cells_container" class="overflow-x-auto max-h-48 border border-slate-200 rounded-lg p-2 bg-white">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-custom-card')" class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 shadow-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">add_box</span>
                    Save Custom Card
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openAdjustModal(id, title, stock) {
        document.getElementById('adjust-item-name').textContent = title;
        document.getElementById('adjust-current-stock').textContent = `Current Stock: ${stock} pieces`;
        document.getElementById('form-adjust-stock').action = `/production/printed-imports/${id}/adjust`;
        openModal('modal-adjust-stock');
    }

    function addCustomFieldRow(containerId) {
        const container = document.getElementById(containerId);
        const idx = Date.now();
        const row = document.createElement('div');
        row.className = 'grid grid-cols-12 gap-2 items-center';
        row.id = `cfield-${idx}`;
        row.innerHTML = `
            <div class="col-span-5">
                <input type="text" name="custom_fields[${idx}][label]" placeholder="Field Label (e.g. GSM / Size)" required
                    class="w-full rounded-lg border-slate-300 text-xs py-1.5 px-2 font-medium" />
            </div>
            <div class="col-span-6">
                <input type="text" name="custom_fields[${idx}][value]" placeholder="Value (e.g. 180 GSM, Gloss Laminated)" required
                    class="w-full rounded-lg border-slate-300 text-xs py-1.5 px-2" />
            </div>
            <div class="col-span-1 text-center">
                <button type="button" onclick="document.getElementById('cfield-${idx}').remove()" class="text-slate-400 hover:text-red-600 p-1" title="Remove">
                    <span class="material-symbols-outlined text-[16px]">delete</span>
                </button>
            </div>
        `;
        container.appendChild(row);
    }

    function generateCustomGridInputs() {
        const rows = parseInt(document.getElementById('builder_rows').value) || 3;
        const cols = parseInt(document.getElementById('builder_cols').value) || 4;

        const hdrContainer = document.getElementById('builder_headers_container');
        hdrContainer.innerHTML = '';
        for (let c = 0; c < cols; c++) {
            const inp = document.createElement('input');
            inp.type = 'text';
            inp.name = 'column_headers[]';
            inp.value = `Col ${c + 1}`;
            inp.className = 'rounded-lg border-slate-300 text-xs py-1 px-2';
            hdrContainer.appendChild(inp);
        }

        const cellsContainer = document.getElementById('builder_cells_container');
        cellsContainer.innerHTML = '';
        const table = document.createElement('table');
        table.className = 'w-full text-xs';
        const tbody = document.createElement('tbody');

        for (let r = 0; r < rows; r++) {
            const tr = document.createElement('tr');
            for (let c = 0; c < cols; c++) {
                const td = document.createElement('td');
                td.className = 'p-1';
                td.innerHTML = `<input type="text" name="grid_data[${r}][${c}]" value="Cell ${r+1},${c+1}" class="w-full rounded border-slate-200 text-xs py-1 px-1.5" />`;
                tr.appendChild(td);
            }
            tbody.appendChild(tr);
        }
        table.appendChild(tbody);
        cellsContainer.appendChild(table);
    }

    function toggleCustomCardTypeView() {
        const type = document.getElementById('custom_card_type').value;
        const gridSection = document.getElementById('grid_builder_section');
        gridSection.style.display = type === 'DYNAMIC_GRID' ? 'block' : 'none';
    }

    document.addEventListener('DOMContentLoaded', () => {
        generateCustomGridInputs();
    });
</script>
@endpush
@endsection
