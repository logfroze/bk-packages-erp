@extends('layouts.app')

@section('title', 'Gate Register & Logistics')
@section('breadcrumb', 'Gate Register')

@section('header_actions')
    <button onclick="openModal('modal-gate-entry')" class="px-4 py-2 rounded-lg bg-navy-900 text-white text-sm font-medium hover:bg-navy-800 flex items-center gap-2">
        <span class="material-symbols-outlined text-[18px]">add</span>
        <span>+ New Vehicle Entry</span>
    </button>
@endsection

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Gate Register & Logistics</h1>
            <p class="text-sm text-slate-500 mt-1">Track incoming paper trucks, outward carton dispatches, and weighbridge slips</p>
        </div>

        <div class="inline-flex rounded-xl bg-white border border-slate-200 p-1">
            @foreach(['ALL' => 'All', 'OUTWARD' => 'Outward Loading', 'INWARD' => 'Inward Unloading', 'IN_YARD' => 'In Yard Now'] as $k => $v)
                <a href="{{ route('gate.index', ['filter' => $k]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $filter === $k ? 'bg-navy-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                    {{ $v }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Clean 4-Card Summary -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs font-semibold text-slate-500 uppercase">Movements Today</div>
            <div class="text-2xl font-bold text-slate-900 mt-1 num-tabular">{{ $stats['total_today'] }} Trucks</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs font-semibold text-slate-500 uppercase">Trucks in Yard</div>
            <div class="text-2xl font-bold text-amber-600 mt-1 num-tabular">{{ $stats['in_yard'] }} Active</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs font-semibold text-slate-500 uppercase">Paper Unloaded Today</div>
            <div class="text-2xl font-bold text-slate-900 mt-1 num-tabular">{{ $stats['reels_unloaded_tons'] }} Tons</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs font-semibold text-slate-500 uppercase">Cartons Dispatched</div>
            <div class="text-2xl font-bold text-emerald-600 mt-1 num-tabular">{{ number_format($stats['boxes_dispatched']) }} Pcs</div>
        </div>
    </div>

    <!-- Clean, Spacious Gate Log List -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-5">Gate Pass</th>
                        <th class="py-3.5 px-5">Vehicle & Driver</th>
                        <th class="py-3.5 px-5">Party & Cargo</th>
                        <th class="py-3.5 px-5 text-right">Weight / Qty</th>
                        <th class="py-3.5 px-5">Status</th>
                        <th class="py-3.5 px-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($entries as $g)
                        <tr class="hover:bg-slate-50/70">
                            <td class="py-4 px-5">
                                <span class="font-bold text-blue-600">{{ $g->gate_pass_number }}</span>
                                <div class="text-xs text-slate-400 mt-0.5">{{ $g->time_in->format('h:i A') }}</div>
                            </td>
                            <td class="py-4 px-5">
                                <div class="font-semibold text-slate-900">{{ $g->vehicle_number }} <span class="text-xs font-normal text-slate-500">({{ $g->vehicle_type }})</span></div>
                                <div class="text-xs text-slate-500">{{ $g->driver_name }} · {{ $g->driver_phone }}</div>
                            </td>
                            <td class="py-4 px-5">
                                <div class="font-medium text-slate-900">{{ $g->party->name ?? '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $g->material_summary ?? 'Standard Cargo' }}</div>
                            </td>
                            <td class="py-4 px-5 text-right num-tabular">
                                @if($g->total_pieces)
                                    <div class="font-bold text-slate-900">{{ number_format($g->total_pieces) }} Pcs</div>
                                @endif
                                @if($g->net_weight_kg)
                                    <div class="text-xs text-slate-500">Net: {{ number_format($g->net_weight_kg) }} kg</div>
                                @else
                                    <span class="text-xs text-amber-600 font-medium">Awaiting Scale</span>
                                @endif
                            </td>
                            <td class="py-4 px-5">
                                @if($g->movement_type === 'INWARD_RAW_MATERIAL')
                                    <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-700 mr-1">INWARD</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-50 text-blue-700 mr-1">OUTWARD</span>
                                @endif
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ in_array($g->status, ['EXITED', 'COMPLETED']) ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $g->status }}
                                </span>
                            </td>
                            <td class="py-4 px-5 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-2">
                                    @if(!in_array($g->status, ['EXITED', 'COMPLETED']))
                                        <button onclick="openWeighModal({{ $g->id }}, '{{ $g->gate_pass_number }}', '{{ $g->gross_weight_kg ?? '' }}', '{{ $g->tare_weight_kg ?? '' }}')"
                                                class="px-3 py-1.5 rounded-lg bg-navy-900 text-white text-xs font-medium hover:bg-navy-800">
                                            Weigh & Exit
                                        </button>
                                    @endif
                                    <a href="{{ route('gate.slip', $g) }}" target="_blank"
                                       class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-medium hover:bg-slate-50 inline-flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[15px]">print</span>
                                        Slip
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500">No vehicle gate movements found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('modals')
    <!-- Modal: New Vehicle Entry -->
    <div id="modal-gate-entry" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-xl">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-lg font-semibold text-slate-900">New Vehicle Gate Entry</h3>
                <button onclick="closeModal('modal-gate-entry')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('gate.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Movement Type</label>
                        <select name="movement_type" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="OUTWARD_DISPATCH">Outward (Loading Cartons)</option>
                            <option value="INWARD_RAW_MATERIAL">Inward (Unloading Paper Reels)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Party / Mill Name</label>
                        <select name="party_id" required class="w-full rounded-lg border-slate-300 text-sm">
                            @foreach($parties as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Vehicle Number</label>
                        <input type="text" name="vehicle_number" required placeholder="e.g. LES-8492" class="w-full rounded-lg border-slate-300 text-sm uppercase" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Vehicle Type</label>
                        <input type="text" name="vehicle_type" placeholder="Mazda / Shehzore / 10-Wheeler" class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Driver Name</label>
                        <input type="text" name="driver_name" required class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Driver Phone</label>
                        <input type="text" name="driver_phone" placeholder="0300-XXXXXXX" class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Carton Spec (If Outward)</label>
                        <select name="carton_product_id" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">-- Optional / Custom --</option>
                            @foreach($cartons as $c)
                                <option value="{{ $c->id }}">{{ $c->box_title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Total Cartons (Pcs)</label>
                        <input type="number" name="total_pieces" min="0" class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cargo / Material Notes</label>
                    <input type="text" name="material_summary" placeholder="e.g. 28 Rolls 150 GSM Kraft Paper" class="w-full rounded-lg border-slate-300 text-sm" />
                </div>
                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-gate-entry')" class="px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-sm font-medium">Log Vehicle Entry</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Weighbridge & Exit -->
    <div id="modal-weigh" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-sm w-full p-6 shadow-xl">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-lg font-semibold text-slate-900">Capture Weight (<span id="weigh-gp-label"></span>)</h3>
                <button onclick="closeModal('modal-weigh')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="weigh-form" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Gross Weight (KG - Loaded)</label>
                    <input type="number" step="0.1" name="gross_weight_kg" id="weigh-gross" required class="w-full rounded-lg border-slate-300 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tare Weight (KG - Empty Vehicle)</label>
                    <input type="number" step="0.1" name="tare_weight_kg" id="weigh-tare" required class="w-full rounded-lg border-slate-300 text-sm" />
                </div>
                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-weigh')" class="px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium">Save & Mark Exited</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function openWeighModal(id, gpNumber, gross, tare) {
        document.getElementById('weigh-gp-label').innerText = gpNumber;
        document.getElementById('weigh-gross').value = gross;
        document.getElementById('weigh-tare').value = tare;
        document.getElementById('weigh-form').action = '/gate-register/' + id + '/weigh-exit';
        openModal('modal-weigh');
    }
</script>
@endpush

