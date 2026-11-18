@extends('layouts.app')

@section('title', 'Warehouse Inventory & Paper Raw Materials')

@section('header_actions')
    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('production.printed_imports') }}" class="px-3 py-1.5 rounded-lg border border-purple-200 bg-purple-50 text-purple-700 hover:bg-purple-100 text-xs font-semibold flex items-center gap-1 transition-colors">
            <span class="material-symbols-outlined text-[16px]">print</span>
            <span>Outsourced Top Sheets Hub</span>
        </a>
        <button onclick="openModal('modal-add-reel')" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50 shadow-xs transition-colors">
            + Add Paper Reel (KG)
        </button>
        <button onclick="openModal('modal-add-carton')" class="btn-solid-primary px-3 py-1.5 rounded-lg text-xs font-semibold shadow-xs transition-colors">
            + New Box Spec
        </button>
    </div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Tab Navigation Bar -->
    <div class="border-b border-slate-200 flex gap-6">
        <a href="{{ route('inventory.index', ['tab' => 'cartons']) }}"
           class="pb-3 text-xs font-bold uppercase tracking-wider border-b-2 flex items-center gap-1.5 {{ $tab === 'cartons' ? 'border-navy-900 text-navy-900' : 'border-transparent text-slate-400 hover:text-slate-600' }}">
            <span class="material-symbols-outlined text-base">inventory_2</span>
            Carton Specifications & Finished Stock (Pieces)
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'reels']) }}"
           class="pb-3 text-xs font-bold uppercase tracking-wider border-b-2 flex items-center gap-1.5 {{ $tab === 'reels' ? 'border-navy-900 text-navy-900' : 'border-transparent text-slate-400 hover:text-slate-600' }}">
            <span class="material-symbols-outlined text-base">layers</span>
            Paper Reels (Kraft / Fluting in KG)
        </a>
        <a href="{{ route('inventory.index', ['tab' => 'top-sheets']) }}"
           class="pb-3 text-xs font-bold uppercase tracking-wider border-b-2 flex items-center gap-1.5 {{ $tab === 'top-sheets' ? 'border-navy-900 text-navy-900' : 'border-transparent text-slate-400 hover:text-slate-600' }}">
            <span class="material-symbols-outlined text-base">photo_library</span>
            Pre-Printed Top Sheets (Pieces)
        </a>
    </div>

    @if($tab === 'cartons')
        <!-- Carton Specifications View (Tracked Strictly in Pieces / Tadaad, Categorized by Client Party) -->
        <div class="space-y-6">
            @forelse($cartonsByParty as $partyName => $partyCartons)
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                    <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
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
                                <tr class="bg-white border-b-2 border-slate-300 uppercase font-semibold text-slate-600">
                                    <th class="py-3 px-5">Box Title & SKU</th>
                                    <th class="py-3 px-5">Ply & Flute</th>
                                    <th class="py-3 px-5 text-right">Unit Price</th>
                                    <th class="py-3 px-5 text-right">Bundles</th>
                                    <th class="py-3 px-5 text-right">Ready Stock</th>
                                    <th class="py-3 px-5 text-center">Actions</th>
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
                                            {{ floor($c->stock_pieces / max(1, $c->bundle_pack_size ?: 25)) }} Bundles
                                        </td>
                                        <td class="py-3 px-5 text-right font-extrabold num-tabular text-slate-900">
                                            {{ number_format($c->stock_pieces) }} Pcs
                                        </td>
                                        <td class="py-3 px-5 text-center whitespace-nowrap">
                                            <div class="inline-flex items-center gap-1">
                                                <button type="button" onclick="openEditCartonModal({{ json_encode($c) }})"
                                                    class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-slate-100 transition-colors" title="Edit Box Spec">
                                                    <span class="material-symbols-outlined text-[17px]">edit</span>
                                                </button>
                                                <form action="{{ route('inventory.cartons.destroy', $c->id) }}" method="POST" onsubmit="return confirm('Delete carton spec \'{{ addslashes($c->box_title) }}\'?');" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Delete Spec">
                                                        <span class="material-symbols-outlined text-[17px]">delete</span>
                                                    </button>
                                                </form>
                                            </div>
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
            <div class="p-4 rounded-xl bg-purple-50/80 border border-purple-200 text-purple-900 text-xs flex items-center justify-between">
                <div>
                    <strong>Pre-Printed Top Sheets Hub:</strong> Sourced from industrial printing presses in Faisalabad, Multan, Lahore, and RYK.
                </div>
                <a href="{{ route('production.printed_imports') }}" class="btn-solid-primary px-3 py-1.5 rounded-lg text-xs font-semibold">
                    Open Full Top Sheets Manager &rarr;
                </a>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div class="font-bold text-sm text-slate-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-600">photo_library</span>
                        <span>Pre-Printed Top Sheets Inventory</span>
                    </div>
                    <span class="text-xs font-semibold text-slate-600 num-tabular">Total Stock: {{ number_format($totalTopSheetsPieces) }} Pcs</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-white border-b-2 border-slate-300 uppercase font-semibold text-slate-600">
                                <th class="py-3 px-4">Code</th>
                                <th class="py-3 px-4">Design Title</th>
                                <th class="py-3 px-4">Supplier / Press</th>
                                <th class="py-3 px-4">Sourcing City</th>
                                <th class="py-3 px-4 text-right">Available Stock</th>
                                <th class="py-3 px-4 text-right">Unit Cost</th>
                                <th class="py-3 px-4 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($printedTopSheets as $sheet)
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-3 px-4 font-mono font-bold text-blue-600">{{ $sheet->item_code }}</td>
                                    <td class="py-3 px-4 font-bold text-slate-900">{{ $sheet->item_name }}</td>
                                    <td class="py-3 px-4 text-slate-700">{{ $sheet->effective_supplier_name ?? $sheet->supplier?->name ?? 'Custom Press' }}</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                            {{ $sheet->sourcing_city }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-extrabold num-tabular text-slate-900">
                                        {{ number_format($sheet->stock_pieces) }} Pcs
                                    </td>
                                    <td class="py-3 px-4 text-right font-medium text-slate-700">Rs. {{ number_format($sheet->unit_cost, 2) }}</td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        <button onclick="openAdjustTopSheetModal({{ json_encode($sheet) }})"
                                            class="text-xs text-blue-600 hover:text-blue-800 font-semibold px-2 py-1 rounded hover:bg-blue-50">
                                            Adjust Stock
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-10 text-center text-slate-400">
                                        No pre-printed top sheets recorded.
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

            <!-- Paper Reels Table -->
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
                            <tr class="bg-white border-b-2 border-slate-300 uppercase font-semibold text-slate-600">
                                <th class="py-3 px-4">Reel Serial #</th>
                                <th class="py-3 px-4">Deckle Size (Inches)</th>
                                <th class="py-3 px-4">Paper Grade</th>
                                <th class="py-3 px-4">Supplier Mill</th>
                                <th class="py-3 px-4 text-right">Remaining Weight</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-center">Actions</th>
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
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" onclick="openEditReelModal({{ json_encode($reel) }})"
                                                class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-slate-100 transition-colors" title="Edit Reel">
                                                <span class="material-symbols-outlined text-[17px]">edit</span>
                                            </button>
                                            <form action="{{ route('inventory.reels.destroy', $reel->id) }}" method="POST" onsubmit="return confirm('Delete paper reel #{{ $reel->reel_number }}?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Delete Reel">
                                                    <span class="material-symbols-outlined text-[17px]">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-10 text-center text-slate-400">
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
                <button type="submit" class="btn-solid-primary px-5 py-2 rounded-lg text-xs font-semibold shadow-xs">Add Reel to Shed</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Paper Reel Modal (Full Customization) -->
<div id="modal-edit-reel" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Edit Paper Reel Details</h3>
            <button onclick="closeModal('modal-edit-reel')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form id="form-edit-reel" method="POST" class="mt-4 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Mill Reel Serial # *</label>
                <input type="text" id="edit-reel-number" name="reel_number" required class="w-full rounded-lg border-slate-300 text-sm uppercase font-mono" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Paper Grade *</label>
                    <select id="edit-reel-grade" name="paper_grade" required class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="TESTLINER">Testliner (TL)</option>
                        <option value="SUPER_FLUTING">Super Fluting (SF)</option>
                        <option value="CMP_ULTRA">CMP Ultra</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Deckle Size (Inches) *</label>
                    <select id="edit-reel-deckle" name="deckle_size_inches" required class="w-full rounded-lg border-slate-300 text-sm font-mono">
                        @foreach($deckleSizes as $size)
                            <option value="{{ $size }}">{{ number_format($size, 1) }} inches</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Raw Material Category *</label>
                <select id="edit-reel-raw-material" name="raw_material_id" required class="w-full rounded-lg border-slate-300 text-sm">
                    @foreach($rawMaterials as $rm)
                        <option value="{{ $rm->id }}">{{ $rm->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier Paper Mill</label>
                <select id="edit-reel-supplier" name="supplier_id" class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="">-- Direct Mill Sourcing --</option>
                    @foreach($suppliers as $supp)
                        <option value="{{ $supp->id }}">{{ $supp->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Initial Weight (KG) *</label>
                    <input type="number" id="edit-reel-initial" name="initial_weight_kg" min="1" step="0.5" required class="w-full rounded-lg border-slate-300 text-sm font-bold font-mono" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Remaining Weight (KG) *</label>
                    <input type="number" id="edit-reel-remaining" name="remaining_weight_kg" min="0" step="0.5" required class="w-full rounded-lg border-slate-300 text-sm font-bold font-mono" />
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Status *</label>
                <select id="edit-reel-status" name="status" required class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="IN_STOCK">IN STOCK</option>
                    <option value="IN_PRODUCTION">IN PRODUCTION (ON CORRUGATOR)</option>
                    <option value="CONSUMED">CONSUMED (EMPTY CORE)</option>
                </select>
            </div>

            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-edit-reel')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Cancel</button>
                <button type="submit" class="btn-solid-primary px-5 py-2 rounded-lg text-xs font-semibold shadow-xs">Update Reel</button>
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
                <button type="submit" class="btn-solid-primary px-5 py-2 rounded-lg text-xs font-semibold shadow-xs">Save Box Spec</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Carton Spec Modal (Full Customization) -->
<div id="modal-edit-carton" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Edit Carton Specification</h3>
            <button onclick="closeModal('modal-edit-carton')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form id="form-edit-carton" method="POST" class="mt-4 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Client Buyer *</label>
                <select id="edit-carton-client" name="client_id" required class="w-full rounded-lg border-slate-300 text-sm">
                    @foreach($customers as $cust)
                        <option value="{{ $cust->id }}">{{ $cust->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Box Name / Title *</label>
                <input type="text" id="edit-carton-title" name="box_title" required class="w-full rounded-lg border-slate-300 text-sm" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ply Type</label>
                    <select id="edit-carton-ply" name="ply_type" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="5-PLY">5-PLY (Heavy Duty)</option>
                        <option value="3-PLY">3-PLY (Standard)</option>
                        <option value="7-PLY">7-PLY (Export)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Flute</label>
                    <select id="edit-carton-flute" name="flute_type" class="w-full rounded-lg border-slate-300 text-sm">
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
                    <input type="number" id="edit-carton-price" name="unit_selling_price" step="0.5" min="0" required class="w-full rounded-lg border-slate-300 text-sm font-bold font-mono" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ready Stock (Pcs) *</label>
                    <input type="number" id="edit-carton-stock" name="stock_pieces" min="0" required class="w-full rounded-lg border-slate-300 text-sm font-mono" />
                </div>
            </div>
            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-edit-carton')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Cancel</button>
                <button type="submit" class="btn-solid-primary px-5 py-2 rounded-lg text-xs font-semibold shadow-xs">Update Box Spec</button>
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
                <button type="submit" class="btn-solid-primary px-5 py-2 rounded-lg text-xs font-semibold shadow-xs">Apply Adjustment</button>
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

function openEditCartonModal(c) {
    document.getElementById('form-edit-carton').action = '/inventory/cartons/' + c.id;
    document.getElementById('edit-carton-client').value = c.client_id;
    document.getElementById('edit-carton-title').value = c.box_title;
    document.getElementById('edit-carton-ply').value = c.ply_type || '5-PLY';
    document.getElementById('edit-carton-flute').value = c.flute_type || 'BC_DOUBLE_WALL';
    document.getElementById('edit-carton-price').value = c.unit_selling_price;
    document.getElementById('edit-carton-stock').value = c.stock_pieces || 0;
    openModal('modal-edit-carton');
}

function openEditReelModal(reel) {
    document.getElementById('form-edit-reel').action = '/inventory/reels/' + reel.id;
    document.getElementById('edit-reel-number').value = reel.reel_number;
    document.getElementById('edit-reel-grade').value = reel.paper_grade || 'TESTLINER';
    document.getElementById('edit-reel-deckle').value = parseFloat(reel.deckle_size_inches || 33.0).toFixed(1);
    document.getElementById('edit-reel-raw-material').value = reel.raw_material_id;
    document.getElementById('edit-reel-supplier').value = reel.supplier_id || '';
    document.getElementById('edit-reel-initial').value = parseFloat(reel.initial_weight_kg || 0);
    document.getElementById('edit-reel-remaining').value = parseFloat(reel.remaining_weight_kg || 0);
    document.getElementById('edit-reel-status').value = reel.status || 'IN_STOCK';
    openModal('modal-edit-reel');
}
</script>
@endpush
