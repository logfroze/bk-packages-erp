@extends('layouts.app')

@section('title', 'Outsourced Pre-Printed Top Sheets')

@section('header_actions')
    <div class="flex items-center gap-2">
        <button onclick="openModal('modal-add-sheet')" class="btn-solid-primary flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold">
            <span class="material-symbols-outlined text-[17px]">add</span>
            <span>Add Printed Top Sheet</span>
        </button>
        <button onclick="openModal('modal-custom-card')" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 text-xs font-semibold flex items-center gap-1">
            <span class="material-symbols-outlined text-[16px]">dashboard_customize</span>
            <span>+ Custom Card / Grid</span>
        </button>
    </div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <div class="flex items-center justify-between text-slate-500 text-xs font-medium">
                <span>Total Pre-Printed Stock</span>
                <span class="material-symbols-outlined text-purple-600 text-lg">layers</span>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2">{{ number_format($totalPiecesStock) }} <span class="text-xs text-slate-400 font-normal">pcs</span></div>
            <div class="text-[11px] text-slate-500 mt-1">Across all external design batches</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <div class="flex items-center justify-between text-slate-500 text-xs font-medium">
                <span>Total Inventory Value</span>
                <span class="material-symbols-outlined text-emerald-600 text-lg">payments</span>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2">Rs. {{ number_format($totalValuation) }}</div>
            <div class="text-[11px] text-emerald-600 font-medium mt-1">Landed piece valuation</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <div class="flex items-center justify-between text-slate-500 text-xs font-medium">
                <span>Low Stock Reorders</span>
                <span class="material-symbols-outlined text-amber-600 text-lg">warning</span>
            </div>
            <div class="text-2xl font-bold {{ $lowStockCount > 0 ? 'text-amber-600' : 'text-slate-900' }} mt-2">
                {{ $lowStockCount }} Designs
            </div>
            <div class="text-[11px] text-slate-500 mt-1">At or below reorder threshold</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <div class="flex items-center justify-between text-slate-500 text-xs font-medium">
                <span>Sourcing Hub Cities</span>
                <span class="material-symbols-outlined text-blue-600 text-lg">location_city</span>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2">{{ $uniqueCitiesCount }} Active</div>
            <div class="text-[11px] text-slate-500 mt-1">Suppliers from major industrial printing hubs</div>
        </div>
    </div>

    <!-- Sourcing Hub City Filter Bar -->
    <div class="bg-white rounded-xl border border-slate-200 p-3 flex flex-wrap items-center justify-between gap-3 shadow-xs">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider mr-1">Hub Filter:</span>
            <a href="{{ route('production.printed_imports') }}"
                class="px-3 py-1 rounded-lg text-xs font-semibold transition-colors {{ empty($cityFilter) || $cityFilter === 'ALL' ? 'bg-navy-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                All Hubs
            </a>
            @foreach($sourcingCities as $city)
                <a href="{{ route('production.printed_imports', ['city' => $city]) }}"
                    class="px-3 py-1 rounded-lg text-xs font-semibold transition-colors {{ $cityFilter === $city ? 'bg-purple-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    {{ $city }}
                </a>
            @endforeach
        </div>

        <div class="text-xs text-slate-500 font-medium">
            Showing <strong class="text-slate-800">{{ $topSheets->count() }}</strong> designs
        </div>
    </div>

    <!-- Custom Cards Section -->
    @if(isset($customCards) && count($customCards) > 0)
        <div class="space-y-4">
            @foreach($customCards as $card)
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-purple-600 text-base">dashboard_customize</span>
                            <h3 class="font-bold text-slate-900 text-xs">{{ $card->title }}</h3>
                            <span class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200">
                                {{ $card->card_type }}
                            </span>
                        </div>
                        <form action="{{ route('custom_cards.destroy', $card->id) }}" method="POST" onsubmit="return confirm('Delete this custom card?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-slate-400 hover:text-red-600 text-xs flex items-center gap-0.5" title="Remove Card">
                                <span class="material-symbols-outlined text-sm">delete</span>
                            </button>
                        </form>
                    </div>

                    @if($card->card_type === 'DYNAMIC_GRID' && is_array($card->grid_data))
                        <div class="overflow-x-auto rounded-lg border border-slate-100 my-2">
                            <table class="w-full text-xs text-left">
                                @if(is_array($card->column_headers) && count($card->column_headers) > 0)
                                    <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-100">
                                        <tr>
                                            @foreach($card->column_headers as $hdr)
                                                <th class="px-3 py-2">{{ $hdr }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                @endif
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($card->grid_data as $row)
                                        <tr>
                                            @if(is_array($row))
                                                @foreach($row as $cell)
                                                    <td class="px-3 py-2 text-slate-700">{{ $cell }}</td>
                                                @endforeach
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @elseif(is_array($card->field_data))
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 my-2">
                            @foreach($card->field_data as $f)
                                <div class="bg-slate-50 rounded-lg p-2.5 border border-slate-100">
                                    <span class="text-[10px] text-slate-400 block font-medium uppercase">{{ $f['label'] ?? 'Field' }}</span>
                                    <span class="text-xs font-bold text-slate-800">{{ $f['value'] ?? '—' }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <!-- Main Pre-Printed Top Sheets Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Pre-Printed Top Sheet Designs</h2>
                <p class="text-xs text-slate-500 mt-0.5">Incoming outsourced printed sheets: quantities, suppliers, landed costs, and warehouse locations</p>
            </div>
            <div class="text-xs font-semibold text-slate-600 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200">
                Total Designs: {{ $topSheets->total() }}
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-700 font-semibold border-b-2 border-slate-300">
                    <tr>
                        <th class="px-3 py-3">Code</th>
                        <th class="px-3 py-3">Arrival Date</th>
                        <th class="px-4 py-3">Design Title / Box</th>
                        <th class="px-3 py-3">Printing Press / Supplier</th>
                        <th class="px-3 py-3">Sourcing Hub</th>
                        <th class="px-3 py-3 text-right">Stock (Pieces)</th>
                        <th class="px-3 py-3 text-right">Min Threshold</th>
                        <th class="px-3 py-3 text-right">Unit Landed Cost</th>
                        <th class="px-3 py-3">Location Bay</th>
                        <th class="px-3 py-3">Custom Specs</th>
                        <th class="px-3 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($topSheets as $sheet)
                        <tr class="hover:bg-slate-50/80 transition-colors {{ $sheet->stock_pieces <= $sheet->minimum_threshold ? 'bg-amber-50/30' : '' }}">
                            <td class="px-3 py-3 font-mono font-bold text-blue-600 whitespace-nowrap">{{ $sheet->item_code }}</td>
                            <td class="px-3 py-3 whitespace-nowrap text-slate-600 font-medium">
                                {{ $sheet->received_date ? $sheet->received_date->format('d M Y') : $sheet->created_at->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-900">{{ $sheet->item_name }}</div>
                                @if($sheet->cartonProduct)
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        For: <strong class="text-slate-700">{{ $sheet->cartonProduct->box_title }}</strong>
                                        @if($sheet->cartonProduct->client)
                                            ({{ $sheet->cartonProduct->client->name }})
                                        @endif
                                    </div>
                                @endif
                                @if(!empty($sheet->notes))
                                    <div class="text-[11px] text-slate-400 italic mt-0.5 truncate max-w-xs">{{ $sheet->notes }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <div class="font-semibold text-slate-800 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[15px] text-purple-600">print</span>
                                    <span>{{ $sheet->effective_supplier_name }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                    {{ $sheet->sourcing_city }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-right whitespace-nowrap">
                                <span class="font-bold text-sm {{ $sheet->stock_pieces <= $sheet->minimum_threshold ? 'text-amber-700' : 'text-slate-900' }}">
                                    {{ number_format($sheet->stock_pieces) }}
                                </span>
                                <span class="text-[10px] text-slate-400 block">pcs</span>
                            </td>
                            <td class="px-3 py-3 text-right text-slate-500 font-mono whitespace-nowrap">{{ number_format($sheet->minimum_threshold) }} pcs</td>
                            <td class="px-3 py-3 text-right font-semibold text-slate-800 whitespace-nowrap">Rs. {{ number_format($sheet->unit_cost, 2) }}</td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap">{{ $sheet->location }}</td>
                            <td class="px-3 py-3">
                                @if(is_array($sheet->custom_fields) && count($sheet->custom_fields) > 0)
                                    <div class="flex flex-wrap gap-1 max-w-xs">
                                        @foreach($sheet->custom_fields as $cf)
                                            <span class="inline-flex items-center text-[10px] px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200" title="{{ $cf['label'] ?? '' }}: {{ $cf['value'] ?? '' }}">
                                                <strong class="mr-1">{{ $cf['label'] ?? 'Spec' }}:</strong> {{ $cf['value'] ?? '' }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <!-- Full Edit Button (Client Customization Requirement) -->
                                    <button type="button" onclick="openEditSheetModal({{ json_encode($sheet) }})"
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors" title="Edit Sheet Details">
                                        <span class="material-symbols-outlined text-[17px]">edit</span>
                                    </button>

                                    <!-- Quick Adjust Stock Quantity -->
                                    <button type="button" onclick="openAdjustModal({{ $sheet->id }}, '{{ addslashes($sheet->item_name) }}', {{ $sheet->stock_pieces }})"
                                        class="px-2 py-1 rounded-lg bg-slate-100 text-slate-700 font-semibold text-[11px] hover:bg-slate-200 transition-colors" title="Adjust Quantity">
                                        Adjust
                                    </button>

                                    <!-- Delete Button -->
                                    <form action="{{ route('production.printed_imports.destroy', $sheet->id) }}" method="POST" onsubmit="return confirm('Delete this printed sheet?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Delete Sheet">
                                            <span class="material-symbols-outlined text-[17px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-12 text-center text-slate-400">
                                <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">layers</span>
                                No pre-printed top sheet batches logged yet. Click <strong>Add Printed Top Sheet</strong> to record incoming sheets.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($topSheets->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $topSheets->links() }}
            </div>
        @endif
    </div>

</div>

<!-- MODAL 1: ADD OUTSOURCED PRE-PRINTED TOP SHEET -->
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

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Item / Batch Code *</label>
                    <input type="text" name="item_code" value="{{ $nextCode }}" required
                        class="w-full rounded-xl border-slate-300 text-xs py-2 font-mono font-bold bg-slate-50" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Arrival Date *</label>
                    <input type="date" name="received_date" value="{{ date('Y-m-d') }}" required
                        class="w-full rounded-xl border-slate-300 text-xs py-2 font-medium" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sourcing Hub City *</label>
                    <select name="sourcing_city" required class="w-full rounded-xl border-slate-300 text-xs py-2 font-medium text-purple-900">
                        @foreach($sourcingCities as $city)
                            <option value="{{ $city }}" {{ $city === 'Faisalabad' ? 'selected' : '' }}>{{ $city }} Hub</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sheet Design / Brand Name *</label>
                    <input type="text" name="item_name" placeholder="e.g. Commander Banaspati 1kgx16p" required
                        class="w-full rounded-xl border-slate-300 text-xs py-2 font-medium" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Supplier / Printing Press
                        <span class="text-[11px] font-normal text-slate-400">(Custom typing or select)</span>
                    </label>
                    <input type="text" name="supplier_name" list="press-suggestions-add" placeholder="e.g. Unicorn Printing Press, Faisalabad"
                        class="w-full rounded-xl border-slate-300 text-xs py-2 font-medium" />
                    <datalist id="press-suggestions-add">
                        @foreach($pressSuggestions as $ps)
                            <option value="{{ $ps }}"></option>
                        @endforeach
                    </datalist>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Linked Carton Product
                        <span class="text-[11px] font-normal text-slate-400">(Optional)</span>
                    </label>
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

            <!-- Dynamic Custom Fields Section -->
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
                <button type="submit" class="btn-solid-primary px-5 py-2 rounded-xl text-xs font-semibold shadow-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">save</span>
                    Save Top Sheet
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: EDIT OUTSOURCED PRE-PRINTED TOP SHEET (FULL CUSTOMIZATION) -->
<div id="modal-edit-sheet" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 my-8">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-slate-900 text-lg">Edit Pre-Printed Top Sheet</h3>
                <p class="text-xs text-slate-500">Update design details, supplier/press, landed costs, dates, and specifications</p>
            </div>
            <button onclick="closeModal('modal-edit-sheet')" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="form-edit-sheet" method="POST" class="mt-5 space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Item / Batch Code *</label>
                    <input type="text" id="edit-item-code" name="item_code" required
                        class="w-full rounded-xl border-slate-300 text-xs py-2 font-mono font-bold bg-slate-50" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Arrival Date *</label>
                    <input type="date" id="edit-received-date" name="received_date" required
                        class="w-full rounded-xl border-slate-300 text-xs py-2 font-medium" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sourcing Hub City *</label>
                    <select id="edit-sourcing-city" name="sourcing_city" required class="w-full rounded-xl border-slate-300 text-xs py-2 font-medium text-purple-900">
                        @foreach($sourcingCities as $city)
                            <option value="{{ $city }}">{{ $city }} Hub</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sheet Design / Brand Name *</label>
                    <input type="text" id="edit-item-name" name="item_name" required
                        class="w-full rounded-xl border-slate-300 text-xs py-2 font-medium" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Supplier / Printing Press
                        <span class="text-[11px] font-normal text-slate-400">(Custom typing or select)</span>
                    </label>
                    <input type="text" id="edit-supplier-name" name="supplier_name" list="press-suggestions-edit"
                        class="w-full rounded-xl border-slate-300 text-xs py-2 font-medium" />
                    <datalist id="press-suggestions-edit">
                        @foreach($pressSuggestions as $ps)
                            <option value="{{ $ps }}"></option>
                        @endforeach
                    </datalist>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Linked Carton Product
                        <span class="text-[11px] font-normal text-slate-400">(Optional)</span>
                    </label>
                    <select id="edit-carton-product-id" name="carton_product_id" class="w-full rounded-xl border-slate-300 text-xs py-2">
                        <option value="">— Select Associated Box Design (Optional) —</option>
                        @foreach($cartonProducts as $product)
                            <option value="{{ $product->id }}">{{ $product->box_title }} ({{ $product->client->name ?? 'Client' }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Storage Location Bay</label>
                    <input type="text" id="edit-location" name="location"
                        class="w-full rounded-xl border-slate-300 text-xs py-2" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Stock Quantity (Pieces) *</label>
                    <input type="number" id="edit-stock-pieces" name="stock_pieces" min="0" required
                        class="w-full rounded-xl border-slate-300 text-xs py-2 font-bold" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reorder Minimum Threshold *</label>
                    <input type="number" id="edit-minimum-threshold" name="minimum_threshold" min="0" required
                        class="w-full rounded-xl border-slate-300 text-xs py-2" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Unit Landed Cost (PKR) *</label>
                    <input type="number" id="edit-unit-cost" name="unit_cost" step="0.01" min="0" required
                        class="w-full rounded-xl border-slate-300 text-xs py-2 font-mono" />
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Notes / Instructions</label>
                <textarea id="edit-notes" name="notes" rows="2"
                    class="w-full rounded-xl border-slate-300 text-xs py-2"></textarea>
            </div>

            <!-- Dynamic Custom Fields Section for Edit -->
            <div class="border-t border-slate-200 pt-4">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider block">Custom Fields & Specifications</span>
                        <p class="text-[11px] text-slate-400">Add or update custom attributes</p>
                    </div>
                    <button type="button" onclick="addCustomFieldRow('edit-sheet-custom-fields-container')" class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 text-xs font-semibold hover:bg-blue-100 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[15px]">add</span>
                        + Add Custom Field
                    </button>
                </div>

                <div id="edit-sheet-custom-fields-container" class="space-y-2 mt-2">
                    <!-- Pre-filled and editable via JS -->
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-edit-sheet')" class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" class="btn-solid-primary px-5 py-2 rounded-xl text-xs font-semibold shadow-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">save</span>
                    Update Top Sheet
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 3: QUICK ADJUST STOCK (PIECES) -->
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
                <select name="adjustment_type" required class="w-full rounded-xl border-slate-300 text-xs py-2 font-medium">
                    <option value="ADD">+ Add Pieces (Incoming Delivery)</option>
                    <option value="SUBTRACT">- Deduct Pieces (Issued to Pasting / Corrugation)</option>
                    <option value="SET">= Set Exact Count (Physical Audit Count)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Quantity (Pieces) *</label>
                <input type="number" name="quantity" min="1" required placeholder="e.g. 500"
                    class="w-full rounded-xl border-slate-300 text-xs py-2 font-bold" />
                <span id="adjust-current-stock" class="text-[11px] text-slate-400 block mt-1"></span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Reason / Reference</label>
                <input type="text" name="reason" placeholder="e.g. Extra delivered from Faisalabad / Spoilage"
                    class="w-full rounded-xl border-slate-300 text-xs py-2" />
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-adjust-stock')" class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-700">
                    Cancel
                </button>
                <button type="submit" class="btn-solid-primary px-4 py-2 rounded-xl text-xs font-semibold">
                    Confirm Stock Update
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 4: CREATE DYNAMIC CUSTOM ERP CARD -->
<div id="modal-custom-card" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-200 my-8">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Create Custom Table / Specification Card</h3>
                <p class="text-xs text-slate-500">Design your own custom comparison matrix or data card</p>
            </div>
            <button onclick="closeModal('modal-custom-card')" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="{{ route('custom_cards.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="page_key" value="printed_imports" />

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Card / Table Title *</label>
                <input type="text" name="title" placeholder="e.g. Faisalabad Supplier Rate Comparison" required
                    class="w-full rounded-xl border-slate-300 text-xs py-2" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Card Type *</label>
                    <select name="card_type" id="custom_card_type" onchange="toggleCustomCardTypeView()"
                        class="w-full rounded-xl border-slate-300 text-xs py-2">
                        <option value="DYNAMIC_GRID">Dynamic Spreadsheet Grid (Rows & Cols)</option>
                        <option value="SPEC_FIELDS">Key-Value Attribute Cards</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Placement Order</label>
                    <input type="number" name="display_order" value="1" min="1"
                        class="w-full rounded-xl border-slate-300 text-xs py-2" />
                </div>
            </div>

            <!-- Dynamic Grid Builder -->
            <div id="grid_builder_section" class="border border-slate-200 rounded-xl p-3 bg-slate-50/50 space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Initial Rows</label>
                        <input type="number" id="builder_rows" value="3" min="1" max="15" onchange="generateCustomGridInputs()"
                            class="w-full rounded-lg border-slate-300 text-xs py-1.5" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Initial Columns</label>
                        <input type="number" id="builder_cols" value="3" min="1" max="8" onchange="generateCustomGridInputs()"
                            class="w-full rounded-lg border-slate-300 text-xs py-1.5" />
                    </div>
                </div>

                <div>
                    <span class="text-[11px] font-semibold text-slate-700 block mb-1">Column Header Labels</span>
                    <div id="builder_headers_container" class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <input type="text" name="column_headers[]" value="Supplier Name" class="rounded-lg border-slate-300 text-xs py-1 px-2" />
                        <input type="text" name="column_headers[]" value="City" class="rounded-lg border-slate-300 text-xs py-1 px-2" />
                        <input type="text" name="column_headers[]" value="Rate / 1000 Pcs" class="rounded-lg border-slate-300 text-xs py-1 px-2" />
                    </div>
                </div>

                <div>
                    <span class="text-[11px] font-semibold text-slate-700 block mb-1">Initial Data Cells</span>
                    <div id="builder_cells_container" class="overflow-x-auto">
                        <!-- Populated via JS -->
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-custom-card')" class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-700">
                    Cancel
                </button>
                <button type="submit" class="btn-solid-primary px-4 py-2 rounded-xl text-xs font-semibold">
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

    function openEditSheetModal(sheet) {
        document.getElementById('form-edit-sheet').action = `/production/printed-imports/${sheet.id}`;
        document.getElementById('edit-item-code').value = sheet.item_code || '';
        document.getElementById('edit-item-name').value = sheet.item_name || '';
        document.getElementById('edit-supplier-name').value = sheet.supplier_name || (sheet.supplier ? sheet.supplier.name : '');
        document.getElementById('edit-sourcing-city').value = sheet.sourcing_city || 'Faisalabad';
        document.getElementById('edit-carton-product-id').value = sheet.carton_product_id || '';
        document.getElementById('edit-location').value = sheet.location || 'Top Sheet Bay 1';
        document.getElementById('edit-stock-pieces').value = sheet.stock_pieces || 0;
        document.getElementById('edit-minimum-threshold').value = sheet.minimum_threshold || 500;
        document.getElementById('edit-unit-cost').value = parseFloat(sheet.unit_cost || 0).toFixed(2);
        
        let recDate = sheet.received_date ? sheet.received_date.substring(0, 10) : '';
        if (!recDate && sheet.created_at) {
            recDate = sheet.created_at.substring(0, 10);
        }
        document.getElementById('edit-received-date').value = recDate || new Date().toISOString().substring(0, 10);
        document.getElementById('edit-notes').value = sheet.notes || '';

        // Populate Custom Fields
        const container = document.getElementById('edit-sheet-custom-fields-container');
        container.innerHTML = '';
        if (sheet.custom_fields && Array.isArray(sheet.custom_fields)) {
            sheet.custom_fields.forEach(cf => {
                addCustomFieldRowWithValue('edit-sheet-custom-fields-container', cf.label || '', cf.value || '');
            });
        }

        openModal('modal-edit-sheet');
    }

    function addCustomFieldRow(containerId) {
        addCustomFieldRowWithValue(containerId, '', '');
    }

    function addCustomFieldRowWithValue(containerId, label, value) {
        const container = document.getElementById(containerId);
        const idx = Date.now() + Math.floor(Math.random() * 1000);
        const row = document.createElement('div');
        row.className = 'grid grid-cols-12 gap-2 items-center';
        row.id = `cfield-${idx}`;
        row.innerHTML = `
            <div class="col-span-5">
                <input type="text" name="custom_fields[${idx}][label]" value="${escapeHtml(label)}" placeholder="Field Label (e.g. GSM / Size)" required
                    class="w-full rounded-lg border-slate-300 text-xs py-1.5 px-2 font-medium" />
            </div>
            <div class="col-span-6">
                <input type="text" name="custom_fields[${idx}][value]" value="${escapeHtml(value)}" placeholder="Value (e.g. 180 GSM, Gloss Laminated)" required
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

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function generateCustomGridInputs() {
        const rows = parseInt(document.getElementById('builder_rows').value) || 3;
        const cols = parseInt(document.getElementById('builder_cols').value) || 3;

        const hdrContainer = document.getElementById('builder_headers_container');
        hdrContainer.innerHTML = '';
        const defaultHeaders = ['Supplier Name', 'City', 'Rate / 1000 Pcs', 'Delivery Time', 'Contact #', 'Remarks'];
        for (let c = 0; c < cols; c++) {
            const inp = document.createElement('input');
            inp.type = 'text';
            inp.name = 'column_headers[]';
            inp.value = defaultHeaders[c] || `Column ${c + 1}`;
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
                td.innerHTML = `<input type="text" name="grid_data[${r}][${c}]" value="" placeholder="Cell ${r+1},${c+1}" class="w-full rounded border-slate-200 text-xs py-1 px-1.5" />`;
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
