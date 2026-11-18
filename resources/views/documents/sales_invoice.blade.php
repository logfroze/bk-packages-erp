<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sales Bill / Receipt #{{ $billNo }} — Manzoor Ali / BK Packages</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
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
            .bill-container {
                box-shadow: none !important;
                border: 2px solid #000 !important;
                margin: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
                border-radius: 0 !important;
                padding: 24px !important;
            }
            @page {
                size: A4 portrait;
                margin: 12mm 15mm;
            }
        }
    </style>
</head>
<body class="py-8 px-4 text-slate-900">

    <!-- Action Bar (Hidden on Print) -->
    <div class="max-w-3xl mx-auto mb-4 flex flex-wrap items-center justify-between gap-3 no-print bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-2">
            <a href="{{ route('khata.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 inline-flex items-center gap-1">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span> Party Khata
            </a>
            <span class="text-slate-300">|</span>
            <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">
                Daily Roznamcha
            </a>
        </div>
        <div class="flex items-center flex-wrap gap-2">
            <button onclick="toggleEditPanel()" type="button" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold inline-flex items-center gap-1">
                <span class="material-symbols-outlined text-[16px]">tune</span> Customize Bill
            </button>
            <button id="btn-bill-img" onclick="downloadBillImage()" type="button" class="px-3 py-1.5 rounded-lg bg-indigo-700 hover:bg-indigo-800 text-white text-xs font-semibold inline-flex items-center gap-1 shadow-xs">
                <span class="material-symbols-outlined text-[16px]">image</span> Download Image (PNG)
            </button>
            <button onclick="window.print()" type="button" class="px-4 py-1.5 rounded-lg bg-navy-900 bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 shadow-xs inline-flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">print</span> Print Bill / Receipt
            </button>
        </div>
    </div>

    <!-- Quick Live Editor Drawer (Hidden on Print) -->
    <div id="edit-panel" class="hidden max-w-3xl mx-auto mb-6 bg-slate-50 border border-slate-300 rounded-xl p-5 no-print shadow-inner">
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[18px] text-blue-600">edit_document</span> Live Bill Receipt Adjuster
        </h4>
        <form method="GET" action="{{ url()->current() }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 text-xs">
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Bill / Ref No:</label>
                <input type="text" name="bill_no" value="{{ $billNo }}" class="w-full rounded border-slate-300 py-1 px-2 text-xs font-bold font-mono" />
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Customer / Party:</label>
                <input type="text" name="party_name" value="{{ $partyName }}" class="w-full rounded border-slate-300 py-1 px-2 text-xs font-semibold" />
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">City / Address:</label>
                <input type="text" name="party_address" value="{{ $partyAddress }}" class="w-full rounded border-slate-300 py-1 px-2 text-xs" />
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Phone Number:</label>
                <input type="text" name="party_phone" value="{{ $partyPhone }}" class="w-full rounded border-slate-300 py-1 px-2 text-xs font-mono" />
            </div>
            <div class="sm:col-span-2 flex items-end">
                <button type="submit" class="w-full py-1.5 rounded bg-blue-600 text-white font-bold hover:bg-blue-700">Apply Changes to Bill</button>
            </div>
        </form>
    </div>

    <!-- The Official Physical Document Representation (Matching Bill receipt.jpeg) -->
    <div class="bill-container max-w-3xl mx-auto bg-white border-2 border-slate-900 shadow-xl p-8 sm:p-10">
        
        <!-- Company Header Exactly Matching Reference: Bill receipt.jpeg -->
        <div class="text-center pb-3">
            <h1 class="text-3xl font-black tracking-tight text-slate-950 uppercase font-sans">MANZOOR ALI</h1>
            <p class="text-xs font-bold text-slate-800 uppercase tracking-wide mt-0.5">
                MANUFACTURERS OF CORRUGATED CARTONS, BOARD & GENERAL ORDER SUPPLIERS
            </p>
            <p class="text-xs font-semibold text-slate-700 mt-0.5">
                3KM Depalpur Road Okara
            </p>
            <p class="text-xs font-bold text-slate-800 font-mono tracking-wider mt-0.5">
                CELL : 03226200815, 03069013502
            </p>
        </div>

        <!-- Document Type Title Box -->
        <div class="text-center my-3">
            <span class="inline-block px-8 py-1 border-2 border-slate-900 text-sm font-black uppercase tracking-widest bg-white">
                BILL / RECEIPT
            </span>
        </div>

        <!-- Top Customer & Bill Meta Grid -->
        <div class="border-y-2 border-slate-900 py-3 my-4">
            <div class="flex flex-col sm:flex-row justify-between gap-4 text-xs">
                <!-- Left Details -->
                <div class="space-y-1.5 flex-1 pr-4">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-900 w-16 uppercase">Ref; No:</span>
                        <span class="font-black text-slate-950 font-mono text-sm">{{ $billNo }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-bold text-slate-900 w-16 uppercase">To:</span>
                        <span class="font-bold text-slate-950 text-[13px] leading-tight">{{ $partyName }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-bold text-slate-600 w-16 uppercase">Address:</span>
                        <span class="text-slate-800 font-medium">{{ $partyAddress }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-600 w-16 uppercase">Phone:</span>
                        <span class="text-slate-800 font-mono font-bold">{{ $partyPhone }}</span>
                    </div>
                </div>

                <!-- Right Details -->
                <div class="sm:text-right space-y-1.5 sm:border-l border-slate-300 sm:pl-6">
                    <div class="flex sm:justify-end items-center gap-2">
                        <span class="font-bold text-slate-900 uppercase">Date:</span>
                        <span class="font-black text-slate-950 font-mono text-sm border-b border-dotted border-slate-900 px-3">{{ $date }}</span>
                    </div>
                    <div class="text-[11px] text-slate-500 mt-2">
                        Payment Mode: <span class="font-bold text-slate-800">Cash / Credit Khata</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Items Table (Matching Reference: SR, Discription, Qty, Rate, Amount) -->
        <div class="border-2 border-slate-900 mt-4">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b-2 border-slate-900 bg-slate-100 text-slate-950 font-black text-center uppercase tracking-wide">
                        <th class="py-2.5 px-3 border-r-2 border-slate-900 w-12">SR.</th>
                        <th class="py-2.5 px-4 border-r-2 border-slate-900 text-left">Discription</th>
                        <th class="py-2.5 px-3 border-r-2 border-slate-900 w-28 text-right">Qty</th>
                        <th class="py-2.5 px-3 border-r-2 border-slate-900 w-24 text-right">Rate</th>
                        <th class="py-2.5 px-4 text-right w-36">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-300 text-slate-900">
                    @foreach($items as $index => $item)
                        <tr class="align-top">
                            <td class="py-3.5 px-3 border-r-2 border-slate-900 text-center font-bold font-mono">{{ $item['sr'] }}</td>
                            <td class="py-3.5 px-4 border-r-2 border-slate-900 font-bold uppercase tracking-tight text-[13px]">
                                {{ $item['description'] }}
                            </td>
                            <td class="py-3.5 px-3 border-r-2 border-slate-900 text-right font-bold font-mono text-[13px]">
                                {{ number_format($item['qty']) }}
                            </td>
                            <td class="py-3.5 px-3 border-r-2 border-slate-900 text-right font-mono text-slate-800">
                                {{ number_format($item['rate'], 2) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-black font-mono text-[13px] text-slate-950">
                                {{ number_format($item['amount']) }}/-
                            </td>
                        </tr>
                    @endforeach

                    <!-- Filler Blank Rows to preserve authentic physical voucher aspect ratio -->
                    @for($i = count($items); $i < 6; $i++)
                        <tr class="h-8">
                            <td class="border-r-2 border-slate-900 text-center text-slate-300 font-mono">{{ $i + 1 }}</td>
                            <td class="border-r-2 border-slate-900"></td>
                            <td class="border-r-2 border-slate-900"></td>
                            <td class="border-r-2 border-slate-900"></td>
                            <td></td>
                        </tr>
                    @endfor
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-900 bg-slate-50 font-black text-slate-950">
                        <td colspan="4" class="py-3 px-4 border-r-2 border-slate-900 text-right uppercase tracking-wider text-xs">
                            Total:
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-lg text-slate-950 font-black">
                            {{ number_format($grandTotalAmount) }}/-
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Bottom Signatures Matching Factory Format -->
        <div class="mt-16 pt-4">
            <div class="grid grid-cols-2 gap-12 text-center text-xs">
                <div>
                    <div class="border-b-2 border-slate-900 mx-8 pb-1"></div>
                    <p class="font-bold text-slate-900 mt-2 uppercase tracking-wider">Customer's Signature</p>
                    <p class="text-[10px] text-slate-500">Name / Stamp</p>
                </div>
                <div>
                    <div class="border-b-2 border-slate-900 mx-8 pb-1"></div>
                    <p class="font-bold text-slate-950 mt-2 uppercase tracking-wider">For: MANZOOR ALI / BK PACKAGES</p>
                    <p class="text-[10px] text-slate-500">Authorized Signature & Stamp</p>
                </div>
            </div>
        </div>

    </div>

    <script src="{{ asset('js/html2canvas.min.js') }}"></script>
    <script>
    async function downloadBillImage() {
        const btn = document.getElementById('btn-bill-img');
        const origHtml = btn.innerHTML;
        btn.innerHTML = 'Rendering...';
        btn.disabled = true;

        try {
            const card = document.querySelector('.bill-container');
            const canvas = await html2canvas(card, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff'
            });
            const link = document.createElement('a');
            link.download = 'Invoice_Bill_{{ $invoiceNo }}.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        } catch (e) {
            console.error('Image capture error:', e);
            alert('Could not render image. Please use Print Bill.');
        } finally {
            btn.innerHTML = origHtml;
            btn.disabled = false;
        }
    }

    function toggleEditPanel() {
        const el = document.getElementById('edit-panel');
        el.classList.toggle('hidden');
    }
    </script>
</body>
</html>
