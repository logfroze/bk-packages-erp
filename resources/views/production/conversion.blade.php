@extends('layouts.app')

@section('title', 'Material Conversion Tracking')
@section('breadcrumb', 'Production / Material Conversion')

@section('header_actions')
    <button onclick="openModal('modal-new-conversion')" class="px-4 py-2 rounded-lg bg-navy-900 text-white text-sm font-medium hover:bg-navy-800 flex items-center gap-2">
        <span class="material-symbols-outlined text-[18px]">add</span>
        <span>+ Feed Reels (Stage 1)</span>
    </button>
@endsection

@section('content')
    <!-- Sub-Navigation Tabs inside Production -->
    <div class="mb-6 border-b border-slate-200 flex items-center gap-6">
        <a href="{{ route('production.index') }}"
           class="pb-3 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-800">
            Production Batches
        </a>
        <a href="{{ route('production.conversion') }}"
           class="pb-3 text-sm font-semibold border-b-2 border-blue-600 text-blue-600">
            Material Conversion (Reel → Roll → Sheet → Carton)
        </a>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Multi-Stage Material Conversion</h1>
            <p class="text-sm text-slate-500 mt-1">Track material yield from Raw Paper Reels → Rolls → Cut Sheets → Finished Cartons</p>
        </div>
    </div>

    <!-- 4-Stage High-Level Summary Strip -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">1. Paper Reels Fed</div>
            <div class="text-2xl font-bold text-slate-900 mt-1 num-tabular">{{ number_format($summary['reels_used_kg'], 1) }} kg</div>
            <div class="text-xs text-slate-400 mt-0.5">Raw Material Input</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">2. Rolls Produced</div>
            <div class="text-2xl font-bold text-blue-600 mt-1 num-tabular">{{ number_format($summary['rolls_produced']) }} Rolls</div>
            <div class="text-xs text-slate-400 mt-0.5">From Dual/Single Reels</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">3. Sheets Cut</div>
            <div class="text-2xl font-bold text-slate-900 mt-1 num-tabular">{{ number_format($summary['sheets_cut']) }} Sheets</div>
            <div class="text-xs text-slate-400 mt-0.5">Measured & Sliced</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">4. Cartons Made</div>
            <div class="text-2xl font-bold text-emerald-600 mt-1 num-tabular">{{ number_format($summary['cartons_made']) }} Pcs</div>
            <div class="text-xs text-slate-400 mt-0.5">Added to Godown Stock</div>
        </div>
    </div>

    <!-- Spacious Conversion Run Cards -->
    <div class="space-y-6">
        @forelse($runs as $run)
            @php
                $badgeStyle = match($run->status) {
                    'COMPLETED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'SHEETS_TO_CARTONS' => 'bg-blue-50 text-blue-700 border-blue-200',
                    default => 'bg-amber-50 text-amber-700 border-amber-200',
                };
            @endphp
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <!-- Run Card Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-slate-100">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <span class="text-sm font-bold text-blue-600">{{ $run->run_code }}</span>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $badgeStyle }}">
                                {{ str_replace('_', ' ', $run->status) }}
                            </span>
                            <span class="text-xs text-slate-400">{{ $run->run_date->format('d M Y') }} · {{ $run->machine_name }}</span>
                        </div>
                        <h3 class="text-base font-semibold text-slate-900 mt-1">
                            {{ $run->cartonProduct->box_title ?? 'Custom Carton Run' }}
                            @if($run->client)
                                <span class="text-xs font-normal text-slate-500">({{ $run->client->name }})</span>
                            @endif
                        </h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('production.conversion.report', $run) }}" target="_blank"
                           class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-medium hover:bg-slate-50 inline-flex items-center gap-1">
                            <span class="material-symbols-outlined text-[15px]">summarize</span>
                            Full Conversion Report
                        </a>
                    </div>
                </div>

                <!-- 4-Stage Horizontal Pipeline -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-5">
                    <!-- Stage 1: Reels -> Rolls -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col justify-between">
                        <div>
                            <div class="text-[11px] font-bold uppercase text-slate-500 mb-2">Stage 1 · Reels → Rolls</div>
                            <div class="space-y-1.5 text-xs text-slate-700">
                                <div>
                                    <span class="font-semibold">Reel 1:</span> {{ $run->reel1->reel_number ?? 'N/A' }}
                                    <span class="text-slate-500">({{ number_format($run->reel_1_used_kg) }}kg → <strong>{{ $run->reel_1_rolls_produced }} rolls</strong>)</span>
                                </div>
                                @if($run->reel_2_id)
                                    <div>
                                        <span class="font-semibold">Reel 2:</span> {{ $run->reel2->reel_number ?? 'N/A' }}
                                        <span class="text-slate-500">({{ number_format($run->reel_2_used_kg) }}kg → <strong>{{ $run->reel_2_rolls_produced }} rolls</strong>)</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-slate-200 flex justify-between items-center text-xs">
                            <span class="text-slate-500">Total Rolls: <strong class="text-slate-900">{{ $run->total_rolls_produced }}</strong></span>
                            <span class="font-semibold text-blue-600">{{ $run->rolls_remaining }} Left</span>
                        </div>
                    </div>

                    <!-- Stage 2: Rolls -> Sheets -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col justify-between">
                        <div>
                            <div class="text-[11px] font-bold uppercase text-slate-500 mb-2">Stage 2 · Rolls → Sheets</div>
                            <div class="text-xs text-slate-700 space-y-1">
                                <div>Rolls Used: <strong>{{ $run->rolls_used_for_sheets }}</strong></div>
                                <div>Actual Sheets Cut: <strong class="text-slate-900">{{ number_format($run->actual_sheets_cut) }}</strong>
                                    @if($run->expected_sheets > 0 && $run->expected_sheets != $run->actual_sheets_cut)
                                        <span class="text-slate-400">(Exp: {{ number_format($run->expected_sheets) }})</span>
                                    @endif
                                </div>
                                @if($run->sheetCuts->count() > 0)
                                    <div class="pt-1 text-[11px] text-slate-500">
                                        @foreach($run->sheetCuts as $cut)
                                            <div>• {{ $cut->sheet_size_label }}: {{ number_format($cut->actual_sheets_cut) }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-slate-200 flex items-center justify-between gap-2">
                            <span class="text-xs font-semibold text-blue-600">{{ number_format($run->sheets_remaining) }} Sheets Left</span>
                            @if($run->rolls_remaining > 0)
                                <button onclick="openSheetModal({{ $run->id }}, '{{ $run->run_code }}', {{ $run->rolls_remaining }})"
                                        class="px-2.5 py-1 rounded-lg bg-navy-900 text-white text-[11px] font-medium hover:bg-navy-800">
                                    + Cut Sheets
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Stage 3: Sheets -> Cartons -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col justify-between">
                        <div>
                            <div class="text-[11px] font-bold uppercase text-slate-500 mb-2">Stage 3 · Sheets → Cartons</div>
                            <div class="text-xs text-slate-700 space-y-1">
                                <div>Sheets Used: <strong>{{ number_format($run->sheets_used_for_cartons) }}</strong></div>
                                <div>Cartons Produced: <strong class="text-emerald-700 text-sm">{{ number_format($run->actual_cartons_produced) }} Pcs</strong></div>
                                <div class="text-[11px] text-slate-500">Rule: {{ $run->sheets_per_carton_rule }} sheet(s) / carton</div>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-slate-200 flex items-center justify-between gap-2">
                            @if($run->sheets_remaining > 0)
                                <button onclick="openCartonModal({{ $run->id }}, '{{ $run->run_code }}', {{ $run->sheets_remaining }}, {{ $run->sheets_per_carton_rule }})"
                                        class="w-full py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">
                                    + Convert to Cartons
                                </button>
                            @else
                                <span class="text-xs text-slate-400">{{ $run->status === 'COMPLETED' ? 'All Sheets Processed' : 'Cut sheets first' }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Stage 4: Yield & Losses -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col justify-between">
                        <div>
                            <div class="text-[11px] font-bold uppercase text-slate-500 mb-2">Stage 4 · Losses & Remaining</div>
                            <div class="text-xs text-slate-700 space-y-1.5">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Stage 1 Waste:</span>
                                    <span class="font-medium num-tabular">{{ number_format($run->stage1_loss_kg, 1) }} kg</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Stage 2 Sheet Loss:</span>
                                    <span class="font-medium num-tabular {{ $run->stage2_loss_sheets > 0 ? 'text-amber-700' : '' }}">{{ number_format($run->stage2_loss_sheets) }} shts</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Stage 3 Box Waste:</span>
                                    <span class="font-medium num-tabular {{ $run->stage3_loss_cartons > 0 ? 'text-amber-700' : '' }}">{{ number_format($run->stage3_loss_cartons) }} pcs</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-slate-200 text-[11px] text-slate-500">
                            @if($run->carton_product_id)
                                Auto-synced to Finished Stock
                            @else
                                Custom Conversion Run
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-slate-200 p-12 text-center text-slate-500">
                No material conversion runs yet. Click <strong>"+ Feed Reels (Stage 1)"</strong> to start tracking Reel → Roll → Sheet → Carton.
            </div>
        @endforelse
    </div>
@endsection

@section('modals')
    <!-- Modal Stage 1: Feed 1 or 2 Reels -> Rolls -->
    <div id="modal-new-conversion" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-xl w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-lg font-semibold text-slate-900">Stage 1: Feed Reels → Produce Rolls</h3>
                <button onclick="closeModal('modal-new-conversion')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('production.conversion.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Machine Name</label>
                        <input type="text" name="machine_name" value="Corrugator Line 1" required class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Target Carton (Auto-adds to stock)</label>
                        <select name="carton_product_id" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">-- Optional / Custom Box --</option>
                            @foreach($cartonProducts as $c)
                                <option value="{{ $c->id }}">{{ $c->box_title }} ({{ $c->client->name ?? '' }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Reel 1 -->
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                    <div class="text-xs font-bold uppercase text-slate-700">Primary Reel (Reel 1)</div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-1">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Select Paper Reel</label>
                            <select name="reel_1_id" required class="w-full rounded-lg border-slate-300 text-xs">
                                @foreach($paperReels as $r)
                                    <option value="{{ $r->id }}">{{ $r->reel_number }} ({{ number_format($r->remaining_weight_kg) }}kg)</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Weight Used (KG)</label>
                            <input type="number" step="0.5" name="reel_1_used_kg" required min="1" placeholder="e.g. 300" class="w-full rounded-lg border-slate-300 text-xs" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Rolls Produced</label>
                            <input type="number" name="reel_1_rolls_produced" required min="1" placeholder="e.g. 12" class="w-full rounded-lg border-slate-300 text-xs" />
                        </div>
                    </div>
                </div>

                <!-- Reel 2 (Optional Simultaneous) -->
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                    <div class="text-xs font-bold uppercase text-slate-700">Second Simultaneous Reel (Reel 2 — Optional)</div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-1">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Select Paper Reel</label>
                            <select name="reel_2_id" class="w-full rounded-lg border-slate-300 text-xs">
                                <option value="">-- Single Reel Only --</option>
                                @foreach($paperReels as $r)
                                    <option value="{{ $r->id }}">{{ $r->reel_number }} ({{ number_format($r->remaining_weight_kg) }}kg)</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Weight Used (KG)</label>
                            <input type="number" step="0.5" name="reel_2_used_kg" min="0" value="0" class="w-full rounded-lg border-slate-300 text-xs" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Rolls Produced</label>
                            <input type="number" name="reel_2_rolls_produced" min="0" value="0" class="w-full rounded-lg border-slate-300 text-xs" />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Reel Trim / Core Waste (KG)</label>
                        <input type="number" step="0.5" name="stage1_loss_kg" value="0" min="0" class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Notes</label>
                        <input type="text" name="notes" placeholder="Optional shift note" class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-new-conversion')" class="px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-sm font-medium">Start Conversion Run</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Stage 2: Cut Rolls -> Sheets -->
    <div id="modal-sheet-cut" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-lg font-semibold text-slate-900">Stage 2: Cut Rolls → Sheets (<span id="sheet-run-label"></span>)</h3>
                <button onclick="closeModal('modal-sheet-cut')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="sheet-cut-form" method="POST" class="mt-4 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Rolls to Cut (Max: <span id="sheet-max-rolls"></span>)</label>
                        <input type="number" name="rolls_consumed" id="sheet-rolls-input" min="1" required class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Sheet Size / Label</label>
                        <input type="text" name="sheet_size_label" required placeholder='e.g. 48" x 32" Ghee' class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Expected Sheets / Roll (Rule)</label>
                        <input type="number" step="0.5" name="sheets_per_roll_formula" placeholder="e.g. 200 (Optional)" class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Actual Sheets Cut</label>
                        <input type="number" name="actual_sheets_cut" min="1" required class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                </div>
                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-sheet-cut')" class="px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-sm font-medium">Save Sheet Cut</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Stage 3: Sheets -> Cartons -->
    <div id="modal-carton-convert" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-lg font-semibold text-slate-900">Stage 3: Sheets → Cartons (<span id="carton-run-label"></span>)</h3>
                <button onclick="closeModal('modal-carton-convert')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="carton-convert-form" method="POST" class="mt-4 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Sheets Used (Max: <span id="carton-max-sheets"></span>)</label>
                        <input type="number" name="sheets_used" id="carton-sheets-input" min="1" required class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Sheets per Carton Rule</label>
                        <input type="number" step="0.1" name="sheets_per_carton_rule" id="carton-rule-input" value="1" min="0.1" required class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Actual Cartons Manufactured (Pcs)</label>
                    <input type="number" name="actual_cartons_produced" min="1" required class="w-full rounded-lg border-slate-300 text-sm" />
                </div>
                <label class="flex items-center gap-2 text-xs text-slate-700">
                    <input type="checkbox" name="mark_completed" value="1" class="rounded border-slate-300 text-emerald-600" />
                    <span>Mark this conversion run as Completed</span>
                </label>
                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-carton-convert')" class="px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Save Cartons & Update Stock</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function openSheetModal(runId, runCode, maxRolls) {
        document.getElementById('sheet-run-label').innerText = runCode;
        document.getElementById('sheet-max-rolls').innerText = maxRolls;
        document.getElementById('sheet-rolls-input').max = maxRolls;
        document.getElementById('sheet-rolls-input').value = maxRolls;
        document.getElementById('sheet-cut-form').action = '/production/conversion/' + runId + '/sheets';
        openModal('modal-sheet-cut');
    }

    function openCartonModal(runId, runCode, maxSheets, rule) {
        document.getElementById('carton-run-label').innerText = runCode;
        document.getElementById('carton-max-sheets').innerText = maxSheets;
        document.getElementById('carton-sheets-input').max = maxSheets;
        document.getElementById('carton-sheets-input').value = maxSheets;
        document.getElementById('carton-rule-input').value = rule;
        document.getElementById('carton-convert-form').action = '/production/conversion/' + runId + '/cartons';
        openModal('modal-carton-convert');
    }
</script>
@endpush

