<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Delivery Challan — #{{ $challanNo }} (BK Packages / Manzoor Ali)</title>
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
            .challan-container {
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
            <a href="{{ route('gate.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 inline-flex items-center gap-1">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span> Gate Register
            </a>
            <span class="text-slate-300">|</span>
            <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">
                Daily Roznamcha
            </a>
        </div>
        <div class="flex items-center flex-wrap gap-2">
            <button onclick="toggleEditPanel()" type="button" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold inline-flex items-center gap-1">
                <span class="material-symbols-outlined text-[16px]">tune</span> Customize Items
            </button>
            <button id="btn-challan-img" onclick="downloadChallanImage()" type="button" class="px-3 py-1.5 rounded-lg bg-indigo-700 hover:bg-indigo-800 text-white text-xs font-semibold inline-flex items-center gap-1 shadow-xs">
                <span class="material-symbols-outlined text-[16px]">image</span> Download Image (PNG)
            </button>
            <button onclick="window.print()" type="button" class="px-4 py-1.5 rounded-lg bg-navy-900 bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 shadow-xs inline-flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">print</span> Print Delivery Challan
            </button>
        </div>
    </div>

    <!-- Quick Live Editor Drawer (Hidden on Print) -->
    <div id="edit-panel" class="hidden max-w-3xl mx-auto mb-6 bg-slate-50 border border-slate-300 rounded-xl p-5 no-print shadow-inner">
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[18px] text-blue-600">edit_note</span> Live Challan Adjuster (Audio Note Specification)
        </h4>
        <form method="GET" action="{{ url()->current() }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Vehicle No:</label>
                <input type="text" name="vehicle_no" value="{{ $vehicleNo }}" class="w-full rounded border-slate-300 py-1 px-2 text-xs font-bold" />
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Customer / Party:</label>
                <input type="text" name="party_name" value="{{ $partyName }}" class="w-full rounded border-slate-300 py-1 px-2 text-xs font-semibold" />
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Bundles Count:</label>
                <input type="number" name="bundles" id="bundles-input" value="{{ $items[0]['bundles'] ?? 1054 }}" oninput="recalcQty()" class="w-full rounded border-slate-300 py-1 px-2 text-xs font-bold font-mono" />
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Pcs / Bundle (10 or 20):</label>
                <select name="pcs_per_bundle" id="pcs-input" onchange="recalcQty()" class="w-full rounded border-slate-300 py-1 px-2 text-xs font-bold">
                    <option value="10" {{ ($items[0]['pcs_per_bundle'] ?? 10) == 10 ? 'selected' : '' }}>10 Pcs per bundle</option>
                    <option value="20" {{ ($items[0]['pcs_per_bundle'] ?? 10) == 20 ? 'selected' : '' }}>20 Pcs per bundle</option>
                    <option value="25" {{ ($items[0]['pcs_per_bundle'] ?? 10) == 25 ? 'selected' : '' }}>25 Pcs per bundle</option>
                    <option value="50" {{ ($items[0]['pcs_per_bundle'] ?? 10) == 50 ? 'selected' : '' }}>50 Pcs per bundle</option>
                </select>
            </div>
            <div class="sm:col-span-2 md:col-span-3">
                <label class="block font-semibold text-slate-600 mb-1">Carton Description:</label>
                <input type="text" name="item_name" value="{{ $items[0]['description'] ?? 'CARTON SHAHBAZ BANASPATI 1KG*12P' }}" class="w-full rounded border-slate-300 py-1 px-2 text-xs" />
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full py-1.5 rounded bg-blue-600 text-white font-bold hover:bg-blue-700">Apply Changes</button>
            </div>
        </form>
    </div>

    <!-- The Official Physical Document Representation -->
    <div class="challan-container max-w-3xl mx-auto bg-white border-2 border-slate-900 shadow-xl p-8 sm:p-10">
        
        <!-- Company Header Exactly Matching Reference: Delivery chalan.jpeg -->
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
            <span class="inline-block px-8 py-1.5 border-2 border-slate-900 text-sm font-black uppercase tracking-widest bg-white">
                DELIVERY CHALLAN
            </span>
        </div>

        <!-- Top Customer & Challan Information Grid -->
        <div class="grid grid-cols-2 gap-4 text-xs border-y-2 border-slate-900 py-3 my-4">
            <!-- Left Side: Party Details -->
            <div class="space-y-1.5 pr-4 border-r border-slate-300">
                <div class="flex items-start gap-2">
                    <span class="font-bold text-slate-900 w-16 shrink-0 uppercase">To:</span>
                    <span class="font-bold text-slate-950 text-[13px] leading-tight">{{ $partyName }}</span>
                </div>
                <div class="flex items-start gap-2">
                    <span class="font-bold text-slate-600 w-16 shrink-0 uppercase">Address:</span>
                    <span class="text-slate-800 font-medium">{{ $partyAddress }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-600 w-16 shrink-0 uppercase">Phone:</span>
                    <span class="text-slate-800 font-mono font-bold">{{ $partyPhone }}</span>
                </div>
            </div>

            <!-- Right Side: Challan Meta -->
            <div class="space-y-1.5 pl-4">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-900 uppercase">Challan No:</span>
                    <span class="font-black text-slate-950 font-mono text-sm border-b border-dotted border-slate-900 px-2">{{ $challanNo }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-900 uppercase">Date:</span>
                    <span class="font-bold text-slate-950 font-mono border-b border-dotted border-slate-900 px-2">{{ $date }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-900 uppercase">Vehicle No:</span>
                    <span class="font-black text-slate-950 font-mono uppercase bg-slate-100 border border-slate-300 px-2 py-0.5 rounded">{{ $vehicleNo }}</span>
                </div>
            </div>
        </div>

        <!-- Main Items Table With Audio Note Specifications (Unit: Bundles + Pcs/Bundle) -->
        <div class="border-2 border-slate-900 mt-4">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b-2 border-slate-900 bg-slate-100 text-slate-950 font-black text-center uppercase tracking-wide">
                        <th class="py-2.5 px-3 border-r-2 border-slate-900 w-12">SR.</th>
                        <th class="py-2.5 px-4 border-r-2 border-slate-900 text-left">Discription</th>
                        <!-- Unit Column with Two Sub-columns (Audio Note Specification) -->
                        <th colspan="2" class="py-1 px-2 border-r-2 border-slate-900 w-44">
                            <div class="border-b border-slate-900 pb-1">Unit</div>
                            <div class="grid grid-cols-2 text-[10px] pt-1">
                                <span class="border-r border-slate-900 pr-1">Bundles</span>
                                <span class="pl-1">Pcs/Bdl</span>
                            </div>
                        </th>
                        <th class="py-2.5 px-4 text-right w-28">QTY</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-300 text-slate-900">
                    @foreach($items as $index => $item)
                        <tr class="align-top">
                            <td class="py-3 px-3 border-r-2 border-slate-900 text-center font-bold font-mono">{{ $item['sr'] }}</td>
                            <td class="py-3 px-4 border-r-2 border-slate-900 font-bold uppercase tracking-tight text-[13px]">
                                {{ $item['description'] }}
                            </td>
                            <!-- Bundles count -->
                            <td class="py-3 px-2 border-r border-slate-900 text-center font-bold font-mono text-[13px] w-22">
                                {{ number_format($item['bundles']) }}
                            </td>
                            <!-- Pcs per bundle (10 or 20) -->
                            <td class="py-3 px-2 border-r-2 border-slate-900 text-center font-semibold font-mono text-[12px] text-slate-700 w-22">
                                {{ $item['pcs_per_bundle'] }} Pcs
                            </td>
                            <!-- Total Quantity -->
                            <td class="py-3 px-4 text-right font-black font-mono text-sm text-slate-950">
                                {{ number_format($item['total_qty']) }}
                            </td>
                        </tr>
                    @endforeach

                    <!-- Filler Blank Rows to preserve authentic physical voucher aspect ratio -->
                    @for($i = count($items); $i < 6; $i++)
                        <tr class="h-8">
                            <td class="border-r-2 border-slate-900 text-center text-slate-300 font-mono">{{ $i + 1 }}</td>
                            <td class="border-r-2 border-slate-900"></td>
                            <td class="border-r border-slate-900"></td>
                            <td class="border-r-2 border-slate-900"></td>
                            <td></td>
                        </tr>
                    @endfor
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-900 bg-slate-50 font-black text-slate-950">
                        <td colspan="2" class="py-2.5 px-4 border-r-2 border-slate-900 text-right uppercase tracking-wider text-xs">
                            Total:
                        </td>
                        <td class="py-2.5 px-2 border-r border-slate-900 text-center font-mono text-[13px]">
                            {{ number_format(array_sum(array_column($items, 'bundles'))) }} Bdls
                        </td>
                        <td class="border-r-2 border-slate-900 bg-slate-100/50"></td>
                        <td class="py-2.5 px-4 text-right font-mono text-base text-slate-950">
                            {{ number_format($grandTotalQty) }} <span class="text-[11px] font-normal">Pcs</span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Physical Terms Note -->
        <div class="mt-4 text-[11px] text-slate-700 italic border-b border-slate-200 pb-3">
            Note: Received the above mentioned goods in full quantity and sound condition. No claims will be accepted after delivery.
        </div>

        <!-- Bottom Signatures Matching Factory Format -->
        <div class="mt-14 pt-4">
            <div class="grid grid-cols-2 gap-12 text-center text-xs">
                <div>
                    <div class="border-b-2 border-slate-900 mx-8 pb-1"></div>
                    <p class="font-bold text-slate-900 mt-2 uppercase tracking-wider">Receiver's Signature</p>
                    <p class="text-[10px] text-slate-500">Name / Stamp / Phone</p>
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
    async function downloadChallanImage() {
        const btn = document.getElementById('btn-challan-img');
        const origHtml = btn.innerHTML;
        btn.innerHTML = 'Rendering...';
        btn.disabled = true;

        try {
            const card = document.querySelector('.challan-container');
            const canvas = await html2canvas(card, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff'
            });
            const link = document.createElement('a');
            link.download = 'Delivery_Challan_{{ $challanNo }}.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        } catch (e) {
            console.error('Image capture error:', e);
            alert('Could not render image. Please use Print Delivery Challan.');
        } finally {
            btn.innerHTML = origHtml;
            btn.disabled = false;
        }
    }

    function toggleEditPanel() {
        const el = document.getElementById('edit-panel');
        el.classList.toggle('hidden');
    }

    function recalcQty() {
        const bundles = parseInt(document.getElementById('bundles-input').value) || 0;
        const pcsPerBundle = parseInt(document.getElementById('pcs-input').value) || 10;
        // Optional instant preview update if needed
    }
    </script>
</body>
</html>
