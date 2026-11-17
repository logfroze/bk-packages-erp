<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Roznamcha Slip — BK Packages Okara ({{ $date }})</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background-color: #f1f5f9;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
        .num-tabular {
            font-variant-numeric: tabular-nums;
        }
        @media print {
            body {
                background: white !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .print-card {
                box-shadow: none !important;
                border: 1px solid #94a3b8 !important;
                margin: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
                border-radius: 0 !important;
                padding: 16px !important;
            }
            @page {
                size: auto;
                margin: 8mm 10mm;
            }
        }
    </style>
</head>
<body class="py-8 px-4 text-slate-800 text-xs">

    <!-- Action Bar (Hidden on Print) -->
    <div class="max-w-2xl mx-auto mb-4 flex items-center justify-between no-print bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <a href="{{ route('reports.index', ['date' => $date]) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 inline-flex items-center gap-1">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span> Back to Daily Report
        </a>
        <div class="flex items-center gap-2">
            <button onclick="copySlipSummary()" type="button" class="px-3 py-1.5 rounded-lg bg-emerald-700 text-white text-xs font-semibold hover:bg-emerald-800 shadow-xs inline-flex items-center gap-1">
                <span class="material-symbols-outlined text-[16px]">content_copy</span> Copy Slip Text
            </button>
            <button onclick="window.print()" type="button" class="px-4 py-1.5 rounded-lg bg-navy-900 bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 shadow-xs inline-flex items-center gap-1">
                <span class="material-symbols-outlined text-[16px]">print</span> Print Slip (Thermal / A4)
            </button>
        </div>
    </div>

    <!-- The Printable Receipt Slip Container -->
    <div class="print-card max-w-2xl mx-auto bg-white rounded-2xl border border-slate-300 shadow-md p-6 sm:p-8">
        
        <!-- Header Matching Factory Paperwork -->
        <div class="text-center border-b-2 border-slate-800 pb-4 mb-4">
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">BK PACKAGES OKARA</h1>
            <p class="text-xs font-semibold text-slate-700 uppercase tracking-wide mt-0.5">MANUFACTURERS OF CORRUGATED CARTONS, BOARD & GENERAL SUPPLIERS</p>
            <p class="text-[11px] text-slate-600">3-KM Depalpur Road, Okara · Cell: 0322-6200815, 0306-9013502</p>
            <div class="inline-block mt-2 px-3 py-1 bg-slate-900 text-white text-[11px] font-bold rounded uppercase tracking-wider">
                DAILY FACTORY PROGRESS & ROZNAMCHA SLIP
            </div>
        </div>

        <!-- Meta Details Row -->
        <div class="grid grid-cols-2 gap-2 text-xs border-b border-dashed border-slate-300 pb-3 mb-4">
            <div>
                <span class="text-slate-500 font-medium">Slip Ref:</span> 
                <span class="font-bold text-slate-900 font-mono">BK-ROZ-{{ \Carbon\Carbon::parse($date)->format('Ymd') }}</span>
            </div>
            <div class="text-right">
                <span class="text-slate-500 font-medium">Date:</span> 
                <span class="font-bold text-slate-900 font-mono">{{ \Carbon\Carbon::parse($date)->format('d-m-Y (l)') }}</span>
            </div>
            <div>
                <span class="text-slate-500 font-medium">Prepared By:</span> 
                <span class="font-bold text-slate-900">Mirza Sohail (Admin)</span>
            </div>
            <div class="text-right">
                <span class="text-slate-500 font-medium">Printed At:</span> 
                <span class="font-medium text-slate-700 font-mono">{{ now()->format('h:i A, d-M-Y') }}</span>
            </div>
        </div>

        <!-- Section 1: Executive Key Metrics -->
        <div class="mb-4">
            <div class="bg-slate-100 px-3 py-1.5 font-bold uppercase tracking-wider text-[11px] text-slate-800 border-l-4 border-slate-800 mb-2">
                1. Executive Operational Metrics
            </div>
            <table class="w-full text-left border-collapse text-xs">
                <tbody class="divide-y divide-slate-200">
                    <tr>
                        <td class="py-1.5 font-medium text-slate-600">Cartons Dispatched Outward:</td>
                        <td class="py-1.5 text-right font-bold font-mono text-slate-900">{{ number_format($summary['dispatched_pcs']) }} Pcs ({{ $summary['trucks_out'] }} Vehicles, {{ number_format($summary['dispatched_bundles']) }} Bdls)</td>
                    </tr>
                    <tr>
                        <td class="py-1.5 font-medium text-slate-600">Raw Material Inward (Paper Reels):</td>
                        <td class="py-1.5 text-right font-bold font-mono text-slate-900">{{ number_format($summary['paper_reels_weight_tons'], 2) }} Tons ({{ number_format($summary['paper_reels_weight_kg']) }} kg, {{ $summary['trucks_in'] }} Trucks)</td>
                    </tr>
                    <tr>
                        <td class="py-1.5 font-medium text-slate-600">Pre-Printed Top Sheets Added:</td>
                        <td class="py-1.5 text-right font-bold font-mono text-slate-900">{{ number_format($summary['top_sheets_received']) }} Pcs (Valuation: Rs. {{ number_format($summary['top_sheets_valuation']) }})</td>
                    </tr>
                    <tr>
                        <td class="py-1.5 font-medium text-slate-600">Corrugations & Production Output:</td>
                        <td class="py-1.5 text-right font-bold font-mono text-slate-900">{{ number_format($summary['production_pcs']) }} Cartons ({{ number_format($summary['nalis_produced']) }} Nalis)</td>
                    </tr>
                    <tr>
                        <td class="py-1.5 font-medium text-slate-600">Labour Workforce & OT:</td>
                        <td class="py-1.5 text-right font-bold font-mono text-slate-900">{{ $summary['workers_present'] }} Present ({{ $summary['overtime_hours'] }} hrs OT, Rs. {{ number_format($summary['wages_accrued']) }} Wages)</td>
                    </tr>
                    <tr>
                        <td class="py-1.5 font-medium text-slate-600">Petty Cash Factory Expenses:</td>
                        <td class="py-1.5 text-right font-bold font-mono text-rose-700">Rs. {{ number_format($summary['petty_cash_spent']) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Section 2: Cash Roznamcha Settlement -->
        <div class="mb-4">
            <div class="bg-slate-100 px-3 py-1.5 font-bold uppercase tracking-wider text-[11px] text-slate-800 border-l-4 border-slate-800 mb-2">
                2. Daily Cash Roznamcha Settlement
            </div>
            <table class="w-full text-left border-collapse text-xs">
                <tbody class="divide-y divide-slate-200">
                    <tr>
                        <td class="py-1.5 font-medium text-slate-600">Gross Invoiced Sales:</td>
                        <td class="py-1.5 text-right font-bold font-mono text-slate-900">Rs. {{ number_format($summary['sales_invoiced']) }}</td>
                    </tr>
                    <tr>
                        <td class="py-1.5 font-medium text-emerald-700">Total Cash Inflow (Receipts & Inward Cash):</td>
                        <td class="py-1.5 text-right font-bold font-mono text-emerald-700">+ Rs. {{ number_format($summary['cash_received']) }}</td>
                    </tr>
                    <tr>
                        <td class="py-1.5 font-medium text-rose-700">Total Cash Outflow (Expenses & Payments):</td>
                        <td class="py-1.5 text-right font-bold font-mono text-rose-700">- Rs. {{ number_format($summary['cash_paid']) }}</td>
                    </tr>
                    <tr class="bg-slate-50 font-bold">
                        <td class="py-2 text-slate-900 uppercase">Net Operating Cashflow Today:</td>
                        <td class="py-2 text-right font-mono text-sm {{ $summary['net_cashflow'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ $summary['net_cashflow'] >= 0 ? '+' : '' }}Rs. {{ number_format($summary['net_cashflow']) }}
                        </td>
                    </tr>
                    <tr class="bg-slate-100 font-bold">
                        <td class="py-2 text-slate-900 uppercase">Closing Safe Cash Balance:</td>
                        <td class="py-2 text-right font-mono text-sm text-slate-900">
                            Rs. {{ number_format($summary['closing_cash_balance']) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Section 3: Dispatches Detail -->
        @if($dispatches->isNotEmpty())
        <div class="mb-4">
            <div class="bg-slate-100 px-3 py-1.5 font-bold uppercase tracking-wider text-[11px] text-slate-800 border-l-4 border-slate-800 mb-2">
                3. Dispatches & Delivery Challans
            </div>
            <table class="w-full text-left border-collapse text-[11px]">
                <thead>
                    <tr class="border-b border-slate-300 text-slate-500 font-semibold uppercase">
                        <th class="py-1">Challan / Pass</th>
                        <th class="py-1">Vehicle</th>
                        <th class="py-1">Customer Party</th>
                        <th class="py-1 text-center">Bdls</th>
                        <th class="py-1 text-right">Pcs</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono">
                    @foreach($dispatches as $d)
                    <tr>
                        <td class="py-1 font-bold text-slate-900">{{ $d->bilti_challan_number ?: $d->gate_pass_number }}</td>
                        <td class="py-1">{{ $d->vehicle_number }}</td>
                        <td class="py-1 font-sans">{{ $d->party->name ?? 'Customer' }}</td>
                        <td class="py-1 text-center">{{ $d->total_bundles ?: '-' }}</td>
                        <td class="py-1 text-right font-bold">{{ number_format($d->total_pieces) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Section 4: Raw Material Inward -->
        @if($inwardTrucks->isNotEmpty())
        <div class="mb-4">
            <div class="bg-slate-100 px-3 py-1.5 font-bold uppercase tracking-wider text-[11px] text-slate-800 border-l-4 border-slate-800 mb-2">
                4. Raw Material Inward (Paper Reels)
            </div>
            <table class="w-full text-left border-collapse text-[11px]">
                <thead>
                    <tr class="border-b border-slate-300 text-slate-500 font-semibold uppercase">
                        <th class="py-1">Gate Pass</th>
                        <th class="py-1">Vehicle</th>
                        <th class="py-1">Supplier Mill</th>
                        <th class="py-1 text-right">Net Wt (Kg)</th>
                        <th class="py-1 text-right">Tons</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono">
                    @foreach($inwardTrucks as $inw)
                    <tr>
                        <td class="py-1 font-bold text-slate-900">{{ $inw->gate_pass_number }}</td>
                        <td class="py-1">{{ $inw->vehicle_number }}</td>
                        <td class="py-1 font-sans">{{ $inw->party->name ?? 'Mill Supplier' }}</td>
                        <td class="py-1 text-right font-bold">{{ number_format($inw->net_weight_kg) }} kg</td>
                        <td class="py-1 text-right">{{ number_format($inw->net_weight_kg / 1000, 2) }} T</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Section 5: Daily Expenses Detail -->
        @if($pettyCashToday->isNotEmpty())
        <div class="mb-4">
            <div class="bg-slate-100 px-3 py-1.5 font-bold uppercase tracking-wider text-[11px] text-slate-800 border-l-4 border-slate-800 mb-2">
                5. Daily Petty Cash Expenses Disbursed
            </div>
            <table class="w-full text-left border-collapse text-[11px]">
                <thead>
                    <tr class="border-b border-slate-300 text-slate-500 font-semibold uppercase">
                        <th class="py-1">Receipt</th>
                        <th class="py-1">Category</th>
                        <th class="py-1">Recipient / Purpose</th>
                        <th class="py-1 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono">
                    @foreach($pettyCashToday as $pc)
                    <tr>
                        <td class="py-1 font-bold text-slate-900">{{ $pc->transaction_code ?: '-' }}</td>
                        <td class="py-1 font-sans">{{ $pc->category ?? 'General' }}</td>
                        <td class="py-1 font-sans">{{ $pc->spent_by_or_paid_to ?? 'Factory' }} ({{ $pc->description }})</td>
                        <td class="py-1 text-right font-bold {{ $pc->transaction_type === 'CASH_IN' ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ $pc->transaction_type === 'CASH_IN' ? '+' : '-' }}Rs. {{ number_format($pc->amount) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Three Official Signatures -->
        <div class="mt-8 pt-6 border-t-2 border-slate-800">
            <div class="grid grid-cols-3 gap-6 text-center text-xs">
                <div>
                    <div class="h-10 border-b border-slate-400"></div>
                    <p class="font-bold text-slate-900 mt-1">Mirza Sohail</p>
                    <p class="text-[10px] text-slate-500">Prepared by (Admin)</p>
                </div>
                <div>
                    <div class="h-10 border-b border-slate-400"></div>
                    <p class="font-bold text-slate-900 mt-1">Dr. Ghulam Abbas</p>
                    <p class="text-[10px] text-slate-500">Verified by (Manager)</p>
                </div>
                <div>
                    <div class="h-10 border-b border-slate-400"></div>
                    <p class="font-bold text-slate-900 mt-1">Company Owner</p>
                    <p class="text-[10px] text-slate-500">Approved by (Owner)</p>
                </div>
            </div>
            <div class="text-center text-[10px] text-slate-400 mt-6">
                BK PACKAGES ERP SYSTEM · OKARA PLANT · GENERATED SECURELY
            </div>
        </div>

    </div>

    <script>
    function copySlipSummary() {
        const text = `*BK PACKAGES OKARA — DAILY ROZNAMCHA SLIP*
Ref: BK-ROZ-{{ \Carbon\Carbon::parse($date)->format('Ymd') }}
Date: {{ \Carbon\Carbon::parse($date)->format('d-m-Y (l)') }}
──────────────────────
• Dispatches: {{ number_format($summary['dispatched_pcs']) }} Pcs ({{ $summary['trucks_out'] }} Vehicles, {{ number_format($summary['dispatched_bundles']) }} Bdls)
• Raw Material: {{ number_format($summary['paper_reels_weight_tons'], 2) }} Tons ({{ number_format($summary['paper_reels_weight_kg']) }} kg)
• Printed Imports: {{ number_format($summary['top_sheets_received']) }} Pcs (Rs. {{ number_format($summary['top_sheets_valuation']) }})
• Finished Production: {{ number_format($summary['production_pcs']) }} Cartons ({{ number_format($summary['nalis_produced']) }} Nalis)
• Labour: {{ $summary['workers_present'] }} Present (OT: {{ $summary['overtime_hours'] }} hrs, Wages: Rs. {{ number_format($summary['wages_accrued']) }})
• Daily Expenses: Rs. {{ number_format($summary['petty_cash_spent']) }}
──────────────────────
• Invoiced Sales: Rs. {{ number_format($summary['sales_invoiced']) }}
• Cash Inflow: Rs. {{ number_format($summary['cash_received']) }}
• Cash Outflow: Rs. {{ number_format($summary['cash_paid']) }}
• Net Cashflow: Rs. {{ number_format($summary['net_cashflow']) }}
• Safe Cash Balance: Rs. {{ number_format($summary['closing_cash_balance']) }}
──────────────────────
Prepared: Mirza Sohail | Verified: Dr. Ghulam Abbas | Approved: Owner`;

        navigator.clipboard.writeText(text).then(() => {
            alert('Receipt slip summary copied to clipboard!');
        });
    }
    </script>
</body>
</html>
