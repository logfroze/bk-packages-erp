@extends('layouts.app')

@section('title', 'Production & Corrugation')
@section('breadcrumb', 'Production')

@section('header_actions')
    <button onclick="openModal('modal-new-batch')" class="px-4 py-2 rounded-lg bg-navy-900 text-white text-sm font-medium hover:bg-navy-800 flex items-center gap-2">
        <span class="material-symbols-outlined text-[18px]">add</span>
        <span>New Production Batch</span>
    </button>
@endsection

@section('content')
    <!-- Sub-Navigation Tabs inside Production -->
    <div class="mb-6 border-b border-slate-200 flex items-center gap-6">
        <a href="{{ route('production.index') }}"
           class="pb-3 text-sm font-semibold border-b-2 border-blue-600 text-blue-600">
            Production Batches
        </a>
        <a href="{{ route('production.conversion') }}"
           class="pb-3 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-800">
            Material Conversion (Reel → Roll → Sheet → Carton)
        </a>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Production Batches</h1>
            <p class="text-sm text-slate-500 mt-1">Manage corrugation runs, printing, and finished carton stock updates</p>
        </div>

        <!-- Clean Filter Tabs -->
        <div class="inline-flex rounded-xl bg-white border border-slate-200 p-1">
            @foreach(['ALL' => 'All', 'RUNNING' => 'Running', 'QUEUED' => 'In Queue', 'COMPLETED' => 'Completed'] as $key => $label)
                <a href="{{ route('production.index', ['status' => $key]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ $statusFilter === $key ? 'bg-navy-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Clean, Spacious Batch Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($batches as $batch)
            @php
                $pct = $batch->target_quantity > 0 ? min(100, round(($batch->produced_quantity / $batch->target_quantity) * 100)) : 0;
                $badgeClass = match($batch->status) {
                    'RUNNING' => 'bg-blue-50 text-blue-700 border-blue-200',
                    'COMPLETED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    default => 'bg-amber-50 text-amber-700 border-amber-200',
                };
            @endphp
            <div class="bg-white rounded-xl border border-slate-200 p-6 flex flex-col justify-between gap-5">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-blue-600">{{ $batch->batch_number }}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $badgeClass }}">
                            {{ $batch->status }}
                        </span>
                    </div>

                    <h3 class="text-base font-semibold text-slate-900">{{ $batch->cartonProduct->box_title ?? 'Custom Carton' }}</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $batch->client->name ?? 'General Order' }}</p>

                    <div class="mt-4 flex flex-wrap gap-2 text-xs text-slate-600">
                        <span class="px-2 py-1 rounded bg-slate-100 font-medium">{{ $batch->cartonProduct->ply_type ?? '5-PLY' }}</span>
                        <span class="px-2 py-1 rounded bg-slate-100">{{ str_replace('_', ' ', $batch->current_machine) }}</span>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between text-xs mb-1.5">
                        <span class="text-slate-500">Progress</span>
                        <span class="font-semibold text-slate-900 num-tabular">{{ number_format($batch->produced_quantity) }} / {{ number_format($batch->target_quantity) }} Pcs ({{ $pct }}%)</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $pct }}%"></div>
                    </div>

                    @if($batch->status !== 'COMPLETED')
                        <form action="{{ route('production.output', $batch) }}" method="POST" class="mt-4 pt-4 border-t border-slate-100 flex items-center gap-2">
                            @csrf
                            <input type="number" name="add_pieces" placeholder="+ Output Pcs" min="1" required
                                   class="flex-1 rounded-lg border-slate-300 text-xs py-2" />
                            <button type="submit" class="px-3 py-2 rounded-lg bg-navy-900 text-white text-xs font-medium hover:bg-navy-800 shrink-0">
                                Add Output
                            </button>
                        </form>
                    @else
                        <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-emerald-700 font-medium flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">check_circle</span>
                            Added to Finished Cartons Stock
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-xl border border-slate-200 p-12 text-center text-slate-500">
                No production batches found for this filter.
            </div>
        @endforelse
    </div>
@endsection

@section('modals')
    <div id="modal-new-batch" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-xl">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-lg font-semibold text-slate-900">Start New Production Batch</h3>
                <button onclick="closeModal('modal-new-batch')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('production.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Carton Specification</label>
                    <select name="carton_product_id" required class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach($cartonProducts as $carton)
                            <option value="{{ $carton->id }}">{{ $carton->box_title }} ({{ $carton->client->name ?? '' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Target Qty (Pcs)</label>
                        <input type="number" name="target_quantity" value="5000" min="1" required class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Already Made (Optional)</label>
                        <input type="number" name="produced_quantity" value="0" min="0" class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Machine Line</label>
                        <select name="current_machine" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="CORRUGATION_LINE_1">Corrugation Line 1</option>
                            <option value="CORRUGATION_LINE_2">Corrugation Line 2</option>
                            <option value="FLEXO_PRINT_SLOTTER">Flexo Print & Slotter</option>
                            <option value="AUTO_STITCHER">Auto Stitching & Bundling</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Shift</label>
                        <select name="shift" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="SHIFT_A_DAY">Day Shift</option>
                            <option value="SHIFT_B_NIGHT">Night Shift</option>
                        </select>
                    </div>
                </div>
                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-new-batch')" class="px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-sm font-medium">Create Batch</button>
                </div>
            </form>
        </div>
    </div>
@endsection

