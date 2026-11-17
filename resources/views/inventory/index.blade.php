@extends('layouts.app')

@section('title', 'Factory Stock, Paper Reels & Pre-Printed Sheets')
@section('breadcrumb', 'Inventory')

@section('header_actions')
    <div class="flex items-center gap-2 flex-wrap">
        <button onclick="openModal('modal-add-top-sheet')" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50 shadow-xs transition-colors">
            + Add Top Sheet (Pcs)
        </button>
        <button onclick="openModal('modal-add-reel')" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50 shadow-xs transition-colors">
            + Add Paper Reel (KG)
        </button>
        <button onclick="openModal('modal-add-carton')" class="px-3 py-1.5 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs transition-colors">
            + New Box Spec
        </button>
    </div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Header with 3 Tabs -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Factory Inventory Management</h1>
            <p class="text-xs text-slate-500 mt-1">Track paper reels in KG, ready cartons by buyer, and pre-printed top sheets in pieces</p>
        </div>

        <div class="inline-flex rounded-xl bg-white border border-slate-200 p-1 shadow-xs">
            <a href="{{ route('inventory.index', ['tab' => 'cartons']) }}"
               class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $tab === 'cartons' ? 'bg-navy-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                Ready Cartons ({{ $cartons->count() }})
            </a>
            <a href="{{ route('inventory.index', ['tab' => 'reels']) }}"
               class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $tab === 'reels' ? 'bg-navy-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                Paper Reels ({{ number_format($totalReelsKg) }} KG)
            </a>
            <a href="{{ route('inventory.index', ['tab' => 'top-sheets']) }}"
               class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $tab === 'top-sheets' ? 'bg-navy-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                Top Sheets ({{ number_format($totalTopSheetsPieces) }} Pcs)
            </a>
        </div>
    </div>

    @if($tab === 'cartons')
        <!-- Finished Cartons Grouped by Buyer / Client Party -->
        <div class="space-y-6">
            @forelse($cartonsByParty as $partyName => $partyCartons)
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                    <div class="px-5 py-3.5 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-blue-600 text-[20px]">corporate_fare</span>
                            <span class="font-bold text-sm text-slate-900">{{ $partyName }}</span>
                            <span class="text-xs text-slate-500 font-medium">({{ $partyCartons->count() }} box specifications)</span>
                        </div>
                        <div class="text-xs text-slate-600 font-semibold num-tabular">
                            Total Ready: {{ number_format($partyCartons->sum('stock_pieces')) }} Pcs
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-white border-b border-slate-200 uppercase font-semibold text-slate-500">
                                    <th class="py-3 px-5">Box Title & SKU</th>
                                    <th class="py-3 px-5">Ply & Flute</th>
                                    <th class="py-3 px-5 text-right">Unit Price</th>
                                    <th class="py-3 px-5 text-right">Bundles</th>
                                    <th class="py-3 px-5 text-right">Ready Stock</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($partyCartons as $c)
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="py-3 px-5">
                                            <div class="font-bold text-slate-900">{{ $c->box_title }}</div>
                                            <div class="text-[10px] text-slate-400 font-mono">{{ $c->sku }}</div>
                                        </td>
                                        <td class="py-3 px-5">
                                            <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700">
                                                {{ $c->ply_type }} · {{ str_replace('_', ' ', $c->flute_type) }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-5 text-right num-tabular font-medium text-slate-700">
                                            Rs. {{ number_format($c->unit_selling_price, 2) }}
                                        </td>
                                        <td class="py-3 px-5 text-right num-tabular text-slate-500">
                                            {{ floor($c->stock_pieces / max(1, $c->bundle_pack_size)) }} Bundles
                                        </td>
                                        <td class="py-3 px-5 text-right font-extrabold num-tabular text-slate-900">
                                            {{ number_format($c->stock_pieces) }} Pcs
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-400">
                    <span class="material-symbols-outlined text-4xl block mb-2 text-slate-300">inventory_2</span>
                    No carton specifications registered yet. Click "+ New Box Spec" above.
                </div>
            @endforelse
        </div>

    @elseif($tab === 'top-sheets')
        <!-- Pre-Printed Top Sheets View (Unit: Pieces / Tadaad, Outsourced Printing) -->
        <div class="space-y-6">
            <div class="p-4 rounded-xl bg-blue-50/80 border border-blue-200 text-blue-900 text-xs flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-blue-600">info</span>
                    <div>
                        <strong>Pre-Printed Top Sheets:</strong> Outsourced flexo printing from <strong>Faisalabad, Multan, Lahore, Rahim Yar Khan & Jumber</strong>. Stock is managed strictly in <strong>Pieces / Tadaad</strong> (not kg) mapped to box specifications.
                    </div>
                </div>
                <button onclick="openModal('modal-add-top-sheet')" class="px-3 py-1.5 rounded-lg bg-blue-600 text-white font-semibold text-xs shrink-0">
                    + Add New Sheet
                </button>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-semibold">
                                <th class="py-3 px-4">Item Code & Title</th>
                                <th class="py-3 px-4">Target Box Spec</th>
                                <th class="py-3 px-4">Sourcing Location</th>
                                <th class="py-3 px-4">Supplier / Printer</th>
                                <th class="py-3 px-4 text-right">Unit Cost</th>
                                <th class="py-3 px-4 text-right">Stock Quantity (Pcs)</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($printedTopSheets as $sheet)
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-900">{{ $sheet->item_name }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $sheet->item_code }} · {{ $sheet->location }}</div>
                                    </td>
                                    <td class="py-3 px-4 text-slate-700 font-medium">
                                        {{ $sheet->cartonProduct?->box_title ?? 'General Stock' }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                            {{ $sheet->sourcing_city }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-600">
                                        {{ $sheet->supplier?->name ?? 'External Flexo Printer' }}
                                    </td>
                                    <td class="py-3 px-4 text-right num-tabular text-slate-700">
                                        Rs. {{ number_format($sheet->unit_cost, 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-extrabold num-tabular text-navy-900 text-sm">
                                        {{ number_format($sheet->stock_pieces) }} Pcs
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        @if($sheet->stock_pieces <= 0)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">Out of Stock</span>
                                        @elseif($sheet->stock_pieces < $sheet->minimum_threshold)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">Low Stock</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Available</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <button onclick="openAdjustTopSheetModal({{ json_encode($sheet) }})"
                                            class="text-xs text-blue-600 hover:text-blue-800 font-semibold">
                                            Adjust Stock
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-10 text-center text-slate-400">
                                        No pre-printed top sheets recorded. Click "+ Add Top Sheet" to register external printed sheets.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @else
        <!-- Kraft Paper Reels View (Tracked Strictly in Kilograms / KG, Deckle Sizes 15.0" to 50.0") -->
        <div class="space-y-6">
            <!-- 3 Grades Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @php
                    $tlKg = $paperReels->where('paper_grade', 'TESTLINER')->sum('remaining_weight_kg');
                    $sfKg = $paperReels->where('paper_grade', 'SUPER_FLUTING')->sum('remaining_weight_kg');
                    $cmpKg = $paperReels->where('paper_grade', 'CMP_ULTRA')->sum('remaining_weight_kg');
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                    <div class="text-xs font-semibold uppercase text-slate-500">Testliner (TL) Grade</div>
                    <div class="text-2xl font-extrabold text-navy-900 mt-2 num-tabular">{{ number_format($tlKg) }} KG</div>
                    <div class="text-xs text-slate-400 mt-1">Liner paper rolls for corrugation</div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                    <div class="text-xs font-semibold uppercase text-slate-500">Super Fluting (SF) Grade</div>
                    <div class="text-2xl font-extrabold text-blue-600 mt-2 num-tabular">{{ number_format($sfKg) }} KG</div>
                    <div class="text-xs text-slate-400 mt-1">Fluting medium rolls</div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                    <div class="text-xs font-semibold uppercase text-slate-500">CMP Ultra Grade</div>
                    <div class="text-2xl font-extrabold text-emerald-600 mt-2 num-tabular">{{ number_format($cmpKg) }} KG</div>
                    <div class="text-xs text-slate-400 mt-1">High-strength craft rolls</div>
                </div>
            </div>

            <!-- Grouped by Deckle Sizes -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div class="font-bold text-sm text-slate-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-600">straighten</span>
                        <span>Reels Inventory by Deckle Size (15" to 50" with 0.5" Increments)</span>
                    </div>
                    <span class="text-xs font-semibold text-slate-600 num-tabular">Total Stock: {{ number_format($totalReelsKg) }} KG</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-white border-b border-slate-200 uppercase font-semibold text-slate-500">
                                <th class="py-3 px-4">Reel Serial #</th>
                                <th class="py-3 px-4">Deckle Size (Inches)</th>
                                <th class="py-3 px-4">Paper Grade</th>
                                <th class="py-3 px-4">Supplier Mill</th>
                                <th class="py-3 px-4 text-right">Remaining Weight</th>
                                <th class="py-3 px-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($paperReels as $reel)
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-3 px-4 font-mono font-bold text-blue-600">
                                        {{ $reel->reel_number }}
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-slate-900 num-tabular">
                                        {{ number_format($reel->deckle_size_inches, 1) }}"
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-800">
                                            {{ str_replace('_', ' ', $reel->paper_grade ?: $reel->rawMaterial?->name) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-600">
                                        {{ $reel->supplier?->name ?? 'Century / Packages Mill' }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-extrabold num-tabular text-navy-900">
                                        {{ number_format($reel->remaining_weight_kg) }} KG
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $reel->status === 'IN_STOCK' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700' }}">
                                            {{ str_replace('_', ' ', $reel->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-10 text-center text-slate-400">
                                        No paper reels in stock.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@section('modals')
<!-- Add Paper Reel Modal -->
<div id="modal-add-reel" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Add Paper Reel Roll (KG)</h3>
            <button onclick="closeModal('modal-add-reel')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form action="{{ route('inventory.reels.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Mill Reel Serial # *</label>
                <input type="text" name="reel_number" required placeholder="e.g. CP-REEL-9005" class="w-full rounded-lg border-slate-300 text-sm uppercase font-mono" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Paper Grade *</label>
                    <select name="paper_grade" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                        <option value="TESTLINER">Testliner (TL)</option>
                        <option value="SUPER_FLUTING">Super Fluting (SF)</option>
                        <option value="CMP_ULTRA">CMP Ultra</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Deckle Size (15"-50") *</label>
                    <select name="deckle_size_inches" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600 font-mono">
                        @foreach($deckleSizes as $size)
                            <option value="{{ $size }}" {{ $size == 33.0 ? 'selected' : '' }}>{{ number_format($size, 1) }} inches</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Raw Material Category *</label>
                <select name="raw_material_id" required class="w-full rounded-lg border-slate-300 text-sm">
                    @foreach($rawMaterials as $rm)
                        <option value="{{ $rm->id }}">{{ $rm->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier Paper Mill</label>
                <select name="supplier_id" class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="">-- Direct Mill Sourcing --</option>
                    @foreach($suppliers as $supp)
                        <option value="{{ $supp->id }}">{{ $supp->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Gross Weight in Kilograms (KG) *</label>
                <input type="number" name="initial_weight_kg" min="10" step="0.5" required placeholder="e.g. 1450" class="w-full rounded-lg border-slate-300 text-sm font-bold focus:border-blue-600 focus:ring-blue-600 font-mono" />
            </div>

            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-add-reel')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs">Add Reel to Shed</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Pre-Printed Top Sheet Modal -->
<div id="modal-add-top-sheet" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Add Pre-Printed Top Sheet (Pieces)</h3>
            <button onclick="closeModal('modal-add-top-sheet')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form action="{{ route('inventory.top_sheets.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Sheet Item Name / Print Title *</label>
                <input type="text" name="item_name" required placeholder="e.g. Irfan Pharma Cough Syrup Outer Top Sheet"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sourcing City *</label>
                    <select name="sourcing_city" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                        @foreach($sourcingCities as $city)
                            <option value="{{ $city }}">{{ $city }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Target Box Spec (Optional)</label>
                    <select name="carton_product_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                        <option value="">-- General Stock --</option>
                        @foreach($cartons as $c)
                            <option value="{{ $c->id }}">{{ $c->box_title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Initial Quantity (Pieces) *</label>
                    <input type="number" name="stock_pieces" min="0" required placeholder="e.g. 10000"
                        class="w-full rounded-lg border-slate-300 text-sm font-bold focus:border-blue-600 focus:ring-blue-600 font-mono" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Unit Cost (Rs./Pc)</label>
                    <input type="number" step="0.01" min="0" name="unit_cost" placeholder="e.g. 8.50"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600 font-mono" />
                </div>
            </div>

            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-add-top-sheet')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs">Save Top Sheet Stock</button>
            </div>
        </form>
    </div>
</div>

<!-- Adjust Top Sheet Stock Modal -->
<div id="modal-adjust-top-sheet" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900" id="adjust-sheet-title">Adjust Top Sheet Stock</h3>
            <button onclick="closeModal('modal-adjust-top-sheet')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form id="adjust-sheet-form" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Adjustment Type *</label>
                <select name="adjustment_type" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                    <option value="ADD">Add New Delivery (+ Pcs)</option>
                    <option value="SUBTRACT">Damaged / Scrap Waste (- Pcs)</option>
                    <option value="SET">Physical Count Override (Set Exact)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Pieces Quantity *</label>
                <input type="number" name="pieces" min="0" required class="w-full rounded-lg border-slate-300 text-sm font-bold focus:border-blue-600 focus:ring-blue-600 font-mono" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Reason / Note</label>
                <input type="text" name="notes" placeholder="e.g. Sourced from Multan / 100 damaged sheets in transit"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
            </div>
            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-adjust-top-sheet')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs">Apply Adjustment</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Carton Spec Modal -->
<div id="modal-add-carton" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">New Carton Specification</h3>
            <button onclick="closeModal('modal-add-carton')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form action="{{ route('inventory.cartons.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Client Buyer *</label>
                <select name="client_id" required class="w-full rounded-lg border-slate-300 text-sm">
                    @foreach($customers as $cust)
                        <option value="{{ $cust->id }}">{{ $cust->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Box Name / Title *</label>
                <input type="text" name="box_title" required placeholder="e.g. 5-Litre Cooking Oil Carton" class="w-full rounded-lg border-slate-300 text-sm" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ply Type</label>
                    <select name="ply_type" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="5-PLY">5-PLY (Heavy Duty)</option>
                        <option value="3-PLY">3-PLY (Standard)</option>
                        <option value="7-PLY">7-PLY (Export)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Flute</label>
                    <select name="flute_type" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="BC_DOUBLE_WALL">B/C Double Wall</option>
                        <option value="B_FLUTE">B Flute</option>
                        <option value="C_FLUTE">C Flute</option>
                        <option value="E_MICRO">E Micro Flute</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Unit Selling Price (Rs./Pc) *</label>
                    <input type="number" name="unit_selling_price" step="0.5" min="0" required class="w-full rounded-lg border-slate-300 text-sm font-bold font-mono" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Opening Ready Stock (Pcs)</label>
                    <input type="number" name="stock_pieces" value="0" min="0" class="w-full rounded-lg border-slate-300 text-sm font-mono" />
                </div>
            </div>
            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-add-carton')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs">Save Box Spec</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openAdjustTopSheetModal(sheet) {
    document.getElementById('adjust-sheet-title').textContent = 'Adjust Stock: ' + sheet.item_name;
    document.getElementById('adjust-sheet-form').action = '/inventory/top-sheets/' + sheet.id + '/adjust';
    openModal('modal-adjust-top-sheet');
}
</script>
@endpush
