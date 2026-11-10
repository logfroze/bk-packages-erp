@extends('layouts.app')

@section('title', 'Stock & Paper Reels')
@section('breadcrumb', 'Inventory')

@section('header_actions')
    <div class="flex items-center gap-2">
        <button onclick="openModal('modal-add-reel')" class="px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50">
            + Add Paper Reel
        </button>
        <button onclick="openModal('modal-add-carton')" class="px-3.5 py-2 rounded-lg bg-navy-900 text-white text-sm font-medium hover:bg-navy-800">
            + New Box Spec
        </button>
    </div>
@endsection

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Stock & Paper Reels</h1>
            <p class="text-sm text-slate-500 mt-1">Track ready cartons in godown and raw kraft paper rolls</p>
        </div>

        <div class="inline-flex rounded-xl bg-white border border-slate-200 p-1">
            <a href="{{ route('inventory.index', ['tab' => 'cartons']) }}"
               class="px-4 py-2 rounded-lg text-xs font-semibold {{ $tab === 'cartons' ? 'bg-navy-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                Finished Cartons ({{ $cartons->count() }})
            </a>
            <a href="{{ route('inventory.index', ['tab' => 'reels']) }}"
               class="px-4 py-2 rounded-lg text-xs font-semibold {{ $tab === 'reels' ? 'bg-navy-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                Kraft Paper Reels ({{ $rawMaterials->count() }})
            </a>
        </div>
    </div>

    @if($tab === 'cartons')
        <!-- Finished Cartons View -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="py-3.5 px-5">Box Specification</th>
                            <th class="py-3.5 px-5">Client Party</th>
                            <th class="py-3.5 px-5">Ply / Flute</th>
                            <th class="py-3.5 px-5 text-right">Rate / Pc</th>
                            <th class="py-3.5 px-5 text-right">Available Stock</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @foreach($cartons as $c)
                            <tr class="hover:bg-slate-50/70">
                                <td class="py-4 px-5 font-medium text-slate-900">
                                    {{ $c->box_title }}
                                    <div class="text-xs text-slate-400 font-normal">{{ $c->sku }}</div>
                                </td>
                                <td class="py-4 px-5 text-slate-600">{{ $c->client->name ?? 'General' }}</td>
                                <td class="py-4 px-5">
                                    <span class="px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-700">{{ $c->ply_type }}</span>
                                </td>
                                <td class="py-4 px-5 text-right num-tabular text-slate-700">Rs. {{ number_format($c->unit_selling_price, 2) }}</td>
                                <td class="py-4 px-5 text-right font-bold num-tabular text-slate-900">
                                    {{ number_format($c->stock_pieces) }} Pcs
                                    <div class="text-xs font-normal text-slate-400">({{ floor($c->stock_pieces / max(1, $c->bundle_pack_size)) }} Bundles)</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <!-- Raw Materials & Reels View -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            @foreach($rawMaterials as $rm)
                <div class="bg-white rounded-xl border border-slate-200 p-6 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase text-blue-600">{{ str_replace('_', ' ', $rm->category) }}</span>
                        <h3 class="text-base font-semibold text-slate-900 mt-1">{{ $rm->name }}</h3>
                        <p class="text-xs text-slate-500 mt-1">{{ $rm->godown_location }} · Min alert: {{ number_format($rm->minimum_threshold) }} {{ $rm->unit_of_measure }}</p>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-slate-900 num-tabular">{{ number_format($rm->total_stock_qty / 1000, 2) }} Tons</div>
                        <div class="text-xs text-slate-500 num-tabular">({{ number_format($rm->total_stock_qty) }} {{ $rm->unit_of_measure }})</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 font-semibold text-slate-900 text-sm">Individual Paper Reel Rolls</div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase text-slate-500">
                            <th class="py-3 px-5">Reel #</th>
                            <th class="py-3 px-5">Paper Grade</th>
                            <th class="py-3 px-5">Paper Mill</th>
                            <th class="py-3 px-5 text-right">Remaining Weight</th>
                            <th class="py-3 px-5">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @foreach($paperReels as $reel)
                            <tr>
                                <td class="py-3.5 px-5 font-semibold text-blue-600">{{ $reel->reel_number }}</td>
                                <td class="py-3.5 px-5 text-slate-800">{{ $reel->rawMaterial->name ?? '-' }}</td>
                                <td class="py-3.5 px-5 text-slate-600">{{ $reel->supplier->name ?? '-' }}</td>
                                <td class="py-3.5 px-5 text-right font-semibold num-tabular">{{ number_format($reel->remaining_weight_kg) }} kg</td>
                                <td class="py-3.5 px-5">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $reel->status === 'IN_STOCK' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700' }}">
                                        {{ str_replace('_', ' ', $reel->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

@section('modals')
    <!-- Add Paper Reel Modal -->
    <div id="modal-add-reel" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-lg font-semibold text-slate-900">Add Paper Reel Roll</h3>
                <button onclick="closeModal('modal-add-reel')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('inventory.reels.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mill Reel Serial #</label>
                    <input type="text" name="reel_number" required placeholder="e.g. CP-REEL-9005" class="w-full rounded-lg border-slate-300 text-sm uppercase" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Paper Grade</label>
                    <select name="raw_material_id" required class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach($rawMaterials as $rm)
                            <option value="{{ $rm->id }}">{{ $rm->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier Mill</label>
                    <select name="supplier_id" class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach($suppliers as $supp)
                            <option value="{{ $supp->id }}">{{ $supp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Weight (KG)</label>
                    <input type="number" name="initial_weight_kg" min="10" step="0.5" required class="w-full rounded-lg border-slate-300 text-sm" />
                </div>
                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-add-reel')" class="px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-sm font-medium">Add Reel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Carton Spec Modal -->
    <div id="modal-add-carton" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-lg font-semibold text-slate-900">New Carton Specification</h3>
                <button onclick="closeModal('modal-add-carton')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('inventory.cartons.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Client Party</label>
                    <select name="client_id" required class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach($customers as $cust)
                            <option value="{{ $cust->id }}">{{ $cust->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Box Name / Title</label>
                    <input type="text" name="box_title" required placeholder="e.g. 5-Litre Cooking Oil Carton" class="w-full rounded-lg border-slate-300 text-sm" />
                </div>
                <div class="grid grid-cols-2 gap-4">
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
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Unit Rate (Rs./Pc)</label>
                        <input type="number" name="unit_selling_price" step="0.5" min="1" required class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Opening Stock (Pcs)</label>
                        <input type="number" name="stock_pieces" value="0" min="0" class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                </div>
                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-add-carton')" class="px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-sm font-medium">Save Box Spec</button>
                </div>
            </form>
        </div>
    </div>
@endsection

