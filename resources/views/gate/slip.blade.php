<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gate Pass {{ $gate->gate_pass_number }} — BK PACKAGES</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
        }
    </style>
</head>
<body class="bg-slate-100 py-8 px-4 font-sans text-slate-900">
    <div class="max-w-lg mx-auto bg-white border border-slate-300 rounded-xl p-8 shadow-sm">
        <!-- Print Controls -->
        <div class="flex items-center justify-between gap-3 pb-6 mb-6 border-b border-slate-200 no-print">
            <a href="{{ route('gate.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">← Back to Gate</a>
            <div class="flex items-center gap-2">
                @php
                    $waText = urlencode("*BK PACKAGES - GATE PASS ({$gate->gate_pass_number})*\nParty: " . ($gate->party->name ?? '') . "\nVehicle: {$gate->vehicle_number} ({$gate->driver_name})\nItem: {$gate->material_summary}\nQty: " . number_format($gate->total_pieces ?? 0) . " Pcs\nNet Weight: " . number_format($gate->net_weight_kg ?? 0) . " kg\nTime: {$gate->time_in->format('d-M-Y h:i A')}");
                @endphp
                <a href="https://wa.me/?text={{ $waText }}" target="_blank"
                   class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">
                    Share on WhatsApp
                </a>
                <button onclick="window.print()" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800">
                    Print Slip
                </button>
            </div>
        </div>

        <!-- Official Header -->
        <div class="text-center border-b-2 border-slate-900 pb-4 mb-5">
            <h1 class="text-xl font-bold tracking-tight uppercase">BK PACKAGES (PVT.) LTD.</h1>
            <p class="text-xs text-slate-600 mt-1">3-KM Depalpur Road, Okara · Ph: 044-2703465 / 0322-6200815</p>
            <div class="mt-3 inline-block px-3 py-1 bg-slate-900 text-white text-xs font-bold uppercase tracking-wider rounded">
                {{ $gate->movement_type === 'INWARD_RAW_MATERIAL' ? 'Inward Material Receipt' : 'Outward Factory Gate Pass' }}
            </div>
        </div>

        <!-- Key Identifiers -->
        <div class="grid grid-cols-2 gap-y-3 text-sm mb-6">
            <div>
                <span class="text-xs text-slate-500 block">Gate Pass #</span>
                <span class="font-bold text-base">{{ $gate->gate_pass_number }}</span>
            </div>
            <div class="text-right">
                <span class="text-xs text-slate-500 block">Date & Time</span>
                <span class="font-semibold">{{ $gate->time_in->format('d M Y, h:i A') }}</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 block">Party / Client</span>
                <span class="font-semibold">{{ $gate->party->name ?? '-' }}</span>
            </div>
            <div class="text-right">
                <span class="text-xs text-slate-500 block">Bilti / Challan Ref</span>
                <span class="font-semibold">{{ $gate->bilti_challan_number ?? '-' }}</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 block">Vehicle Number</span>
                <span class="font-bold">{{ $gate->vehicle_number }} ({{ $gate->vehicle_type }})</span>
            </div>
            <div class="text-right">
                <span class="text-xs text-slate-500 block">Driver Name & Phone</span>
                <span class="font-semibold">{{ $gate->driver_name }} ({{ $gate->driver_phone }})</span>
            </div>
        </div>

        <!-- Cargo & Weight Table -->
        <div class="border border-slate-200 rounded-lg p-4 bg-slate-50 mb-6 space-y-2 text-sm">
            <div class="flex justify-between font-semibold border-b border-slate-200 pb-2">
                <span>Cargo Description</span>
                <span>{{ $gate->material_summary ?? 'Corrugated Cartons' }}</span>
            </div>
            @if($gate->total_pieces)
            <div class="flex justify-between">
                <span class="text-slate-600">Total Quantity</span>
                <span class="font-bold">{{ number_format($gate->total_pieces) }} Pcs ({{ $gate->total_bundles ?? 0 }} Bundles)</span>
            </div>
            @endif
            <div class="flex justify-between">
                <span class="text-slate-600">Gross Weight</span>
                <span>{{ $gate->gross_weight_kg ? number_format($gate->gross_weight_kg) . ' kg' : 'Pending' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-600">Tare Weight</span>
                <span>{{ $gate->tare_weight_kg ? number_format($gate->tare_weight_kg) . ' kg' : 'Pending' }}</span>
            </div>
            <div class="flex justify-between pt-2 border-t border-slate-300 font-bold text-base">
                <span>Net Cargo Weight</span>
                <span>{{ $gate->net_weight_kg ? number_format($gate->net_weight_kg) . ' kg' : 'Pending' }}</span>
            </div>
        </div>

        <!-- Signatures -->
        <div class="grid grid-cols-3 gap-4 pt-8 text-center text-xs text-slate-600">
            <div class="border-t border-slate-400 pt-2">Driver Signature</div>
            <div class="border-t border-slate-400 pt-2">Accountant (Mirza Sohail)</div>
            <div class="border-t border-slate-400 pt-2">Security Gate Officer</div>
        </div>
    </div>
</body>
</html>

