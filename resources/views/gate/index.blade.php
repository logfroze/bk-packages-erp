@extends('layouts.app')

@section('title', 'Gate Register & Logistics')
@section('breadcrumb', 'Gate Register')

@section('header_actions')
    @if(!auth()->user()?->isReadOnly())
        <button onclick="openModal('modal-gate-entry')" class="px-4 py-2 rounded-lg bg-navy-900 text-white text-sm font-medium hover:bg-navy-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">add</span>
            <span>+ New Vehicle Entry</span>
        </button>
    @endif
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
                                <div class="inline-flex items-center gap-1.5">
                                    @if(!auth()->user()?->isReadOnly() && !in_array($g->status, ['EXITED', 'COMPLETED']))
                                        <button onclick="openWeighModal({{ $g->id }}, '{{ $g->gate_pass_number }}', '{{ $g->gross_weight_kg ?? '' }}', '{{ $g->tare_weight_kg ?? '' }}')"
                                                class="px-2.5 py-1.5 rounded-lg bg-navy-900 text-white text-xs font-medium hover:bg-navy-800">
                                            Weigh & Exit
                                        </button>
                                    @endif
                                    @if($g->receipt_photo_path)
                                        <button type="button" onclick="viewGateReceipt('{{ asset($g->receipt_photo_path) }}', 'Gate Pass: {{ $g->gate_pass_number }}', 'Vehicle: {{ $g->vehicle_number }} • {{ addslashes($g->party->name ?? 'Gate') }}')"
                                            class="px-2.5 py-1.5 rounded-lg border border-purple-200 bg-purple-50 text-purple-700 text-xs font-semibold hover:bg-purple-100 inline-flex items-center gap-1 shadow-xs">
                                            <span class="material-symbols-outlined text-[15px]">image</span>
                                            <span>Photo</span>
                                        </button>
                                    @endif
                                    @if($g->movement_type === 'OUTWARD_DISPATCH')
                                        <a href="{{ route('documents.delivery_challan', $g->id) }}" target="_blank"
                                           class="px-2 py-1.5 rounded-lg border border-blue-200 bg-blue-50 text-blue-700 text-xs font-semibold hover:bg-blue-100 inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[15px]">local_shipping</span>
                                            Challan
                                        </a>
                                    @endif
                                    <a href="{{ route('gate.slip', $g) }}" target="_blank"
                                       class="px-2 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-medium hover:bg-slate-50 inline-flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[15px]">print</span>
                                        Slip
                                    </a>
                                    @if(!auth()->user()?->isReadOnly())
                                        <button type="button" onclick="openEditGateModal({{ json_encode($g) }})"
                                                class="p-1.5 rounded-lg border border-slate-300 bg-white text-slate-600 hover:text-blue-600 hover:bg-slate-50 transition-colors shadow-xs" title="Edit Gate Entry">
                                            <span class="material-symbols-outlined text-[16px]">edit</span>
                                        </button>
                                        <form action="{{ route('gate.destroy', $g) }}" method="POST" onsubmit="return confirm('Delete gate pass {{ $g->gate_pass_number }} (Vehicle {{ $g->vehicle_number }})?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg border border-slate-300 bg-white text-slate-600 hover:text-red-600 hover:bg-slate-50 transition-colors shadow-xs" title="Delete Gate Entry">
                                                <span class="material-symbols-outlined text-[16px]">delete</span>
                                            </button>
                                        </form>
                                    @endif
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
            <form action="{{ route('gate.store') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                    <span class="text-slate-600">Auto Gate Pass #: <strong class="text-navy-900 font-mono">{{ $previewGatePass ?? \App\Models\DocumentSequence::previewNextNumber('GATE_PASS') }}</strong></span>
                    <input type="text" name="gate_pass_number" placeholder="Override # (optional)" class="w-40 rounded-md border-slate-300 text-xs py-1 uppercase font-mono" />
                </div>
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

                <!-- Option C: In-Form Media Photo Capture (Fail-safe single file input) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Bilti / Delivery Challan / Gate Photo (Optional)</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center justify-center gap-1.5 px-3 py-2 border-2 border-dashed border-blue-200 bg-blue-50/40 hover:bg-blue-50 rounded-xl cursor-pointer text-xs font-semibold text-blue-700 transition-colors">
                            <span class="material-symbols-outlined text-base">photo_camera</span>
                            <span>Camera Snap</span>
                            <input type="file" id="gate_entry_file_input" name="receipt_photo" accept="image/*" capture="environment" class="hidden" onchange="previewGatePhoto(this, 'gate-entry-preview')" />
                        </label>
                        <button type="button" onclick="const fi = document.getElementById('gate_entry_file_input'); fi.removeAttribute('capture'); fi.click();" class="flex items-center justify-center gap-1.5 px-3 py-2 border border-slate-200 bg-slate-50 hover:bg-slate-100 rounded-xl text-xs font-medium text-slate-700 transition-colors">
                            <span class="material-symbols-outlined text-base">upload_file</span>
                            <span>Upload File</span>
                        </button>
                    </div>
                    <div id="gate-entry-preview" class="hidden mt-2 text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-2 items-center justify-between">
                        <div class="flex items-center gap-1.5 truncate">
                            <span class="material-symbols-outlined text-sm text-emerald-600">check_circle</span>
                            <span class="preview-filename font-medium truncate"></span>
                        </div>
                        <button type="button" onclick="clearGatePhoto('gate-entry-preview')" class="text-red-500 hover:text-red-700 text-sm font-bold ml-2">&times;</button>
                    </div>
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
            <form id="weigh-form" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Gross Weight (KG - Loaded)</label>
                    <input type="number" step="0.1" name="gross_weight_kg" id="weigh-gross" required class="w-full rounded-lg border-slate-300 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tare Weight (KG - Empty Vehicle)</label>
                    <input type="number" step="0.1" name="tare_weight_kg" id="weigh-tare" required class="w-full rounded-lg border-slate-300 text-sm" />
                </div>

                <!-- Weighbridge Slip Camera Snap -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Weighbridge Scale Ticket Slip Photo</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center justify-center gap-1 px-2.5 py-1.5 border-2 border-dashed border-emerald-200 bg-emerald-50/40 hover:bg-emerald-50 rounded-xl cursor-pointer text-xs font-semibold text-emerald-700 transition-colors">
                            <span class="material-symbols-outlined text-sm">photo_camera</span>
                            <span>Snap Ticket</span>
                            <input type="file" id="weigh_file_input" name="receipt_photo" accept="image/*" capture="environment" class="hidden" onchange="previewGatePhoto(this, 'weigh-preview')" />
                        </label>
                        <button type="button" onclick="const fi = document.getElementById('weigh_file_input'); fi.removeAttribute('capture'); fi.click();" class="flex items-center justify-center gap-1 px-2.5 py-1.5 border border-slate-200 bg-slate-50 hover:bg-slate-100 rounded-xl text-xs font-medium text-slate-700 transition-colors">
                            <span class="material-symbols-outlined text-sm">upload_file</span>
                            <span>Upload File</span>
                        </button>
                    </div>
                    <div id="weigh-preview" class="hidden mt-2 text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-2 items-center justify-between">
                        <div class="flex items-center gap-1.5 truncate">
                            <span class="material-symbols-outlined text-sm text-emerald-600">check_circle</span>
                            <span class="preview-filename font-medium truncate"></span>
                        </div>
                        <button type="button" onclick="clearGatePhoto('weigh-preview')" class="text-red-500 hover:text-red-700 text-sm font-bold ml-2">&times;</button>
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-weigh')" class="px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium">Save & Mark Exited</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Vehicle Entry (Full Customization & Edit Ability) -->
    <div id="modal-edit-gate" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-xl w-full p-6 shadow-2xl my-8">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-600">edit_note</span>
                        <span>Edit Vehicle Gate Entry</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Update vehicle details, party, piece counts, or status</p>
                </div>
                <button onclick="closeModal('modal-edit-gate')" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="edit-gate-form" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Gate Pass #</label>
                        <input type="text" name="gate_pass_number" id="edit-gate-pass-number" required class="w-full rounded-lg border-slate-300 text-xs py-2 uppercase font-mono font-bold bg-slate-50" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Movement Type</label>
                        <select name="movement_type" id="edit-movement-type" class="w-full rounded-lg border-slate-300 text-xs py-2 font-semibold">
                            <option value="OUTWARD_DISPATCH">Outward (Loading Cartons)</option>
                            <option value="INWARD_RAW_MATERIAL">Inward (Unloading Paper Reels)</option>
                            <option value="GENERAL">General Movement</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Party / Mill Name</label>
                        <select name="party_id" id="edit-party-id" required class="w-full rounded-lg border-slate-300 text-xs py-2">
                            @foreach($parties as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
                        <select name="status" id="edit-status" class="w-full rounded-lg border-slate-300 text-xs py-2 font-semibold">
                            <option value="LOADING">LOADING (In Yard)</option>
                            <option value="UNLOADING">UNLOADING (In Yard)</option>
                            <option value="EXITED">EXITED (Completed Outward)</option>
                            <option value="COMPLETED">COMPLETED (Finished Inward)</option>
                            <option value="GENERAL">GENERAL</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Vehicle Number</label>
                        <input type="text" name="vehicle_number" id="edit-vehicle-number" required class="w-full rounded-lg border-slate-300 text-xs py-2 uppercase font-bold" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Vehicle Type</label>
                        <input type="text" name="vehicle_type" id="edit-vehicle-type" class="w-full rounded-lg border-slate-300 text-xs py-2" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Driver Name</label>
                        <input type="text" name="driver_name" id="edit-driver-name" required class="w-full rounded-lg border-slate-300 text-xs py-2" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Driver Phone</label>
                        <input type="text" name="driver_phone" id="edit-driver-phone" class="w-full rounded-lg border-slate-300 text-xs py-2 font-mono" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Carton Spec (Optional)</label>
                        <select name="carton_product_id" id="edit-carton-product-id" class="w-full rounded-lg border-slate-300 text-xs py-2">
                            <option value="">-- None / Custom Cargo --</option>
                            @foreach($cartons as $c)
                                <option value="{{ $c->id }}">{{ $c->box_title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Total Cartons (Pcs)</label>
                        <input type="number" name="total_pieces" id="edit-total-pieces" min="0" class="w-full rounded-lg border-slate-300 text-xs py-2" />
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Gross (KG)</label>
                        <input type="number" step="0.1" name="gross_weight_kg" id="edit-gross-weight" class="w-full rounded-lg border-slate-300 text-xs py-2" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tare (KG)</label>
                        <input type="number" step="0.1" name="tare_weight_kg" id="edit-tare-weight" class="w-full rounded-lg border-slate-300 text-xs py-2" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Bilti / Challan #</label>
                        <input type="text" name="bilti_challan_number" id="edit-bilti-challan" class="w-full rounded-lg border-slate-300 text-xs py-2 font-mono" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Material / Cargo Notes</label>
                    <input type="text" name="material_summary" id="edit-material-summary" class="w-full rounded-lg border-slate-300 text-xs py-2" />
                </div>

                <!-- Replace / Attach Photo -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Replace / Attach Gate Photo (Optional)</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center justify-center gap-1.5 px-3 py-2 border-2 border-dashed border-blue-200 bg-blue-50/40 hover:bg-blue-50 rounded-xl cursor-pointer text-xs font-semibold text-blue-700 transition-colors">
                            <span class="material-symbols-outlined text-base">photo_camera</span>
                            <span>Camera Snap</span>
                            <input type="file" id="edit_gate_file_input" name="receipt_photo" accept="image/*" capture="environment" class="hidden" onchange="previewGatePhoto(this, 'edit-gate-preview')" />
                        </label>
                        <button type="button" onclick="const fi = document.getElementById('edit_gate_file_input'); fi.removeAttribute('capture'); fi.click();" class="flex items-center justify-center gap-1.5 px-3 py-2 border border-slate-200 bg-slate-50 hover:bg-slate-100 rounded-xl text-xs font-medium text-slate-700 transition-colors">
                            <span class="material-symbols-outlined text-base">upload_file</span>
                            <span>Upload File</span>
                        </button>
                    </div>
                    <div id="edit-gate-preview" class="hidden mt-2 text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-2 items-center justify-between">
                        <div class="flex items-center gap-1.5 truncate">
                            <span class="material-symbols-outlined text-sm text-emerald-600">check_circle</span>
                            <span class="preview-filename font-medium truncate"></span>
                        </div>
                        <button type="button" onclick="clearGatePhoto('edit-gate-preview')" class="text-red-500 hover:text-red-700 text-sm font-bold ml-2">&times;</button>
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-edit-gate')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Gate Slip / Receipt Photo Viewer -->
    <div id="modal-gate-viewer" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-2xl w-full p-5 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                    </div>
                    <div>
                        <h3 id="gate-modal-title" class="text-sm font-bold text-slate-900">Gate Slip / Challan Document</h3>
                        <p id="gate-modal-subtitle" class="text-[11px] text-slate-500"></p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a id="gate-modal-download" href="#" target="_blank" download class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors" title="Download / Open Full">
                        <span class="material-symbols-outlined text-[18px]">open_in_new</span>
                    </a>
                    <button type="button" onclick="closeModal('modal-gate-viewer')" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>
            </div>
            <div class="p-4 bg-slate-950/5 rounded-xl my-3 flex items-center justify-center overflow-auto flex-1 min-h-[300px]">
                <img id="gate-modal-img" src="" alt="Gate Challan Photo" class="max-h-[60vh] max-w-full rounded-lg object-contain shadow-sm border border-slate-200" />
            </div>
            <div class="pt-2 flex justify-between items-center text-xs text-slate-500">
                <a href="{{ route('media_vault.index') }}" class="text-blue-600 hover:underline font-semibold flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">perm_media</span>
                    <span>View All Media in Central Vault</span>
                </a>
                <button type="button" onclick="closeModal('modal-gate-viewer')" class="px-4 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Close</button>
            </div>
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

    function previewGatePhoto(input, containerId) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const container = document.getElementById(containerId);
            if (container) {
                container.querySelector('.preview-filename').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
                container.classList.remove('hidden');
                container.classList.add('flex');
            }
        }
    }

    function clearGatePhoto(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            container.classList.add('hidden');
            container.classList.remove('flex');
            const form = container.closest('form');
            if (form) {
                const inputs = form.querySelectorAll('input[type="file"]');
                inputs.forEach(i => i.value = '');
            }
        }
    }

    function viewGateReceipt(imgUrl, title, subtitle) {
        document.getElementById('gate-modal-img').src = imgUrl;
        document.getElementById('gate-modal-title').textContent = title || 'Gate Pass Challan';
        document.getElementById('gate-modal-subtitle').textContent = subtitle || '';
        document.getElementById('gate-modal-download').href = imgUrl;
        openModal('modal-gate-viewer');
    }

    function openEditGateModal(g) {
        document.getElementById('edit-gate-form').action = '/gate-register/' + g.id;
        document.getElementById('edit-gate-pass-number').value = g.gate_pass_number || '';
        document.getElementById('edit-movement-type').value = g.movement_type || 'OUTWARD_DISPATCH';
        document.getElementById('edit-party-id').value = g.party_id || '';
        document.getElementById('edit-status').value = g.status || 'LOADING';
        document.getElementById('edit-vehicle-number').value = g.vehicle_number || '';
        document.getElementById('edit-vehicle-type').value = g.vehicle_type || 'Truck';
        document.getElementById('edit-driver-name').value = g.driver_name || '';
        document.getElementById('edit-driver-phone').value = g.driver_phone || '';
        document.getElementById('edit-carton-product-id').value = g.carton_product_id || '';
        document.getElementById('edit-total-pieces').value = g.total_pieces || '';
        document.getElementById('edit-gross-weight').value = g.gross_weight_kg || '';
        document.getElementById('edit-tare-weight').value = g.tare_weight_kg || '';
        document.getElementById('edit-bilti-challan').value = g.bilti_challan_number || '';
        document.getElementById('edit-material-summary').value = g.material_summary || '';
        openModal('modal-edit-gate');
    }
</script>
@endpush

