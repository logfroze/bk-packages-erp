@extends('layouts.app')

@section('title', 'Finishing & Conversion — Corrugation-2, Flexo, Slitter & Binding')
@section('breadcrumb', 'Finishing & Conversion')

@section('header_actions')
    @if(!auth()->user()?->isReadOnly())
        <div class="flex items-center gap-2 flex-wrap">
            <button onclick="openModal('modal-custom-card')" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50 shadow-xs flex items-center gap-1.5 transition-colors">
                <span class="material-symbols-outlined text-[16px] text-blue-600">table_chart</span>
                + Custom Table / Card
            </button>
            <button onclick="openModal('modal-new-batch')" class="px-3.5 py-1.5 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs flex items-center gap-1.5 transition-colors">
                <span class="material-symbols-outlined text-[16px]">add_circle</span>
                + Start Production Batch
            </button>
        </div>
    @endif
@endsection

@section('content')
<div class="space-y-6">

    <!-- Sub-Navigation Tabs inside Production -->
    <div class="border-b border-slate-200 flex items-center gap-6">
        <a href="{{ route('production.index') }}"
           class="pb-3 text-xs font-semibold border-b-2 border-blue-600 text-blue-600 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px]">precision_manufacturing</span>
            Finishing & Conversion Batches
        </a>
        <a href="{{ route('production.conversion') }}"
           class="pb-3 text-xs font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px]">timeline</span>
            Material Conversion Ledger (Reel → Roll → Sheet → Carton)
        </a>
    </div>

    <!-- Header & 4-Stage Finishing Pipeline Banner -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider bg-blue-100 text-blue-800 border border-blue-200">
                        Stage 2 & 3 Finishing
                    </span>
                    <span class="text-xs text-slate-500">Okara Carton Manufacturing Plant</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 mt-2">Finishing, Flexo Printing & Binding</h1>
                <p class="text-xs text-slate-500 mt-1 max-w-2xl">
                    Manage subsequent manufacturing stages after Nali/sheet corrugation: Sheet Pasting, Slitter/Thinning Blade, Flexo Printing, and Wire Stitching / Binding.
                </p>
            </div>

            <!-- Visual Finishing Pipeline Steps -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 shrink-0">
                <div class="p-3 rounded-xl bg-amber-50/70 border border-amber-200 text-center">
                    <div class="text-[10px] font-bold uppercase text-amber-800">Step 1</div>
                    <div class="text-xs font-bold text-amber-950 mt-0.5">Corrugation-2</div>
                    <div class="text-[10px] text-amber-700 mt-0.5">Sheet Pasting</div>
                </div>
                <div class="p-3 rounded-xl bg-blue-50/70 border border-blue-200 text-center">
                    <div class="text-[10px] font-bold uppercase text-blue-800">Step 2</div>
                    <div class="text-xs font-bold text-blue-950 mt-0.5">Thinning Blade</div>
                    <div class="text-[10px] text-blue-700 mt-0.5">Slider / Creasing</div>
                </div>
                <div class="p-3 rounded-xl bg-purple-50/70 border border-purple-200 text-center">
                    <div class="text-[10px] font-bold uppercase text-purple-800">Step 3</div>
                    <div class="text-xs font-bold text-purple-950 mt-0.5">Flexo Printer</div>
                    <div class="text-[10px] text-purple-700 mt-0.5">Printing & Slotting</div>
                </div>
                <div class="p-3 rounded-xl bg-emerald-50/70 border border-emerald-200 text-center">
                    <div class="text-[10px] font-bold uppercase text-emerald-800">Step 4</div>
                    <div class="text-xs font-bold text-emerald-950 mt-0.5">Binding & Wire</div>
                    <div class="text-[10px] text-emerald-700 mt-0.5">Stitching / Bundles</div>
                </div>
            </div>
        </div>
    </div>

    <!-- User-Defined Dynamic Custom Cards Section -->
    @if(isset($customCards) && $customCards->isNotEmpty())
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-600 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-blue-600">dashboard_customize</span>
                    Custom Configured Cards & Grids ({{ $customCards->count() }})
                </h2>
                <button onclick="openModal('modal-custom-card')" class="text-xs text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">add</span>
                    Add Another Custom Card
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($customCards as $card)
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between relative group hover:border-blue-300 transition-colors">
                        <div>
                            <div class="flex items-start justify-between gap-2 pb-3 border-b border-slate-100">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-bold text-slate-900 text-sm">{{ $card->title }}</h3>
                                        @if($card->badge_text)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                {{ $card->badge_text }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($card->subtitle)
                                        <p class="text-[11px] text-slate-500 mt-0.5">{{ $card->subtitle }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1">
                                    <button type="button" onclick="openEditProductionCardModal({{ json_encode($card) }})" class="p-1 rounded text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors" title="Edit card">
                                        <span class="material-symbols-outlined text-[16px]">edit</span>
                                    </button>
                                    <form action="{{ route('custom_cards.destroy', $card->id) }}" method="POST" onsubmit="return confirm('Delete this custom card?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 rounded text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Delete card">
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="pt-3">
                                @if(in_array($card->card_type, ['GRID_TABLE', 'DYNAMIC_GRID']) && is_array($card->grid_data))
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left text-xs">
                                            @if(is_array($card->column_headers) && count($card->column_headers) > 0)
                                                <thead>
                                                    <tr class="border-b border-slate-200 bg-slate-50 text-slate-600">
                                                        @foreach($card->column_headers as $hdr)
                                                            <th class="px-2.5 py-1.5 font-semibold text-[11px]">{{ $hdr }}</th>
                                                        @endforeach
                                                    </tr>
                                                </thead>
                                            @endif
                                            <tbody class="divide-y divide-slate-100 font-mono text-[11px]">
                                                @foreach($card->grid_data as $row)
                                                    <tr class="hover:bg-slate-50/50">
                                                        @foreach((array)$row as $cell)
                                                            <td class="px-2.5 py-1.5 text-slate-800">{{ $cell }}</td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @elseif(is_array($card->field_pairs))
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        @foreach($card->field_pairs as $label => $val)
                                            <div class="p-2 rounded-lg bg-slate-50 border border-slate-100">
                                                <div class="text-[10px] uppercase font-bold text-slate-400">{{ $label }}</div>
                                                <div class="font-bold text-slate-800 text-xs mt-0.5 font-mono">{{ $val }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="pt-3 mt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                            <span>Type: {{ str_replace('_', ' ', $card->card_type) }}</span>
                            <span>{{ $card->created_at?->diffForHumans() }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Batches Filter & List Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-base font-bold text-slate-900">Active Production Batches</h2>
            <p class="text-xs text-slate-500 mt-0.5">Filter by batch status and add direct production output to inventory</p>
        </div>

        <!-- Status Filter Tabs -->
        <div class="inline-flex rounded-xl bg-white border border-slate-200 p-1">
            @foreach(['ALL' => 'All', 'RUNNING' => 'Running', 'QUEUED' => 'In Queue', 'COMPLETED' => 'Completed'] as $key => $label)
                <a href="{{ route('production.index', ['status' => $key]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ $statusFilter === $key ? 'bg-navy-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Production Batches Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($batches as $batch)
            @php
                $pct = $batch->target_quantity > 0 ? min(100, round(($batch->produced_quantity / $batch->target_quantity) * 100)) : 0;
                $badgeClass = match($batch->status) {
                    'RUNNING' => 'bg-blue-50 text-blue-700 border-blue-200',
                    'COMPLETED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    default => 'bg-amber-50 text-amber-700 border-amber-200',
                };
                $machineLabel = match($batch->current_machine) {
                    'PASTING_CORRUGATION_2', 'CORRUGATION_LINE_2' => 'Corrugation-2 (Pasting)',
                    'THINNING_SLITTER' => 'Slider / Thinning Blade',
                    'FLEXO_PRINTER', 'FLEXO_PRINT_SLOTTER' => 'Flexo Printing & Slotter',
                    'WIRE_STITCHING_BINDING', 'AUTO_STITCHER' => 'Wire Stitching & Binding',
                    default => str_replace('_', ' ', $batch->current_machine),
                };
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200 p-6 flex flex-col justify-between gap-5 shadow-xs hover:border-slate-300 transition-colors">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-blue-600 font-mono">{{ $batch->batch_number }}</span>
                            <button type="button" onclick="openEditBatchModal({{ json_encode($batch) }})" class="p-1 text-slate-400 hover:text-blue-600 rounded hover:bg-slate-100 transition-colors" title="Edit Batch">
                                <span class="material-symbols-outlined text-[15px]">edit</span>
                            </button>
                            <form action="{{ route('production.destroy', $batch->id) }}" method="POST" onsubmit="return confirm('Delete production batch {{ $batch->batch_number }}?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1 text-slate-400 hover:text-red-600 rounded hover:bg-red-50 transition-colors" title="Delete Batch">
                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                </button>
                            </form>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $badgeClass }}">
                            {{ $batch->status }}
                        </span>
                    </div>

                    <h3 class="text-base font-semibold text-slate-900">{{ $batch->cartonProduct->box_title ?? 'Custom Carton' }}</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $batch->client->name ?? 'General Order' }}</p>

                    <div class="mt-4 flex flex-wrap gap-2 text-xs text-slate-600">
                        <span class="px-2 py-1 rounded bg-slate-100 font-medium">{{ $batch->cartonProduct->ply_type ?? '5-PLY' }}</span>
                        <span class="px-2 py-1 rounded bg-blue-50 text-blue-800 font-semibold border border-blue-200">{{ $machineLabel }}</span>
                        <span class="px-2 py-1 rounded bg-slate-100 text-slate-700">{{ str_replace('_', ' ', $batch->shift) }}</span>
                    </div>

                    <!-- Custom Fields (Dynamic Client Details) -->
                    @if(is_array($batch->custom_fields) && count($batch->custom_fields) > 0)
                        <div class="mt-3 pt-3 border-t border-slate-100">
                            <div class="text-[10px] font-bold uppercase text-slate-400 mb-1.5">Custom Specifications</div>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($batch->custom_fields as $ck => $cv)
                                    <span class="px-2 py-0.5 rounded bg-slate-50 border border-slate-200 text-[11px] text-slate-700 font-mono">
                                        <strong>{{ $ck }}:</strong> {{ $cv }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Custom Table Grid (Dynamic Matrix) -->
                    @if(is_array($batch->custom_grid) && count($batch->custom_grid) > 0)
                        <div class="mt-3 pt-3 border-t border-slate-100">
                            <div class="text-[10px] font-bold uppercase text-slate-400 mb-1">Custom Data Matrix</div>
                            <div class="overflow-x-auto max-h-36">
                                <table class="w-full text-[10px] font-mono">
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($batch->custom_grid as $r)
                                            <tr>
                                                @foreach((array)$r as $c)
                                                    <td class="p-1 text-slate-600 bg-slate-50 border border-slate-100">{{ $c }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>

                <div>
                    <div class="flex items-center justify-between text-xs mb-1.5">
                        <span class="text-slate-500 font-medium">Output Progress</span>
                        <span class="font-semibold text-slate-900 num-tabular">{{ number_format($batch->produced_quantity) }} / {{ number_format($batch->target_quantity) }} Pcs ({{ $pct }}%)</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: {{ $pct }}%"></div>
                    </div>

                    @if($batch->status !== 'COMPLETED')
                        <form action="{{ route('production.output', $batch) }}" method="POST" class="mt-4 pt-4 border-t border-slate-100 flex items-center gap-2">
                            @csrf
                            <input type="number" name="add_pieces" placeholder="+ Output Pcs" min="1" required
                                   class="flex-1 rounded-lg border-slate-300 text-xs py-2" />
                            <button type="submit" class="px-3.5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shrink-0 shadow-xs">
                                Add Output
                            </button>
                        </form>
                    @else
                        <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-emerald-700 font-medium flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">check_circle</span>
                            Finished & Added to Godown Stock
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-500">
                <span class="material-symbols-outlined text-4xl text-slate-400 mb-2">precision_manufacturing</span>
                <p class="font-semibold text-slate-700">No production batches found for this filter.</p>
                <p class="text-xs text-slate-400 mt-1">Click "+ Start Production Batch" above to log a new finishing run.</p>
            </div>
        @endforelse
    </div>

</div>
@endsection

@section('modals')

    <!-- Modal 1: + Start New Production Batch (with Dynamic Fields & Grid Builders) -->
    <div id="modal-new-batch" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-2xl w-full p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Start New Production Batch</h3>
                    <p class="text-xs text-slate-500">Configure machine stage, target quantities, and custom specifications</p>
                </div>
                <button onclick="closeModal('modal-new-batch')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form action="{{ route('production.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf

                <!-- Carton Product Selection -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Carton Design / Product <span class="text-red-500">*</span></label>
                    <select name="carton_product_id" required class="w-full rounded-lg border-slate-300 text-xs py-2 font-medium">
                        @foreach($cartonProducts as $carton)
                            <option value="{{ $carton->id }}">{{ $carton->box_title }} — {{ $carton->client->name ?? 'General' }} ({{ $carton->dimensions ?? 'Standard' }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Finishing Stage Machine Line -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Finishing Stage Machine <span class="text-red-500">*</span></label>
                        <select name="current_machine" required class="w-full rounded-lg border-slate-300 text-xs py-2">
                            <option value="PASTING_CORRUGATION_2">1. Corrugation-2 (Sheet Pasting Machine)</option>
                            <option value="THINNING_SLITTER">2. Slider / Thinning Blade (Creaser & Slitter)</option>
                            <option value="FLEXO_PRINTER">3. Flexo Printer & Slotter</option>
                            <option value="WIRE_STITCHING_BINDING">4. Binding & Wire Stitching / Bundling</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Work Shift <span class="text-red-500">*</span></label>
                        <select name="shift" required class="w-full rounded-lg border-slate-300 text-xs py-2">
                            <option value="SHIFT_A_DAY">Day Shift (08:00 AM - 08:00 PM)</option>
                            <option value="SHIFT_B_NIGHT">Night Shift (08:00 PM - 08:00 AM)</option>
                        </select>
                    </div>
                </div>

                <!-- Target Qty & Already Produced -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Target Quantity (Pcs) <span class="text-red-500">*</span></label>
                        <input type="number" name="target_quantity" value="5000" min="1" required class="w-full rounded-lg border-slate-300 text-xs py-2 font-mono" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Already Made Pcs (Optional)</label>
                        <input type="number" name="produced_quantity" value="0" min="0" class="w-full rounded-lg border-slate-300 text-xs py-2 font-mono" />
                    </div>
                </div>

                <!-- Due Date -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Target Completion Date</label>
                    <input type="date" name="due_date" value="{{ date('Y-m-d', strtotime('+1 day')) }}" class="w-full rounded-lg border-slate-300 text-xs py-2 font-mono" />
                </div>

                <!-- CLIENT CUSTOMIZATION: Dynamic Custom Field Rows -->
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <span class="text-xs font-bold text-slate-800">Dynamic Custom Fields</span>
                            <p class="text-[11px] text-slate-500">Add any custom parameter (e.g. Ink Color, Flute Type, Blade Setting, Wire Gauge)</p>
                        </div>
                        <button type="button" onclick="addBatchCustomFieldRow()" class="px-2.5 py-1 rounded bg-white border border-slate-300 text-slate-700 text-xs font-semibold hover:bg-slate-100 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">add</span>
                            + Add Field
                        </button>
                    </div>
                    <div id="batch-custom-fields-container" class="space-y-2">
                        <!-- Dynamic rows injected here -->
                    </div>
                </div>

                <!-- CLIENT CUSTOMIZATION: Dynamic Custom Grid Matrix -->
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <span class="text-xs font-bold text-slate-800">Dynamic Custom Table Grid</span>
                            <p class="text-[11px] text-slate-500">Build an arbitrary table matrix (e.g. 3 rows × 4 columns)</p>
                        </div>
                        <button type="button" onclick="toggleBatchGridBuilder()" class="px-2.5 py-1 rounded bg-white border border-slate-300 text-slate-700 text-xs font-semibold hover:bg-slate-100 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">grid_on</span>
                            <span id="btn-batch-grid-text">Build Table Grid</span>
                        </button>
                    </div>

                    <div id="batch-grid-builder" class="hidden space-y-3 pt-2">
                        <div class="flex items-center gap-3">
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-600 mb-0.5">Rows</label>
                                <input type="number" id="batch_grid_rows" value="3" min="1" max="20" class="w-16 rounded border-slate-300 text-xs py-1 px-2 font-mono" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-600 mb-0.5">Columns</label>
                                <input type="number" id="batch_grid_cols" value="3" min="1" max="10" class="w-16 rounded border-slate-300 text-xs py-1 px-2 font-mono" />
                            </div>
                            <button type="button" onclick="generateBatchGridMatrix()" class="mt-4 px-3 py-1 rounded bg-navy-900 text-white text-xs font-semibold">Generate Matrix</button>
                        </div>
                        <div id="batch-grid-table-mount" class="overflow-x-auto max-h-48"></div>
                        <input type="hidden" name="custom_grid_json" id="batch_grid_json" />
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-new-batch')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs">Start Production Batch</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Production Batch (Full Customization & Adjustment) -->
    <div id="modal-edit-batch" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Edit Production Batch</h3>
                    <p id="edit-batch-number-title" class="text-xs text-blue-600 font-mono font-bold mt-0.5"></p>
                </div>
                <button onclick="closeModal('modal-edit-batch')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form id="form-edit-batch" method="POST" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Target Box Design *</label>
                    <select id="edit-batch-product" name="carton_product_id" required class="w-full rounded-lg border-slate-300 text-xs py-2">
                        @foreach($cartonProducts as $cp)
                            <option value="{{ $cp->id }}">{{ $cp->box_title }} ({{ $cp->client->name ?? 'General' }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Shift *</label>
                        <select id="edit-batch-shift" name="shift" required class="w-full rounded-lg border-slate-300 text-xs py-2">
                            <option value="DAY_SHIFT">Day Shift (08:00 AM - 08:00 PM)</option>
                            <option value="NIGHT_SHIFT">Night Shift (08:00 PM - 08:00 AM)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Machine Line *</label>
                        <select id="edit-batch-machine" name="current_machine" required class="w-full rounded-lg border-slate-300 text-xs py-2">
                            <option value="SINGLE_FACER_CORRUGATOR">Single Facer Corrugator (Nali)</option>
                            <option value="THINNING_SLITTER">Slider / Thinning Blade</option>
                            <option value="FLEXO_PRINTER">Flexo Printing & Slotter</option>
                            <option value="WIRE_STITCHING_BINDING">Wire Stitching & Binding</option>
                            <option value="MANUAL_PASTING">Manual Sheet Pasting</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Target Quantity (Pcs) *</label>
                        <input type="number" id="edit-batch-target" name="target_quantity" min="1" required class="w-full rounded-lg border-slate-300 text-xs py-2 font-mono font-bold" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Produced So Far (Pcs) *</label>
                        <input type="number" id="edit-batch-produced" name="produced_quantity" min="0" required class="w-full rounded-lg border-slate-300 text-xs py-2 font-mono font-bold" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Status *</label>
                        <select id="edit-batch-status" name="status" required class="w-full rounded-lg border-slate-300 text-xs py-2 font-semibold">
                            <option value="RUNNING">RUNNING (IN PROCESS)</option>
                            <option value="COMPLETED">COMPLETED (FINISHED)</option>
                            <option value="PAUSED">PAUSED (MAINTENANCE)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Target Due Date</label>
                        <input type="date" id="edit-batch-due" name="due_date" class="w-full rounded-lg border-slate-300 text-xs py-2" />
                    </div>
                </div>

                <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-edit-batch')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Cancel</button>
                    <button type="submit" class="btn-solid-primary px-5 py-2 rounded-lg text-xs font-semibold shadow-xs">Update Batch</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: + Custom Card / Table Builder (Full Dynamic Client Customization) -->
    <div id="modal-custom-card" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-2xl w-full p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Create Custom ERP Card / Table</h3>
                    <p class="text-xs text-slate-500">Design any custom card with custom rows, columns, or field pairs</p>
                </div>
                <button onclick="closeModal('modal-custom-card')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form action="{{ route('custom_cards.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="page_key" value="production" />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Card Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" required placeholder="e.g. Slitter Blade Gap Table" class="w-full rounded-lg border-slate-300 text-xs py-2 font-medium" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Subtitle / Note</label>
                        <input type="text" name="subtitle" placeholder="e.g. Knife speeds & flute gauges" class="w-full rounded-lg border-slate-300 text-xs py-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Card Type <span class="text-red-500">*</span></label>
                        <select name="card_type" id="card_type_select" onchange="toggleCardTypeUi()" class="w-full rounded-lg border-slate-300 text-xs py-2 font-semibold">
                            <option value="GRID_TABLE">Custom Grid / Table (N Rows × M Columns)</option>
                            <option value="FIELD_PAIRS">Custom Key-Value Field Card</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Badge Tag</label>
                        <input type="text" name="badge_text" placeholder="e.g. MACHINE 2" class="w-full rounded-lg border-slate-300 text-xs py-2 uppercase font-mono" />
                    </div>
                </div>

                <!-- Type 1: Dynamic Table Matrix Builder -->
                <div id="grid-table-ui" class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                    <div class="text-xs font-bold text-slate-800">Grid Dimensions (Rows × Columns)</div>
                    <div class="flex items-center gap-3">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 mb-0.5">Rows</label>
                            <input type="number" id="gen_rows" value="3" min="1" max="30" class="w-20 rounded border-slate-300 text-xs py-1 px-2 font-mono" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 mb-0.5">Columns</label>
                            <input type="number" id="gen_cols" value="4" min="1" max="10" class="w-20 rounded border-slate-300 text-xs py-1 px-2 font-mono" />
                        </div>
                        <button type="button" onclick="buildCardMatrix()" class="mt-4 px-3.5 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700">
                            Build Editable Table
                        </button>
                    </div>

                    <div id="card-matrix-mount" class="overflow-x-auto max-h-56 mt-2"></div>
                    <input type="hidden" name="column_headers" id="card_col_headers_json" />
                    <input type="hidden" name="grid_data" id="card_grid_data_json" />
                    <input type="hidden" name="rows_count" id="card_rows_count" value="3" />
                    <input type="hidden" name="columns_count" id="card_cols_count" value="4" />
                </div>

                <!-- Type 2: Dynamic Field Pairs Builder -->
                <div id="field-pairs-ui" class="hidden p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-800">Key-Value Pairs</span>
                        <button type="button" onclick="addCardFieldRow()" class="px-2.5 py-1 rounded bg-white border border-slate-300 text-slate-700 text-xs font-semibold hover:bg-slate-100 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">add</span>
                            + Add Field
                        </button>
                    </div>
                    <div id="card-fields-mount" class="space-y-2"></div>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-custom-card')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" onclick="prepareCustomCardSubmit()" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs">Save Custom Card</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Custom ERP Card (Production) -->
    <div id="modal-edit-custom-card-prod" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-3xl w-full p-6 shadow-2xl max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]">edit</span>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Edit Custom ERP Card / Table</h3>
                        <p class="text-xs text-slate-500">Edit titles, custom columns, rows, or key-value fields</p>
                    </div>
                </div>
                <button onclick="closeModal('modal-edit-custom-card-prod')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form id="edit-prod-card-form" action="" method="POST" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Card Title <span class="text-red-500">*</span></label>
                        <input type="text" id="edit_prod_card_title" name="title" required class="w-full rounded-lg border-slate-300 text-xs py-2 font-medium" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Subtitle / Note</label>
                        <input type="text" id="edit_prod_card_subtitle" name="subtitle" class="w-full rounded-lg border-slate-300 text-xs py-2" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Badge Tag</label>
                    <input type="text" id="edit_prod_card_badge" name="badge_text" class="w-full rounded-lg border-slate-300 text-xs py-2 uppercase font-mono" />
                </div>

                <!-- Grid Table Editor Section -->
                <div id="edit-prod-grid-ui" class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-800">Table Columns & Rows</span>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="addEditProdGridCol()" class="px-2.5 py-1 rounded bg-slate-200 hover:bg-slate-300 text-slate-700 text-[11px] font-semibold flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">view_column</span>
                                + Column
                            </button>
                            <button type="button" onclick="addEditProdGridRow()" class="px-2.5 py-1 rounded bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-semibold flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">add</span>
                                + Row
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto max-h-60 border border-slate-200 rounded-lg p-2 bg-white">
                        <table class="w-full text-xs text-left" id="edit-prod-card-table">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr id="edit-prod-headers-row"></tr>
                            </thead>
                            <tbody id="edit-prod-rows-tbody" class="divide-y divide-slate-100"></tbody>
                        </table>
                    </div>
                    <input type="hidden" id="edit_prod_rows_count" name="rows_count" value="1" />
                    <input type="hidden" id="edit_prod_columns_count" name="columns_count" value="1" />
                </div>

                <!-- Field Pairs Editor Section -->
                <div id="edit-prod-pairs-ui" class="hidden p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-800">Key-Value Pairs</span>
                        <button type="button" onclick="addEditProdFieldPair()" class="px-2.5 py-1 rounded bg-white border border-slate-300 text-slate-700 text-xs font-semibold hover:bg-slate-100 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">add</span>
                            + Add Field
                        </button>
                    </div>
                    <div id="edit-prod-pairs-mount" class="space-y-2"></div>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-edit-custom-card-prod')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Dynamic Custom Field Rows for New Production Batch
    function addBatchCustomFieldRow() {
        const container = document.getElementById('batch-custom-fields-container');
        const rowId = 'bcf_' + Date.now();
        const div = document.createElement('div');
        div.id = rowId;
        div.className = 'flex items-center gap-2';
        div.innerHTML = `
            <input type="text" name="custom_keys[]" placeholder="Field Name (e.g. Ink Shade / Wire Gauge)" class="flex-1 rounded border-slate-300 text-xs py-1.5 px-2.5 font-medium" />
            <input type="text" name="custom_values[]" placeholder="Value (e.g. Reflex Blue / 22 SWG)" class="flex-1 rounded border-slate-300 text-xs py-1.5 px-2.5 font-mono" />
            <button type="button" onclick="document.getElementById('${rowId}').remove()" class="p-1.5 text-slate-400 hover:text-red-600 rounded">
                <span class="material-symbols-outlined text-[16px]">close</span>
            </button>
        `;
        container.appendChild(div);
    }

    // Toggle Dynamic Grid Builder inside Production Batch Modal
    function toggleBatchGridBuilder() {
        const builder = document.getElementById('batch-grid-builder');
        const btnText = document.getElementById('btn-batch-grid-text');
        if (builder.classList.contains('hidden')) {
            builder.classList.remove('hidden');
            btnText.innerText = 'Hide Table Grid';
            generateBatchGridMatrix();
        } else {
            builder.classList.add('hidden');
            btnText.innerText = 'Build Table Grid';
        }
    }

    // Generate Editable Matrix Table inside Production Batch Modal
    function generateBatchGridMatrix() {
        const rows = parseInt(document.getElementById('batch_grid_rows').value) || 3;
        const cols = parseInt(document.getElementById('batch_grid_cols').value) || 3;
        const mount = document.getElementById('batch-grid-table-mount');

        let html = '<table class="w-full text-xs border border-slate-200 divide-y divide-slate-200 mt-2"><tbody class="divide-y divide-slate-100">';
        for (let r = 0; r < rows; r++) {
            html += '<tr>';
            for (let c = 0; c < cols; c++) {
                html += `<td class="p-1"><input type="text" class="batch-grid-cell w-full rounded border-slate-200 text-xs py-1 px-1.5 font-mono" data-row="${r}" data-col="${c}" placeholder="R${r+1}C${c+1}" oninput="syncBatchGridJson()" /></td>`;
            }
            html += '</tr>';
        }
        html += '</tbody></table>';
        mount.innerHTML = html;
        syncBatchGridJson();
    }

    function syncBatchGridJson() {
        const rows = parseInt(document.getElementById('batch_grid_rows').value) || 3;
        const cols = parseInt(document.getElementById('batch_grid_cols').value) || 3;
        const matrix = [];
        for (let r = 0; r < rows; r++) {
            const rowData = [];
            for (let c = 0; c < cols; c++) {
                const input = document.querySelector(`.batch-grid-cell[data-row="${r}"][data-col="${c}"]`);
                rowData.push(input ? input.value : '');
            }
            matrix.push(rowData);
        }
        document.getElementById('batch_grid_json').value = JSON.stringify(matrix);
    }

    // Custom Card Modal Logic
    function toggleCardTypeUi() {
        const type = document.getElementById('card_type_select').value;
        if (type === 'GRID_TABLE') {
            document.getElementById('grid-table-ui').classList.remove('hidden');
            document.getElementById('field-pairs-ui').classList.add('hidden');
        } else {
            document.getElementById('grid-table-ui').classList.add('hidden');
            document.getElementById('field-pairs-ui').classList.remove('hidden');
            if (document.getElementById('card-fields-mount').children.length === 0) {
                addCardFieldRow();
                addCardFieldRow();
            }
        }
    }

    function buildCardMatrix() {
        const rows = parseInt(document.getElementById('gen_rows').value) || 3;
        const cols = parseInt(document.getElementById('gen_cols').value) || 4;
        const mount = document.getElementById('card-matrix-mount');

        document.getElementById('card_rows_count').value = rows;
        document.getElementById('card_cols_count').value = cols;

        let html = '<table class="w-full text-xs border border-slate-200">';
        html += '<thead><tr class="bg-slate-100">';
        for (let c = 0; c < cols; c++) {
            html += `<th class="p-1.5"><input type="text" class="card-col-header w-full rounded border-slate-300 text-xs font-bold py-1 px-1.5" placeholder="Col ${c+1}" data-col="${c}" /></th>`;
        }
        html += '</tr></thead><tbody class="divide-y divide-slate-100">';
        for (let r = 0; r < rows; r++) {
            html += '<tr>';
            for (let c = 0; c < cols; c++) {
                html += `<td class="p-1"><input type="text" class="card-grid-input w-full rounded border-slate-200 text-xs py-1 px-1.5 font-mono" data-row="${r}" data-col="${c}" placeholder="..." /></td>`;
            }
            html += '</tr>';
        }
        html += '</tbody></table>';
        mount.innerHTML = html;
    }

    function addCardFieldRow() {
        const mount = document.getElementById('card-fields-mount');
        const rowId = 'cfr_' + Date.now();
        const div = document.createElement('div');
        div.id = rowId;
        div.className = 'flex items-center gap-2';
        div.innerHTML = `
            <input type="text" name="field_labels[]" placeholder="Parameter (e.g. Blade Angle / Speed)" class="flex-1 rounded border-slate-300 text-xs py-1.5 px-2.5 font-medium" />
            <input type="text" name="field_values[]" placeholder="Value (e.g. 45 Deg / 120 m/min)" class="flex-1 rounded border-slate-300 text-xs py-1.5 px-2.5 font-mono" />
            <button type="button" onclick="document.getElementById('${rowId}').remove()" class="p-1.5 text-slate-400 hover:text-red-600 rounded">
                <span class="material-symbols-outlined text-[16px]">close</span>
            </button>
        `;
        mount.appendChild(div);
    }

    function prepareCustomCardSubmit() {
        const type = document.getElementById('card_type_select').value;
        if (type === 'GRID_TABLE') {
            const rows = parseInt(document.getElementById('card_rows_count').value) || 3;
            const cols = parseInt(document.getElementById('card_cols_count').value) || 4;

            const headers = [];
            document.querySelectorAll('.card-col-header').forEach((inp, idx) => {
                headers.push(inp.value.trim() || `Col ${idx+1}`);
            });
            document.getElementById('card_col_headers_json').value = JSON.stringify(headers);

            const grid = [];
            for (let r = 0; r < rows; r++) {
                const rData = [];
                for (let c = 0; c < cols; c++) {
                    const cell = document.querySelector(`.card-grid-input[data-row="${r}"][data-col="${c}"]`);
                    rData.push(cell ? cell.value : '');
                }
                grid.push(rData);
            }
            document.getElementById('card_grid_data_json').value = JSON.stringify(grid);
        }
    }

    function openEditBatchModal(b) {
        document.getElementById('form-edit-batch').action = '/production/' + b.id;
        document.getElementById('edit-batch-number-title').textContent = b.batch_number;
        document.getElementById('edit-batch-product').value = b.carton_product_id;
        document.getElementById('edit-batch-shift').value = b.shift;
        document.getElementById('edit-batch-machine').value = b.current_machine;
        document.getElementById('edit-batch-target').value = b.target_quantity;
        document.getElementById('edit-batch-produced').value = b.produced_quantity || 0;
        document.getElementById('edit-batch-status').value = b.status || 'RUNNING';
        let dDate = b.due_date ? b.due_date.substring(0, 10) : '';
        document.getElementById('edit-batch-due').value = dDate;
        openModal('modal-edit-batch');
    }

    // Initialize Card matrix on load
    document.addEventListener('DOMContentLoaded', () => {
        buildCardMatrix();
    });

    let currentEditProdCols = 0;

    function openEditProductionCardModal(card) {
        document.getElementById('edit-prod-card-form').action = '/custom-cards/' + card.id;
        document.getElementById('edit_prod_card_title').value = card.title || '';
        document.getElementById('edit_prod_card_subtitle').value = card.subtitle || '';
        document.getElementById('edit_prod_card_badge').value = card.badge_text || '';

        const gridUi = document.getElementById('edit-prod-grid-ui');
        const pairsUi = document.getElementById('edit-prod-pairs-ui');

        if (card.card_type === 'FIELD_PAIRS' || card.card_type === 'KEY_VALUE' || (card.field_pairs && Object.keys(card.field_pairs).length > 0)) {
            gridUi.classList.add('hidden');
            pairsUi.classList.remove('hidden');
            renderEditProdPairs(card.field_pairs || {});
        } else {
            pairsUi.classList.add('hidden');
            gridUi.classList.remove('hidden');
            const headers = Array.isArray(card.column_headers) && card.column_headers.length > 0
                ? card.column_headers
                : ['Col 1', 'Col 2', 'Col 3'];
            const rows = Array.isArray(card.grid_data) && card.grid_data.length > 0
                ? card.grid_data
                : [['', '', '']];
            renderEditProdGrid(headers, rows);
        }

        openModal('modal-edit-custom-card-prod');
    }

    function renderEditProdGrid(headers, rows) {
        currentEditProdCols = headers.length;

        const trHead = document.getElementById('edit-prod-headers-row');
        trHead.innerHTML = '';
        headers.forEach((h, cIdx) => {
            const th = document.createElement('th');
            th.className = 'p-1.5 font-semibold text-slate-700';
            th.innerHTML = `<input type="text" name="column_headers[]" value="${(h || '').replace(/"/g, '&quot;')}" class="w-full rounded border-slate-300 text-xs py-1 px-1.5 font-semibold" placeholder="Header ${cIdx + 1}" />`;
            trHead.appendChild(th);
        });
        const thAct = document.createElement('th');
        thAct.className = 'p-1.5 w-10 text-center text-slate-400';
        thAct.innerHTML = '';
        trHead.appendChild(thAct);

        const tbody = document.getElementById('edit-prod-rows-tbody');
        tbody.innerHTML = '';
        rows.forEach((rData, rIdx) => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50 edit-prod-row';
            for (let c = 0; c < currentEditProdCols; c++) {
                const td = document.createElement('td');
                td.className = 'p-1';
                const cellVal = (Array.isArray(rData) && rData[c] !== undefined) ? rData[c] : '';
                td.innerHTML = `<input type="text" name="grid_data[${rIdx}][${c}]" value="${(cellVal || '').toString().replace(/"/g, '&quot;')}" class="w-full rounded border-slate-200 text-xs py-1 px-1.5" />`;
                tr.appendChild(td);
            }
            const tdAct = document.createElement('td');
            tdAct.className = 'p-1 text-center';
            tdAct.innerHTML = `<button type="button" onclick="this.closest('tr').remove(); reindexEditProdRows();" class="text-slate-300 hover:text-red-600 p-1" title="Delete Row"><span class="material-symbols-outlined text-[16px]">delete</span></button>`;
            tr.appendChild(tdAct);
            tbody.appendChild(tr);
        });

        updateEditProdCounts();
    }

    function addEditProdGridRow() {
        const tbody = document.getElementById('edit-prod-rows-tbody');
        const rIdx = tbody.querySelectorAll('tr').length;
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50 edit-prod-row';
        for (let c = 0; c < currentEditProdCols; c++) {
            const td = document.createElement('td');
            td.className = 'p-1';
            td.innerHTML = `<input type="text" name="grid_data[${rIdx}][${c}]" value="" class="w-full rounded border-slate-200 text-xs py-1 px-1.5" />`;
            tr.appendChild(td);
        }
        const tdAct = document.createElement('td');
        tdAct.className = 'p-1 text-center';
        tdAct.innerHTML = `<button type="button" onclick="this.closest('tr').remove(); reindexEditProdRows();" class="text-slate-300 hover:text-red-600 p-1" title="Delete Row"><span class="material-symbols-outlined text-[16px]">delete</span></button>`;
        tr.appendChild(tdAct);
        tbody.appendChild(tr);
        updateEditProdCounts();
    }

    function addEditProdGridCol() {
        currentEditProdCols++;
        const trHead = document.getElementById('edit-prod-headers-row');
        const thAct = trHead.lastElementChild;
        const newTh = document.createElement('th');
        newTh.className = 'p-1.5 font-semibold text-slate-700';
        newTh.innerHTML = `<input type="text" name="column_headers[]" value="Col ${currentEditProdCols}" class="w-full rounded border-slate-300 text-xs py-1 px-1.5 font-semibold" />`;
        trHead.insertBefore(newTh, thAct);

        const rows = document.querySelectorAll('#edit-prod-rows-tbody tr');
        rows.forEach((tr, rIdx) => {
            const tdAct = tr.lastElementChild;
            const newTd = document.createElement('td');
            newTd.className = 'p-1';
            newTd.innerHTML = `<input type="text" name="grid_data[${rIdx}][${currentEditProdCols - 1}]" value="" class="w-full rounded border-slate-200 text-xs py-1 px-1.5" />`;
            tr.insertBefore(newTd, tdAct);
        });
        updateEditProdCounts();
    }

    function reindexEditProdRows() {
        const rows = document.querySelectorAll('#edit-prod-rows-tbody tr');
        rows.forEach((tr, rIdx) => {
            const inputs = tr.querySelectorAll('td:not(:last-child) input');
            inputs.forEach((inp, cIdx) => {
                inp.name = `grid_data[${rIdx}][${cIdx}]`;
            });
        });
        updateEditProdCounts();
    }

    function updateEditProdCounts() {
        const rows = document.querySelectorAll('#edit-prod-rows-tbody tr').length;
        document.getElementById('edit_prod_rows_count').value = rows;
        document.getElementById('edit_prod_columns_count').value = currentEditProdCols;
    }

    function renderEditProdPairs(pairs) {
        const mount = document.getElementById('edit-prod-pairs-mount');
        mount.innerHTML = '';
        const entries = Object.entries(pairs);
        if (entries.length === 0) {
            addEditProdFieldPair();
            return;
        }
        entries.forEach(([label, val]) => {
            addEditProdFieldPair(label, val);
        });
    }

    function addEditProdFieldPair(label = '', val = '') {
        const mount = document.getElementById('edit-prod-pairs-mount');
        const rowId = 'edit-field-pair-' + Date.now() + Math.random().toString(36).substr(2, 4);
        const div = document.createElement('div');
        div.id = rowId;
        div.className = 'flex items-center gap-2';
        div.innerHTML = `
            <input type="text" name="field_labels[]" value="${label.replace(/"/g, '&quot;')}" placeholder="Parameter (e.g. Blade Angle / Speed)" class="flex-1 rounded border-slate-300 text-xs py-1.5 px-2.5 font-medium" />
            <input type="text" name="field_values[]" value="${val.replace(/"/g, '&quot;')}" placeholder="Value (e.g. 45 Deg / 120 m/min)" class="flex-1 rounded border-slate-300 text-xs py-1.5 px-2.5 font-mono" />
            <button type="button" onclick="document.getElementById('${rowId}').remove()" class="p-1.5 text-slate-400 hover:text-red-600 rounded">
                <span class="material-symbols-outlined text-[16px]">close</span>
            </button>
        `;
        mount.appendChild(div);
    }
</script>
@endpush
