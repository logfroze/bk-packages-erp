<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Conversion Report {{ $run->run_code }} — BK PACKAGES</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print { .no-print { display: none !important; } body { background: #fff !important; } }
    </style>
</head>
<body class="bg-slate-100 py-8 px-4 font-sans text-slate-900">
    <div class="max-w-3xl mx-auto bg-white border border-slate-300 rounded-xl p-8 shadow-sm">
        <div class="flex items-center justify-between pb-6 mb-6 border-b border-slate-200 no-print">
            <a href="{{ route('production.conversion') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">← Back to Conversion Tracking</a>
            <button onclick="window.print()" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800">
                Print Conversion Report
            </button>
        </div>

        <div class="flex justify-between items-start border-b-2 border-slate-900 pb-4 mb-6">
            <div>
                <h1 class="text-xl font-bold uppercase">BK PACKAGES — Multi-Stage Production Report</h1>
                <p class="text-xs text-slate-600 mt-1">3-KM Depalpur Road, Okara · Machine: {{ $run->machine_name }}</p>
            </div>
            <div class="text-right">
                <div class="font-bold text-lg text-blue-700">{{ $run->run_code }}</div>
                <div class="text-xs text-slate-500">{{ $run->run_date->format('d M Y') }} · Status: {{ $run->status }}</div>
            </div>
        </div>

        <div class="mb-6 text-sm">
            <span class="text-slate-500">Carton Specification:</span>
            <strong class="ml-1">{{ $run->cartonProduct->box_title ?? 'Custom Carton' }}</strong>
            @if($run->client)
                <span class="text-slate-500 ml-2">({{ $run->client->name }})</span>
            @endif
        </div>

        <table class="w-full text-left border-collapse border border-slate-200 text-sm mb-6">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase text-slate-600">
                    <th class="py-3 px-4">Production Stage</th>
                    <th class="py-3 px-4">Input Fed</th>
                    <th class="py-3 px-4">Output Produced</th>
                    <th class="py-3 px-4 text-right">Remaining / Loss</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <tr>
                    <td class="py-3.5 px-4 font-semibold">1. Reels → Rolls</td>
                    <td class="py-3.5 px-4">
                        <div>Reel 1: {{ $run->reel1->reel_number ?? '-' }} ({{ number_format($run->reel_1_used_kg) }} kg)</div>
                        @if($run->reel_2_id)
                            <div>Reel 2: {{ $run->reel2->reel_number ?? '-' }} ({{ number_format($run->reel_2_used_kg) }} kg)</div>
                        @endif
                    </td>
                    <td class="py-3.5 px-4 font-bold">{{ $run->total_rolls_produced }} Total Rolls
                        <div class="text-xs font-normal text-slate-500">({{ $run->reel_1_rolls_produced }} + {{ $run->reel_2_rolls_produced }})</div>
                    </td>
                    <td class="py-3.5 px-4 text-right">
                        <div>{{ $run->rolls_remaining }} Rolls Left</div>
                        <div class="text-xs text-amber-700">Waste: {{ number_format($run->stage1_loss_kg, 1) }} kg</div>
                    </td>
                </tr>
                <tr>
                    <td class="py-3.5 px-4 font-semibold">2. Rolls → Sheets</td>
                    <td class="py-3.5 px-4">{{ $run->rolls_used_for_sheets }} Rolls Cut</td>
                    <td class="py-3.5 px-4 font-bold">{{ number_format($run->actual_sheets_cut) }} Sheets
                        @if($run->expected_sheets > 0)
                            <div class="text-xs font-normal text-slate-500">Expected: {{ number_format($run->expected_sheets) }}</div>
                        @endif
                    </td>
                    <td class="py-3.5 px-4 text-right">
                        <div>{{ number_format($run->sheets_remaining) }} Sheets Left</div>
                        <div class="text-xs text-amber-700">Loss: {{ number_format($run->stage2_loss_sheets) }} sheets</div>
                    </td>
                </tr>
                <tr>
                    <td class="py-3.5 px-4 font-semibold">3. Sheets → Cartons</td>
                    <td class="py-3.5 px-4">{{ number_format($run->sheets_used_for_cartons) }} Sheets Used
                        <div class="text-xs text-slate-500">Rule: {{ $run->sheets_per_carton_rule }} sheet/box</div>
                    </td>
                    <td class="py-3.5 px-4 font-bold text-emerald-700">{{ number_format($run->actual_cartons_produced) }} Cartons
                        @if($run->expected_cartons > 0)
                            <div class="text-xs font-normal text-slate-500">Expected: {{ number_format($run->expected_cartons) }}</div>
                        @endif
                    </td>
                    <td class="py-3.5 px-4 text-right">
                        <div class="text-xs text-amber-700">Waste: {{ number_format($run->stage3_loss_cartons) }} pcs</div>
                    </td>
                </tr>
            </tbody>
        </table>

        @if($run->sheetCuts->count() > 0)
            <h2 class="text-xs font-bold uppercase text-slate-500 mb-2">Sheet Cutting Breakdown (Measurements)</h2>
            <table class="w-full text-left border-collapse border border-slate-200 text-xs mb-6">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600">
                        <th class="py-2 px-3">Size / Spec</th>
                        <th class="py-2 px-3">Rolls Cut</th>
                        <th class="py-2 px-3 text-right">Expected</th>
                        <th class="py-2 px-3 text-right">Actual Cut</th>
                        <th class="py-2 px-3 text-right">Waste</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($run->sheetCuts as $cut)
                        <tr>
                            <td class="py-2 px-3 font-medium">{{ $cut->sheet_size_label }}</td>
                            <td class="py-2 px-3">{{ $cut->rolls_consumed }}</td>
                            <td class="py-2 px-3 text-right">{{ number_format($cut->expected_sheets) }}</td>
                            <td class="py-2 px-3 text-right font-semibold">{{ number_format($cut->actual_sheets_cut) }}</td>
                            <td class="py-2 px-3 text-right text-amber-700">{{ number_format($cut->waste_sheets) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="pt-6 border-t border-slate-200 flex justify-between text-xs text-slate-500">
            <span>Notes: {{ $run->notes ?? 'None' }}</span>
            <span>Prepared by: Mirza Sohail</span>
        </div>
    </div>
</body>
</html>

