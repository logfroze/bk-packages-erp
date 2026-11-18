@extends('layouts.app')

@section('title', 'Corrugations — Sheet & Nali Production')
@section('breadcrumb', 'Corrugations')

@section('header_actions')
    @if(!auth()->user()?->isReadOnly())
        <div class="flex items-center gap-2 flex-wrap">
            <button onclick="openModal('modal-custom-card')" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50 shadow-xs flex items-center gap-1.5 transition-colors">
                <span class="material-symbols-outlined text-[16px] text-blue-600">table_chart</span>
                + Custom Table / Card
            </button>
            <button onclick="openModal('modal-add-corrugation')" class="px-3.5 py-1.5 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs flex items-center gap-1.5 transition-colors">
                <span class="material-symbols-outlined text-[16px]">add_circle</span>
                + Record Corrugation Run
            </button>
        </div>
    @endif
@endsection

@section('content')
<div class="space-y-6">

    <!-- Page Header & Visual Board Guide Banner -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider bg-blue-100 text-blue-800 border border-blue-200">
                        Stage 1 Production
                    </span>
                    <span class="text-xs text-slate-500">Okara Corrugation Plant</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 mt-2">Corrugations: Nali & Sheet Production</h1>
                <p class="text-xs text-slate-500 mt-1 max-w-2xl">
                    Produce 2-ply Single Face rolls (<strong class="text-slate-700">Nali</strong> = 1 plain paper + 1 wavy fluting) and assemble finished cardboard sheets (3-ply, 5-ply Double Wall, and 7-ply Triple Wall).
                </p>
            </div>

            <!-- Layer Guide Visual Pill Boxes -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 shrink-0">
                <div class="p-3 rounded-xl bg-amber-50/70 border border-amber-200 text-center">
                    <div class="text-[10px] font-bold uppercase text-amber-800">Single Face</div>
                    <div class="text-sm font-bold text-amber-950 mt-0.5">1 Nali (2-Ply)</div>
                    <div class="text-[10px] text-amber-700 mt-0.5">1 Plain + 1 Wavy</div>
                </div>
                <div class="p-3 rounded-xl bg-emerald-50/70 border border-emerald-200 text-center">
                    <div class="text-[10px] font-bold uppercase text-emerald-800">Single Wall</div>
                    <div class="text-sm font-bold text-emerald-950 mt-0.5">3-Ply Sheet</div>
                    <div class="text-[10px] text-emerald-700 mt-0.5">1 Nali + 1 Plain</div>
                </div>
                <div class="p-3 rounded-xl bg-blue-50/70 border border-blue-200 text-center ring-2 ring-blue-500/20">
                    <div class="text-[10px] font-bold uppercase text-blue-800">Double Wall</div>
                    <div class="text-sm font-bold text-blue-950 mt-0.5">5-Ply Sheet</div>
                    <div class="text-[10px] text-blue-700 mt-0.5">2 Nalis + 1 Plain</div>
                </div>
                <div class="p-3 rounded-xl bg-purple-50/70 border border-purple-200 text-center">
                    <div class="text-[10px] font-bold uppercase text-purple-800">Triple Wall</div>
                    <div class="text-sm font-bold text-purple-950 mt-0.5">7-Ply Sheet</div>
                    <div class="text-[10px] text-purple-700 mt-0.5">3 Nalis + 1 Plain</div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Nalis</span>
                <span class="w-8 h-8 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-xs">NL</span>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2">{{ number_format($totalNalisProduced) }}</div>
            <div class="text-[11px] text-slate-500 mt-1">2-Ply Single Face rolls formed</div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">5-Ply Double Wall</span>
                <span class="w-8 h-8 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs">5P</span>
            </div>
            <div class="text-2xl font-bold text-blue-900 mt-2">{{ number_format($doubleWallSheets) }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Complete sheets (2 Nalis + 1 Plain)</div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Sheets</span>
                <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xs">SH</span>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2">{{ number_format($totalSheetsProduced) }}</div>
            <div class="text-[11px] text-slate-500 mt-1">3-Ply: {{ number_format($singleWallSheets) }} · 7-Ply: {{ number_format($tripleWallSheets) }}</div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Paper Used / Waste</span>
                <span class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs">KG</span>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2">{{ number_format($totalPaperWeightKg / 1000, 1) }} <span class="text-xs text-slate-500 font-normal">Tons</span></div>
            <div class="text-[11px] text-red-600 mt-1">Trim Loss: {{ number_format($totalWasteTrimKg) }} KG</div>
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
                <span class="text-xs text-slate-400">Created via Client Customizer</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($customCards as $card)
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <h3 class="font-bold text-slate-900 text-sm">{{ $card->title }}</h3>
                                <div class="flex items-center gap-1">
                                    @if($card->badge_text)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            {{ $card->badge_text }}
                                        </span>
                                    @endif
                                    <button type="button" onclick="openEditCustomCardModal({{ json_encode($card) }})" class="text-slate-400 hover:text-blue-600 p-1 rounded transition-colors" title="Edit Card & Grid">
                                        <span class="material-symbols-outlined text-[16px]">edit</span>
                                    </button>
                                    <form action="{{ route('custom_cards.destroy', $card->id) }}" method="POST" onsubmit="return confirm('Delete this custom card?');" class="inline">
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

    <!-- Corrugation Production Runs Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-slate-900">Corrugation Sheet Production Ledger</h2>
                <p class="text-xs text-slate-500 mt-0.5">Records of Nali rolling and 3-Ply / 5-Ply / 7-Ply sheet assembly runs</p>
            </div>
            <div class="text-xs font-semibold text-slate-600 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200">
                Total Runs: {{ $runs->total() }}
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 text-slate-600 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Run Code</th>
                        <th class="px-4 py-3">Board Layer Type</th>
                        <th class="px-4 py-3 text-right">Nalis (2-Ply)</th>
                        <th class="px-4 py-3 text-right">Sheets Produced</th>
                        <th class="px-4 py-3">Sheet Size</th>
                        <th class="px-4 py-3">Deckle Size</th>
                        <th class="px-4 py-3 text-right">Paper Used</th>
                        <th class="px-4 py-3 text-right">Trim Loss</th>
                        <th class="px-4 py-3">Operator / Shift</th>
                        <th class="px-4 py-3">Custom Data</th>
                        <th class="px-4 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($runs as $run)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-4 py-3 font-mono font-bold text-blue-600">{{ $run->run_code }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold border {{ $run->boardTypeBadgeColor() }}">
                                    {{ $run->boardTypeLabel() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-amber-900">{{ number_format($run->nalis_produced) }}</td>
                            <td class="px-4 py-3 text-right font-bold text-slate-900">{{ number_format($run->completed_sheets_produced) }}</td>
                            <td class="px-4 py-3 font-medium text-slate-700">{{ $run->sheet_size ?: '—' }}</td>
                            <td class="px-4 py-3 font-mono text-slate-600">{{ $run->deckle_size_inches }}"</td>
                            <td class="px-4 py-3 text-right text-slate-800">{{ number_format($run->total_paper_weight_kg) }} kg</td>
                            <td class="px-4 py-3 text-right text-red-600">{{ number_format($run->waste_trim_kg) }} kg</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $run->operator_name }}</div>
                                <div class="text-[10px] text-slate-400">{{ $run->shift }} Shift · {{ $run->created_at->format('d M') }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if(is_array($run->custom_fields) && count($run->custom_fields) > 0)
                                    <span class="inline-flex items-center gap-1 text-[11px] text-blue-700 font-semibold bg-blue-50 px-2 py-0.5 rounded">
                                        <span class="material-symbols-outlined text-[13px]">tune</span>
                                        {{ count($run->custom_fields) }} custom fields
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button" onclick="openEditCorrugationModal({{ json_encode($run) }})"
                                        class="p-1.5 rounded text-slate-400 hover:text-blue-600 hover:bg-slate-100 transition-colors" title="Edit Run">
                                        <span class="material-symbols-outlined text-[16px]">edit</span>
                                    </button>
                                    <form action="{{ route('production.corrugations.destroy', $run->id) }}" method="POST" onsubmit="return confirm('Delete this corrugation run record?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Delete Run">
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-12 text-center text-slate-400">
                                <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">view_quilt</span>
                                No corrugation runs recorded yet. Click <strong class="text-slate-600">+ Record Corrugation Run</strong> above to start!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($runs->hasPages())
            <div class="px-6 py-3 border-t border-slate-100 bg-slate-50/50">
                {{ $runs->links() }}
            </div>
        @endif
    </div>

</div>

<!-- MODAL 1: RECORD CORRUGATION RUN -->
<div id="modal-add-corrugation" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-3xl w-full p-6 shadow-2xl border border-slate-200 my-8">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-slate-900 text-lg">Record Corrugation Sheet Run</h3>
                <p class="text-xs text-slate-500">Produce Nalis (Single Face 2-Ply) & Assemble 3-Ply / 5-Ply / 7-Ply Sheets</p>
            </div>
            <button onclick="closeModal('modal-add-corrugation')" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="{{ route('production.corrugations.store') }}" method="POST" class="mt-5 space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Run Code *</label>
                    <input type="text" name="run_code" value="{{ $nextCode }}" required
                        class="w-full rounded-xl border-slate-300 text-xs py-2 font-mono font-bold bg-slate-50" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Board Layer Type *</label>
                    <select id="corr_board_type" name="board_type" onchange="updateCorrugationCalculator()" required
                        class="w-full rounded-xl border-slate-300 text-xs py-2 font-semibold text-blue-900">
                        <option value="DOUBLE_WALL" selected>Double Wall (2 Nalis + 1 Plain / 5-Ply) — Standard</option>
                        <option value="SINGLE_WALL">Single Wall (1 Nali + 1 Plain / 3-Ply)</option>
                        <option value="SINGLE_FACE">Single Face (1 Nali / 2-Ply only)</option>
                        <option value="TRIPLE_WALL">Triple Wall (3 Nalis + 1 Plain / 7-Ply)</option>
                    </select>
                </div>
            </div>

            <!-- Calculation Helper Callout -->
            <div id="corr_calc_box" class="p-3.5 rounded-xl bg-blue-50 border border-blue-200 text-xs text-blue-900">
                <div class="font-bold flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px] text-blue-600">calculate</span>
                    <span id="corr_calc_title">Double Wall (5-Ply) Structure Formula</span>
                </div>
                <div id="corr_calc_desc" class="text-blue-800 text-[11px] mt-1 leading-relaxed">
                    1 Complete Sheet = <strong>2 Nalis</strong> (2 Plain Liners + 2 Wavy Flutings) + <strong>1 Top Plain Paper</strong> = 3 Plain + 2 Wavy papers total.
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Complete Sheets to Produce *</label>
                    <input type="number" id="corr_sheets_qty" name="completed_sheets_produced" value="500" min="1" oninput="updateCorrugationCalculator()" required
                        class="w-full rounded-xl border-slate-300 text-xs py-2 font-bold" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nalis Required / Produced *</label>
                    <input type="number" id="corr_nalis_qty" name="nalis_produced" value="1000" min="0" required
                        class="w-full rounded-xl border-slate-300 text-xs py-2 font-bold bg-amber-50 text-amber-900 border-amber-200" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Deckle Size (Inches) *</label>
                    <select name="deckle_size_inches" required class="w-full rounded-xl border-slate-300 text-xs py-2 font-mono">
                        @foreach($deckleSizes as $size)
                            <option value="{{ $size }}" {{ $size == '35.0' ? 'selected' : '' }}>{{ $size }}" Deckle</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sheet Size (Dimensions)</label>
                    <input type="text" name="sheet_size" placeholder="e.g. 45&quot; x 65&quot; or 1140 x 1650 mm"
                        class="w-full rounded-xl border-slate-300 text-xs py-2" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Total Paper Weight (KG)</label>
                    <input type="number" name="total_paper_weight_kg" step="0.1" placeholder="e.g. 450.0"
                        class="w-full rounded-xl border-slate-300 text-xs py-2 font-mono" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Trim / Waste Loss (KG)</label>
                    <input type="number" name="waste_trim_kg" step="0.1" placeholder="e.g. 18.5"
                        class="w-full rounded-xl border-slate-300 text-xs py-2 font-mono text-red-600" />
                </div>
            </div>

            <!-- Reel Selection Group -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider block">Paper Reel Feeds (Liner & Fluting Medium)</span>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 mb-0.5">Plain Paper Reel (Liner: Testliner / CMP)</label>
                        <select name="plain_paper_reel_1_id" class="w-full rounded-xl border-slate-300 text-xs py-1.5">
                            <option value="">— Select Plain Reel (Optional) —</option>
                            @foreach($plainReels as $reel)
                                <option value="{{ $reel->id }}">{{ $reel->reel_number }} — {{ $reel->paper_grade }} ({{ $reel->deckle_size_inches }}", {{ $reel->current_weight_kg }}kg)</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 mb-0.5">Wavy Paper Reel (Fluting: Super Fluting)</label>
                        <select name="fluting_wavy_reel_1_id" class="w-full rounded-xl border-slate-300 text-xs py-1.5">
                            <option value="">— Select Fluting Reel (Optional) —</option>
                            @foreach($flutingReels as $reel)
                                <option value="{{ $reel->id }}">{{ $reel->reel_number }} — {{ $reel->paper_grade }} ({{ $reel->deckle_size_inches }}", {{ $reel->current_weight_kg }}kg)</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Operator / Master Name</label>
                    <input type="text" name="operator_name" value="Master Corrugator"
                        class="w-full rounded-xl border-slate-300 text-xs py-2" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Shift</label>
                    <select name="shift" class="w-full rounded-xl border-slate-300 text-xs py-2">
                        <option value="DAY" selected>Day Shift</option>
                        <option value="NIGHT">Night Shift</option>
                    </select>
                </div>
            </div>

            <!-- Dynamic Custom Fields Section (CLIENT CUSTOMIZATION REQUIREMENT) -->
            <div class="border-t border-slate-200 pt-4">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider block">Custom Fields & Parameters</span>
                        <p class="text-[11px] text-slate-400">Add any dynamic factory attributes (e.g. Moisture %, Glue Brand, Grammage GSM, Urdu notes)</p>
                    </div>
                    <button type="button" onclick="addCustomFieldRow('corr-custom-fields-container')" class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 text-xs font-semibold hover:bg-blue-100 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[15px]">add</span>
                        + Add Custom Field
                    </button>
                </div>

                <div id="corr-custom-fields-container" class="space-y-2 mt-2">
                    <!-- Dynamic fields added via JS -->
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-add-corrugation')" class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">save</span>
                    Save Corrugation Run
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: EDIT CORRUGATION RUN -->
<div id="modal-edit-corrugation" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 my-8">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-slate-900 text-lg flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-600">edit_note</span>
                    Edit Corrugation Run (<span id="edit-corr-run-code-label"></span>)
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Update sheet count, Nalis, board type, operator, or notes</p>
            </div>
            <button onclick="closeModal('modal-edit-corrugation')" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form id="edit-corrugation-form" method="POST" class="mt-5 space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Board Layer Type *</label>
                    <select name="board_type" id="edit-corr-board-type" required class="w-full rounded-xl border-slate-300 text-xs py-2 font-semibold text-blue-900">
                        <option value="DOUBLE_WALL">Double Wall (5-Ply) — Standard</option>
                        <option value="SINGLE_WALL">Single Wall (3-Ply)</option>
                        <option value="SINGLE_FACE">Single Face (2-Ply)</option>
                        <option value="TRIPLE_WALL">Triple Wall (7-Ply)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Shift</label>
                    <select name="shift" id="edit-corr-shift" required class="w-full rounded-xl border-slate-300 text-xs py-2 font-semibold">
                        <option value="DAY">DAY Shift</option>
                        <option value="NIGHT">NIGHT Shift</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sheets Produced</label>
                    <input type="number" name="completed_sheets_produced" id="edit-corr-sheets" min="0" class="w-full rounded-xl border-slate-300 text-xs py-2 font-bold" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nalis Produced</label>
                    <input type="number" name="nalis_produced" id="edit-corr-nalis" min="0" class="w-full rounded-xl border-slate-300 text-xs py-2 font-bold" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Deckle Size (Inches)</label>
                    <input type="number" step="0.5" name="deckle_size_inches" id="edit-corr-deckle" min="15" max="50" class="w-full rounded-xl border-slate-300 text-xs py-2" />
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sheet Size (Optional)</label>
                    <input type="text" name="sheet_size" id="edit-corr-sheet-size" placeholder="e.g. 36×24 Inch" class="w-full rounded-xl border-slate-300 text-xs py-2" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Total Paper Weight (KG)</label>
                    <input type="number" step="0.01" name="total_paper_weight_kg" id="edit-corr-weight" min="0" class="w-full rounded-xl border-slate-300 text-xs py-2" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Waste Trim (KG)</label>
                    <input type="number" step="0.01" name="waste_trim_kg" id="edit-corr-waste" min="0" class="w-full rounded-xl border-slate-300 text-xs py-2" />
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Operator Name</label>
                <input type="text" name="operator_name" id="edit-corr-operator" class="w-full rounded-xl border-slate-300 text-xs py-2" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Notes</label>
                <textarea name="notes" id="edit-corr-notes" rows="2" class="w-full rounded-xl border-slate-300 text-xs py-2 resize-none"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-edit-corrugation')" class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">save</span>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: CREATE CUSTOM CARD / TABLE (CLIENT CUSTOMIZATION REQUIREMENT) -->
<div id="modal-custom-card" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 my-8">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-slate-900 text-lg">Create Custom Table / Card</h3>
                <p class="text-xs text-slate-500">Design your own custom cards and tables (e.g. 3 rows × 5 columns, or any layout)</p>
            </div>
            <button onclick="closeModal('modal-custom-card')" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="{{ route('custom_cards.store') }}" method="POST" class="mt-5 space-y-4">
            @csrf
            <input type="hidden" name="page_key" value="corrugations" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Card / Table Title *</label>
                    <input type="text" name="title" placeholder="e.g. Weekly Sheet Quality Audit" required
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
                <input type="text" name="subtitle" placeholder="e.g. Track GSM grammage, humidity, and bursting factor"
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
                        <input type="text" name="column_headers[]" value="Batch #" class="rounded-lg border-slate-300 text-xs py-1 px-2" />
                        <input type="text" name="column_headers[]" value="Deckle Size" class="rounded-lg border-slate-300 text-xs py-1 px-2" />
                        <input type="text" name="column_headers[]" value="Bursting Factor" class="rounded-lg border-slate-300 text-xs py-1 px-2" />
                        <input type="text" name="column_headers[]" value="Moisture %" class="rounded-lg border-slate-300 text-xs py-1 px-2" />
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

<!-- MODAL: EDIT CUSTOM CARD / TABLE (FULL CLIENT CUSTOMIZATION) -->
<div id="modal-edit-custom-card" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-3xl w-full p-6 shadow-2xl border border-slate-200 my-8">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">edit</span>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-lg">Edit Custom Table / Card</h3>
                    <p class="text-xs text-slate-500">Customize card title, badge, columns, rows, and cell data</p>
                </div>
            </div>
            <button onclick="closeModal('modal-edit-custom-card')" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="edit-custom-card-form" action="" method="POST" class="mt-5 space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Card / Table Title *</label>
                    <input type="text" id="edit_card_title" name="title" required
                        class="w-full rounded-xl border-slate-300 text-xs py-2" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Badge Tag</label>
                    <input type="text" id="edit_card_badge_text" name="badge_text" placeholder="e.g. AUDIT"
                        class="w-full rounded-xl border-slate-300 text-xs py-2 uppercase font-mono" />
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Subtitle / Note</label>
                <input type="text" id="edit_card_subtitle" name="subtitle"
                    class="w-full rounded-xl border-slate-300 text-xs py-2" />
            </div>

            <!-- Dynamic Table Editor -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Custom Table Columns & Rows</span>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="addEditGridColumn()" class="px-2.5 py-1 rounded bg-slate-200 hover:bg-slate-300 text-slate-700 text-[11px] font-semibold flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">view_column</span>
                            + Column
                        </button>
                        <button type="button" onclick="addEditGridRow()" class="px-2.5 py-1 rounded bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-semibold flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">add</span>
                            + Row
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto max-h-64 border border-slate-200 rounded-lg p-2 bg-white">
                    <table class="w-full text-xs text-left" id="edit-card-grid-table">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr id="edit-card-headers-row"></tr>
                        </thead>
                        <tbody id="edit-card-rows-tbody" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
                <input type="hidden" id="edit_card_rows_count" name="rows_count" value="1" />
                <input type="hidden" id="edit_card_columns_count" name="columns_count" value="1" />
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-edit-custom-card')" class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 shadow-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">check</span>
                    Update Custom Card
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function updateCorrugationCalculator() {
        const type = document.getElementById('corr_board_type').value;
        const sheets = parseInt(document.getElementById('corr_sheets_qty').value) || 0;
        const nalisInput = document.getElementById('corr_nalis_qty');
        const calcTitle = document.getElementById('corr_calc_title');
        const calcDesc = document.getElementById('corr_calc_desc');

        let multiplier = 2;
        let formulaText = "";

        if (type === 'SINGLE_FACE') {
            multiplier = 1;
            calcTitle.textContent = "Single Face Board (1 Nali / 2-Ply)";
            formulaText = `${sheets} Rolls/Sheets = ${sheets} Nalis (1 Plain Liner + 1 Wavy Fluting).`;
        } else if (type === 'SINGLE_WALL') {
            multiplier = 1;
            calcTitle.textContent = "Single Wall Board (3-Ply)";
            formulaText = `1 Sheet = 1 Nali + 1 Plain Paper. For ${sheets} Sheets: Needs ${sheets} Nalis + ${sheets} Plain Papers.`;
        } else if (type === 'DOUBLE_WALL') {
            multiplier = 2;
            calcTitle.textContent = "Double Wall Board (5-Ply Complete Sheet)";
            formulaText = `1 Set/Sheet = 2 Nalis + 1 Plain Paper. For ${sheets} Sheets: Needs ${sheets * 2} Nalis (${sheets * 2} Plain + ${sheets * 2} Wavy) + ${sheets} Plain Papers = ${sheets * 3} Plain + ${sheets * 2} Wavy total!`;
        } else if (type === 'TRIPLE_WALL') {
            multiplier = 3;
            calcTitle.textContent = "Triple Wall Board (7-Ply Heavy Duty)";
            formulaText = `1 Sheet = 3 Nalis + 1 Plain Paper. For ${sheets} Sheets: Needs ${sheets * 3} Nalis + ${sheets} Plain Papers.`;
        }

        nalisInput.value = sheets * multiplier;
        calcDesc.textContent = formulaText;
    }

    // Dynamic Custom Field Rows Builder
    function addCustomFieldRow(containerId) {
        const container = document.getElementById(containerId);
        const idx = Date.now();
        const row = document.createElement('div');
        row.className = 'grid grid-cols-12 gap-2 items-center';
        row.id = `cfield-${idx}`;
        row.innerHTML = `
            <div class="col-span-5">
                <input type="text" name="custom_fields[${idx}][label]" placeholder="Field Label (e.g. Moisture %)" required
                    class="w-full rounded-lg border-slate-300 text-xs py-1.5 px-2 font-medium" />
            </div>
            <div class="col-span-6">
                <input type="text" name="custom_fields[${idx}][value]" placeholder="Value (e.g. 8.5%, Grade A, etc.)" required
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

    // Dynamic Grid Cells Generator for Custom Cards
    function generateCustomGridInputs() {
        const rows = parseInt(document.getElementById('builder_rows').value) || 3;
        const cols = parseInt(document.getElementById('builder_cols').value) || 4;

        // Generate Headers
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

        // Generate Cells
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
                td.innerHTML = `<input type="text" name="grid_data[${r}][${c}]" value="Data ${r+1},${c+1}" class="w-full rounded border-slate-200 text-xs py-1 px-1.5" />`;
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

    function openEditCorrugationModal(run) {
        document.getElementById('edit-corrugation-form').action = '/production/corrugations/' + run.id;
        document.getElementById('edit-corr-run-code-label').innerText = run.run_code || '';
        document.getElementById('edit-corr-board-type').value = run.board_type || 'DOUBLE_WALL';
        document.getElementById('edit-corr-shift').value = run.shift || 'DAY';
        document.getElementById('edit-corr-sheets').value = run.completed_sheets_produced || 0;
        document.getElementById('edit-corr-nalis').value = run.nalis_produced || 0;
        document.getElementById('edit-corr-deckle').value = run.deckle_size_inches || '';
        document.getElementById('edit-corr-sheet-size').value = run.sheet_size || '';
        document.getElementById('edit-corr-weight').value = run.total_paper_weight_kg || '';
        document.getElementById('edit-corr-waste').value = run.waste_trim_kg || '';
        document.getElementById('edit-corr-operator').value = run.operator_name || '';
        document.getElementById('edit-corr-notes').value = run.notes || '';
        openModal('modal-edit-corrugation');
    }

    let currentEditCardCols = 0;

    function openEditCustomCardModal(card) {
        document.getElementById('edit-custom-card-form').action = '/custom-cards/' + card.id;
        document.getElementById('edit_card_title').value = card.title || '';
        document.getElementById('edit_card_subtitle').value = card.subtitle || '';
        document.getElementById('edit_card_badge_text').value = card.badge_text || '';
        
        const headers = Array.isArray(card.column_headers) && card.column_headers.length > 0 
            ? card.column_headers 
            : ['Col 1', 'Col 2', 'Col 3'];
        const rows = Array.isArray(card.grid_data) && card.grid_data.length > 0
            ? card.grid_data
            : [['', '', '']];

        renderEditGrid(headers, rows);
        openModal('modal-edit-custom-card');
    }

    function renderEditGrid(headers, rows) {
        currentEditCardCols = headers.length;

        const trHead = document.getElementById('edit-card-headers-row');
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

        const tbody = document.getElementById('edit-card-rows-tbody');
        tbody.innerHTML = '';
        rows.forEach((rData, rIdx) => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50 edit-grid-row';
            for (let c = 0; c < currentEditCardCols; c++) {
                const td = document.createElement('td');
                td.className = 'p-1';
                const cellVal = (Array.isArray(rData) && rData[c] !== undefined) ? rData[c] : '';
                td.innerHTML = `<input type="text" name="grid_data[${rIdx}][${c}]" value="${(cellVal || '').toString().replace(/"/g, '&quot;')}" class="w-full rounded border-slate-200 text-xs py-1 px-1.5" />`;
                tr.appendChild(td);
            }
            const tdAct = document.createElement('td');
            tdAct.className = 'p-1 text-center';
            tdAct.innerHTML = `<button type="button" onclick="this.closest('tr').remove(); reindexEditGridRows();" class="text-slate-300 hover:text-red-600 p-1" title="Delete Row"><span class="material-symbols-outlined text-[16px]">delete</span></button>`;
            tr.appendChild(tdAct);
            tbody.appendChild(tr);
        });

        updateEditGridCounts();
    }

    function addEditGridRow() {
        const tbody = document.getElementById('edit-card-rows-tbody');
        const rIdx = tbody.querySelectorAll('tr').length;
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50 edit-grid-row';
        for (let c = 0; c < currentEditCardCols; c++) {
            const td = document.createElement('td');
            td.className = 'p-1';
            td.innerHTML = `<input type="text" name="grid_data[${rIdx}][${c}]" value="" class="w-full rounded border-slate-200 text-xs py-1 px-1.5" />`;
            tr.appendChild(td);
        }
        const tdAct = document.createElement('td');
        tdAct.className = 'p-1 text-center';
        tdAct.innerHTML = `<button type="button" onclick="this.closest('tr').remove(); reindexEditGridRows();" class="text-slate-300 hover:text-red-600 p-1" title="Delete Row"><span class="material-symbols-outlined text-[16px]">delete</span></button>`;
        tr.appendChild(tdAct);
        tbody.appendChild(tr);
        updateEditGridCounts();
    }

    function addEditGridColumn() {
        currentEditCardCols++;
        const trHead = document.getElementById('edit-card-headers-row');
        const thAct = trHead.lastElementChild;
        const newTh = document.createElement('th');
        newTh.className = 'p-1.5 font-semibold text-slate-700';
        newTh.innerHTML = `<input type="text" name="column_headers[]" value="Col ${currentEditCardCols}" class="w-full rounded border-slate-300 text-xs py-1 px-1.5 font-semibold" />`;
        trHead.insertBefore(newTh, thAct);

        const rows = document.querySelectorAll('#edit-card-rows-tbody tr');
        rows.forEach((tr, rIdx) => {
            const tdAct = tr.lastElementChild;
            const newTd = document.createElement('td');
            newTd.className = 'p-1';
            newTd.innerHTML = `<input type="text" name="grid_data[${rIdx}][${currentEditCardCols - 1}]" value="" class="w-full rounded border-slate-200 text-xs py-1 px-1.5" />`;
            tr.insertBefore(newTd, tdAct);
        });
        updateEditGridCounts();
    }

    function reindexEditGridRows() {
        const rows = document.querySelectorAll('#edit-card-rows-tbody tr');
        rows.forEach((tr, rIdx) => {
            const inputs = tr.querySelectorAll('td:not(:last-child) input');
            inputs.forEach((inp, cIdx) => {
                inp.name = `grid_data[${rIdx}][${cIdx}]`;
            });
        });
        updateEditGridCounts();
    }

    function updateEditGridCounts() {
        const rows = document.querySelectorAll('#edit-card-rows-tbody tr').length;
        document.getElementById('edit_card_rows_count').value = rows;
        document.getElementById('edit_card_columns_count').value = currentEditCardCols;
    }

    document.addEventListener('DOMContentLoaded', () => {
        generateCustomGridInputs();
    });
</script>
@endpush
@endsection
