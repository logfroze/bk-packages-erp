@extends('layouts.app')

@section('title', 'Party Khata & Local Vendor Ledgers')
@section('breadcrumb', 'Party Khata')

@section('header_actions')
    @if(auth()->user()?->canEditOperations())
        <div class="flex items-center gap-2 flex-wrap">
            <button onclick="openModal('modal-new-local-tx')" class="px-3.5 py-2 rounded-lg bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700 shadow-xs flex items-center gap-1.5 transition-colors">
                <span class="material-symbols-outlined text-[16px]">storefront</span>
                <span>+ Local Party Entry (Snap / Bill)</span>
            </button>
            <button onclick="openModal('modal-custom-local-card')" class="px-3 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50 shadow-xs flex items-center gap-1.5 transition-colors">
                <span class="material-symbols-outlined text-[16px] text-blue-600">table_chart</span>
                <span>+ Custom Grid</span>
            </button>
            <button onclick="openModal('modal-new-party')" class="px-3.5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs transition-colors">
                + Add Party / Local Vendor
            </button>
            <button onclick="openModal('modal-voucher')" class="px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50 shadow-xs transition-colors">
                + Record Voucher / Payment
            </button>
        </div>
    @endif
@endsection

@section('content')
<div class="space-y-6">

    <!-- Top Header & Classification Filter Tabs -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Party Khata & Vendor Management</h1>
            <p class="text-xs text-slate-500 mt-1">Manage corporate carton buyers, paper mills, and local Okara vendor shops (hardware, spare parts, food, fuel)</p>
        </div>

        <!-- Filter Classification Tabs -->
        <div class="inline-flex rounded-xl bg-white border border-slate-200 p-1 shadow-xs flex-wrap">
            <a href="{{ route('khata.index', ['type' => 'CUSTOMER', 'classification' => 'ALL']) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $type === 'CUSTOMER' && $classification === 'ALL' ? 'bg-navy-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                Carton Buyers
            </a>
            <a href="{{ route('khata.index', ['type' => 'ALL', 'classification' => 'LOCAL_VENDOR_SHOP']) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $classification === 'LOCAL_VENDOR_SHOP' ? 'bg-navy-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                Local Vendor Shops
            </a>
            <a href="{{ route('khata.index', ['type' => 'SUPPLIER', 'classification' => 'PAPER_MILL']) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $classification === 'PAPER_MILL' ? 'bg-navy-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                Paper Mills
            </a>
            <a href="{{ route('khata.index', ['type' => 'ALL', 'classification' => 'ALL']) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $type === 'ALL' && $classification === 'ALL' ? 'bg-navy-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                All Accounts
            </a>
        </div>
    </div>

    <!-- Top Area Grid: 4 Core Finance Cards + TOP RIGHT 'Local Party' Section -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

        <!-- Left 8 Columns: 4 Financial Stat Cards -->
        <div class="lg:col-span-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Receivables (Lena)</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">south_west</span>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-bold text-navy-900 num-tabular">
                    Rs. {{ number_format($totalReceivables) }}
                </div>
                <div class="text-xs text-slate-400 mt-1">Pending from carton buyers</div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Payables (Dena)</span>
                    <div class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">north_east</span>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-bold text-red-600 num-tabular">
                    Rs. {{ number_format($totalPayables) }}
                </div>
                <div class="text-xs text-slate-400 mt-1">Paper mills & local vendors</div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Local Vendor Dena</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">store</span>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-bold text-amber-700 num-tabular">
                    Rs. {{ number_format($localVendorPayables) }}
                </div>
                <div class="text-xs text-slate-400 mt-1">Hardware, fuel & spare shops</div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Cash in Hand / Till</span>
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[18px]">payments</span>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-bold text-emerald-600 num-tabular">
                    Rs. {{ number_format($cashInHand) }}
                </div>
                <div class="text-xs text-slate-400 mt-1">Available office cash</div>
            </div>
        </div>

        <!-- Right 4 Columns: TOP RIGHT SECTION 'Local Party' Daily Barter & Small Expenses Hub -->
        <div class="lg:col-span-4 bg-gradient-to-br from-amber-500/10 via-white to-amber-500/5 rounded-2xl border border-amber-200/80 p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-amber-500 text-white flex items-center justify-center shadow-xs">
                            <span class="material-symbols-outlined text-[18px]">storefront</span>
                        </div>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Local Party</h2>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-200">
                        Daily Micro Khata
                    </span>
                </div>
                <p class="text-[11px] text-slate-500">
                    Daily small expenses from local shops (food portions, scales, fuel) & counter goods they took (sheets, cartons).
                </p>

                <!-- Dual Micro Metrics -->
                <div class="mt-4 grid grid-cols-2 gap-3 bg-white/80 p-3 rounded-xl border border-amber-100">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">We Bought (Kharch)</span>
                        <span class="text-sm font-extrabold text-amber-800 num-tabular">
                            Rs. {{ number_format($totalWeBought) }}
                        </span>
                        <span class="text-[10px] text-slate-400 block mt-0.5">Food, Tools, Diesel</span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">They Took (Sale)</span>
                        <span class="text-sm font-extrabold text-emerald-700 num-tabular">
                            Rs. {{ number_format($totalTheyBought) }}
                        </span>
                        <span class="text-[10px] text-slate-400 block mt-0.5">Sheets, Cartons, Scrap</span>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-between text-xs px-1">
                    <span class="font-semibold text-slate-600">Net Local Baqaya:</span>
                    <span class="font-extrabold num-tabular {{ $netLocalBalance >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
                        Rs. {{ number_format(abs($netLocalBalance)) }} {{ $netLocalBalance >= 0 ? '(Lena / Dr)' : '(Dena / Cr)' }}
                    </span>
                </div>
            </div>

            <!-- Quick Action Triggers in Top Right Section -->
            <div class="mt-4 pt-3 border-t border-amber-200/60 flex items-center gap-2">
                <button type="button" onclick="openModal('modal-new-local-tx')" class="flex-1 px-3 py-2 rounded-xl bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700 shadow-xs flex items-center justify-center gap-1.5 transition-colors">
                    <span class="material-symbols-outlined text-[16px]">photo_camera</span>
                    <span>+ Snap / Add Entry</span>
                </button>
                <a href="#local-party-section" class="px-3 py-2 rounded-xl border border-amber-300 bg-white text-amber-900 text-xs font-semibold hover:bg-amber-50 shadow-xs flex items-center gap-1 transition-colors">
                    <span>View All</span>
                    <span class="material-symbols-outlined text-[15px]">arrow_downward</span>
                </a>
            </div>
        </div>

    </div>

    <!-- SECTION: LOCAL PARTY DUAL BARTER & DAILY EXPENSE REGISTER (FULL CUSTOMIZABLE TABLE) -->
    <div id="local-party-section" class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
        <!-- Section Header -->
        <div class="p-5 border-b border-slate-200 bg-slate-50/70 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span>Local Party & Small Shop Daily Expenses Register</span>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                            {{ $localTransactions->count() }} Entries
                        </span>
                    </h2>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Details of items bought from small shops (e.g. 4 portions food, measuring scales) and items they took (paper sheets, cartons). Includes camera receipt snapshots and fully customizable columns.
                </p>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <button onclick="openModal('modal-new-local-tx')" class="px-3 py-1.5 rounded-lg bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700 shadow-xs flex items-center gap-1 transition-colors">
                    <span class="material-symbols-outlined text-[15px]">add_circle</span>
                    <span>+ Add Local Entry</span>
                </button>
                <button onclick="openModal('modal-custom-local-card')" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50 shadow-xs flex items-center gap-1 transition-colors">
                    <span class="material-symbols-outlined text-[15px] text-blue-600">dashboard_customize</span>
                    <span>+ Add Custom Table</span>
                </button>
                <a href="{{ route('media_vault.index', ['category' => 'LOCAL_PARTY_BILL']) }}" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-50 shadow-xs flex items-center gap-1 transition-colors">
                    <span class="material-symbols-outlined text-[15px] text-purple-600">photo_library</span>
                    <span>View Media Vault</span>
                </a>
            </div>
        </div>

        <!-- Filter Sub-Tabs for Local Party Table -->
        <div class="p-3 border-b border-slate-100 bg-white flex items-center gap-2 overflow-x-auto text-xs">
            <button onclick="filterLocalRows('ALL')" id="local-tab-all" class="local-filter-tab px-3 py-1 rounded-lg font-bold bg-navy-900 text-white">
                All Local Items ({{ $localTransactions->count() }})
            </button>
            <button onclick="filterLocalRows('WE_BOUGHT')" id="local-tab-we-bought" class="local-filter-tab px-3 py-1 rounded-lg font-semibold text-slate-600 hover:bg-slate-100">
                We Bought (Factory Expense)
            </button>
            <button onclick="filterLocalRows('THEY_BOUGHT')" id="local-tab-they-bought" class="local-filter-tab px-3 py-1 rounded-lg font-semibold text-slate-600 hover:bg-slate-100">
                They Took (Shop Counter Sale)
            </button>
        </div>

        <!-- Transactions Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 font-bold uppercase tracking-wider text-slate-600">
                        <th class="py-3 px-4">Date & Time</th>
                        <th class="py-3 px-4">Shop / Local Party</th>
                        <th class="py-3 px-4">Direction</th>
                        <th class="py-3 px-4">Item & Quantity Details</th>
                        <th class="py-3 px-4 text-right">Price / Amount</th>
                        <th class="py-3 px-4 text-center">Payment Status</th>
                        <th class="py-3 px-4 text-center">Bill / Photo</th>
                        <th class="py-3 px-4">Custom Fields / Notes</th>
                        <th class="py-3 px-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($localTransactions as $tx)
                        <tr class="hover:bg-amber-50/40 transition-colors local-item-row" data-type="{{ $tx->transaction_type }}">
                            <td class="py-3.5 px-4 text-slate-600 num-tabular whitespace-nowrap">
                                <span class="font-bold text-slate-800">{{ $tx->transaction_datetime?->format('d M Y') }}</span>
                                <span class="block text-[10px] text-slate-400">{{ $tx->transaction_datetime?->format('h:i A') }}</span>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 text-sm flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[15px] text-slate-400">store</span>
                                    <span>{{ $tx->shop_name }}</span>
                                </div>
                                <span class="text-[10px] text-slate-400">{{ $tx->shop_city }}</span>
                            </td>

                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase {{ $tx->typeBadgeClass() }}">
                                    {{ $tx->typeLabel() }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-900 text-xs">{{ $tx->item_description }}</div>
                                @if($tx->quantity_details)
                                    <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700">
                                        Qty: {{ $tx->quantity_details }}
                                    </span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-right num-tabular whitespace-nowrap">
                                <div class="text-sm font-extrabold {{ $tx->isWeBought() ? 'text-amber-800' : 'text-emerald-700' }}">
                                    Rs. {{ number_format($tx->total_amount) }}
                                </div>
                                @if($tx->unit_price > 0)
                                    <span class="text-[10px] text-slate-400">@ Rs. {{ number_format($tx->unit_price) }}</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $tx->paymentBadgeClass() }}">
                                    {{ str_replace('_', ' ', $tx->payment_status) }}
                                </span>
                            </td>

                            <!-- Bill / Receipt Photo Attachment -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($tx->receipt_photo_path)
                                    <button type="button" onclick="viewFullImage('{{ asset($tx->receipt_photo_path) }}', '{{ addslashes($tx->shop_name . ' — ' . $tx->item_description) }}', 'image', 'Local Shop Bill', 'Rs. {{ number_format($tx->total_amount) }}', '{{ addslashes($tx->shop_name) }}')"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 text-[11px] font-semibold hover:bg-blue-100 transition-colors" title="View attached bill">
                                        <span class="material-symbols-outlined text-[14px]">receipt</span>
                                        <span>View Bill</span>
                                    </button>
                                @else
                                    <span class="text-[10px] text-slate-400 italic">No Photo</span>
                                @endif
                            </td>

                            <!-- Custom Fields & Dynamic Notes -->
                            <td class="py-3.5 px-4 max-w-xs">
                                @if(!empty($tx->custom_fields) && is_array($tx->custom_fields))
                                    <div class="flex flex-wrap gap-1 mb-1">
                                        @foreach($tx->custom_fields as $fieldKey => $fieldVal)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 text-slate-700 border border-slate-200">
                                                <strong>{{ $fieldKey }}:</strong> {{ $fieldVal }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                @if($tx->notes)
                                    <p class="text-[11px] text-slate-500 line-clamp-1 truncate" title="{{ $tx->notes }}">{{ $tx->notes }}</p>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if(!auth()->user()?->isReadOnly())
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" onclick="openEditLocalTxModal({{ json_encode($tx) }})"
                                                class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-slate-100 transition-colors" title="Edit row">
                                            <span class="material-symbols-outlined text-[16px]">edit</span>
                                        </button>
                                        <form action="{{ route('khata.local_transactions.destroy', $tx->id) }}" method="POST" onsubmit="return confirm('Delete this local transaction?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-slate-100 transition-colors" title="Delete row">
                                                <span class="material-symbols-outlined text-[16px]">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 text-center text-slate-400">
                                <span class="material-symbols-outlined text-4xl block mb-2 text-slate-300">storefront</span>
                                No local party expenses or barter transactions recorded yet. Click <strong>"+ Local Party Entry"</strong> to add one with a camera photo!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Custom User-Defined Dynamic Grids & Cards for Local Party -->
        @if($localCustomCards->isNotEmpty())
            <div class="p-5 border-t border-slate-200 bg-slate-50/50 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px] text-blue-600">dashboard_customize</span>
                        <span>Custom Configured Local Tables & Grids ({{ $localCustomCards->count() }})</span>
                    </h3>
                    <span class="text-[11px] text-slate-400">Fully customizable by user (add/edit headings, rows, columns)</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($localCustomCards as $card)
                        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs" id="custom-card-{{ $card->id }}">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900">{{ $card->title }}</h4>
                                    @if($card->subtitle)
                                        <p class="text-[11px] text-slate-400">{{ $card->subtitle }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1">
                                    <button onclick="addCustomGridRow({{ $card->id }})" class="px-2 py-1 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 hover:bg-blue-100">
                                        + Row
                                    </button>
                                    <form action="{{ route('custom_cards.destroy', $card->id) }}" method="POST" onsubmit="return confirm('Delete this custom table?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 rounded text-slate-400 hover:text-red-600" title="Delete Table">
                                            <span class="material-symbols-outlined text-[15px]">close</span>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            @if(!empty($card->column_headers) && is_array($card->column_headers))
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-xs border border-slate-200 rounded-lg overflow-hidden" id="custom-table-{{ $card->id }}">
                                        <thead>
                                            <tr class="bg-slate-100 border-b border-slate-200 text-slate-700 font-bold">
                                                @foreach($card->column_headers as $hIdx => $header)
                                                    <th class="p-2 border-r border-slate-200">
                                                        <input type="text" value="{{ $header }}" onchange="saveCustomCardData({{ $card->id }})"
                                                               class="w-full bg-transparent font-bold border-0 p-0 text-xs focus:ring-0 text-slate-800" />
                                                    </th>
                                                @endforeach
                                                <th class="p-2 w-8 text-center">#</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @if(!empty($card->grid_data) && is_array($card->grid_data))
                                                @foreach($card->grid_data as $rIdx => $row)
                                                    <tr class="hover:bg-slate-50 custom-row-data">
                                                        @foreach($card->column_headers as $cIdx => $h)
                                                            <td class="p-1.5 border-r border-slate-100">
                                                                <input type="text" value="{{ $row[$cIdx] ?? '' }}" onchange="saveCustomCardData({{ $card->id }})"
                                                                       class="w-full bg-transparent border-0 p-1 text-xs focus:ring-1 focus:ring-blue-500 rounded" />
                                                            </td>
                                                        @endforeach
                                                        <td class="p-1 text-center">
                                                            <button onclick="removeCustomGridRow(this, {{ $card->id }})" class="text-slate-300 hover:text-red-500 p-0.5">
                                                                <span class="material-symbols-outlined text-[14px]">remove_circle_outline</span>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Search & Filter Bar for Party Accounts -->
    <div class="flex items-center justify-between gap-4 pt-2">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Registered Party & Vendor Ledgers</h2>
            <p class="text-xs text-slate-400">Select any party to open their detailed double-entry ledger statement</p>
        </div>
        <form method="GET" action="{{ route('khata.index') }}" class="flex gap-2 max-w-md w-full">
            <input type="hidden" name="type" value="{{ $type }}" />
            <input type="hidden" name="classification" value="{{ $classification }}" />
            <input type="text" name="q" value="{{ $search }}" placeholder="Search shop name, owner, city, or phone..."
                   class="flex-1 rounded-xl border-slate-300 text-xs py-2 px-3 bg-white focus:border-blue-600 focus:ring-blue-600" />
            <button type="submit" class="px-4 py-2 rounded-xl bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs">
                Search
            </button>
            @if($search)
                <a href="{{ route('khata.index', ['type' => $type, 'classification' => $classification]) }}"
                   class="px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium text-slate-600 hover:bg-slate-50 flex items-center">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Party Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($parties as $party)
            @php
                $bal = $party->current_balance;
                $isReceivable = $bal > 0;
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs hover:border-blue-400 transition-all flex flex-col justify-between gap-4 group">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-1.5">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                {{ $party->classification === 'LOCAL_VENDOR_SHOP' ? 'bg-amber-100 text-amber-800' : ($party->type === 'CUSTOMER' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700') }}">
                                {{ str_replace('_', ' ', $party->classification ?: $party->type) }}
                            </span>
                            @if($party->vendor_category)
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600">
                                    {{ $party->vendor_category }}
                                </span>
                            @endif
                        </div>
                        <span class="text-xs text-slate-400 font-medium">{{ $party->city }}</span>
                    </div>

                    <a href="{{ route('khata.show', $party) }}" class="block">
                        <h3 class="text-base font-bold text-slate-900 group-hover:text-blue-600 transition-colors">{{ $party->name }}</h3>
                        <p class="text-xs text-slate-500 mt-1">{{ $party->phone }} {{ $party->contact_person ? '· ' . $party->contact_person : '' }}</p>
                        @if($party->address)
                            <p class="text-[11px] text-slate-400 mt-0.5 truncate">{{ $party->address }}</p>
                        @endif
                    </a>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">
                            {{ $isReceivable ? 'Receivable (Lena / Dr)' : ($bal < 0 ? 'Payable (Dena / Cr)' : 'Zero Settled') }}
                        </span>
                        <span class="text-lg font-extrabold num-tabular {{ $isReceivable ? 'text-navy-900' : ($bal < 0 ? 'text-red-600' : 'text-emerald-600') }}">
                            Rs. {{ number_format(abs($bal)) }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" onclick="openAdjustModal({{ json_encode($party) }})"
                            title="Audit Balance Adjustment"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                            <span class="material-symbols-outlined text-[18px]">tune</span>
                        </button>
                        <a href="{{ route('khata.show', $party) }}"
                            class="px-3 py-1.5 rounded-lg bg-slate-50 hover:bg-blue-50 text-blue-600 text-xs font-semibold inline-flex items-center gap-1 transition-colors">
                            <span>Khata</span>
                            <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-400">
                <span class="material-symbols-outlined text-4xl block mb-2 text-slate-300">account_balance_wallet</span>
                No party or vendor accounts matched your filter. Click "+ Add Party / Local Vendor" above.
            </div>
        @endforelse
    </div>
</div>
@endsection

@section('modals')

<!-- MODAL 1: ADD LOCAL PARTY ENTRY (WITH LIVE CAMERA SNAP & FILE UPLOAD) -->
<div id="modal-new-local-tx" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-xl w-full p-6 shadow-2xl max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
                    <span class="material-symbols-outlined text-[20px]">storefront</span>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Add Local Party Expense / Barter</h3>
                    <p class="text-[11px] text-slate-400">Small daily shop expenses, food grocery, hardware & mutual trade</p>
                </div>
            </div>
            <button onclick="closeModal('modal-new-local-tx')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="{{ route('khata.local_transactions.store') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf

            <!-- Direction Choice: We Bought vs They Bought -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Transaction Direction *</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="cursor-pointer border-2 border-amber-300 bg-amber-50/60 hover:bg-amber-50 p-3 rounded-xl flex items-center gap-2.5 transition-colors">
                        <input type="radio" name="transaction_type" value="WE_BOUGHT" checked class="text-amber-600 focus:ring-amber-500" />
                        <div>
                            <span class="text-xs font-bold text-amber-900 block">We Bought (Ham Ne Liya)</span>
                            <span class="text-[10px] text-slate-500 block">Factory expense (Food, scales, fuel, tools)</span>
                        </div>
                    </label>

                    <label class="cursor-pointer border-2 border-emerald-300 bg-emerald-50/60 hover:bg-emerald-50 p-3 rounded-xl flex items-center gap-2.5 transition-colors">
                        <input type="radio" name="transaction_type" value="THEY_BOUGHT" class="text-emerald-600 focus:ring-emerald-500" />
                        <div>
                            <span class="text-xs font-bold text-emerald-900 block">They Took (Unhon Ne Liya)</span>
                            <span class="text-[10px] text-slate-500 block">Shop counter sale (Sheets, cartons, waste)</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Shop / Party Name -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Local Shop / Party Name *</label>
                    <input type="text" name="shop_name" list="local-shop-names" required placeholder="e.g. Butt Karahi Hotel / Al-Madina Hardware"
                           class="w-full rounded-lg border-slate-300 text-xs focus:border-blue-600 focus:ring-blue-600" />
                    <datalist id="local-shop-names">
                        @foreach($localShopList as $shop)
                            <option value="{{ $shop->name }}">
                        @endforeach
                        <option value="Butt Karahi Hotel">
                        <option value="Al-Madina Hardware & Tools">
                        <option value="Asif Autos & Spare Parts">
                        <option value="Okara PSO Petroleum Service">
                        <option value="Subhan Stationery & Printing">
                        <option value="Rafiq General Store">
                    </datalist>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">City / Location</label>
                    <input type="text" name="shop_city" value="Okara"
                           class="w-full rounded-lg border-slate-300 text-xs focus:border-blue-600 focus:ring-blue-600" />
                </div>
            </div>

            <!-- Item & Quantity -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Item / Particulars *</label>
                    <input type="text" name="item_description" required placeholder="e.g. 4 portions food / Measuring scales / 50 Sheets"
                           class="w-full rounded-lg border-slate-300 text-xs focus:border-blue-600 focus:ring-blue-600" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Quantity / Portions Details</label>
                    <input type="text" name="quantity_details" placeholder="e.g. 4 portions, 2 scales, 50 sheets"
                           class="w-full rounded-lg border-slate-300 text-xs focus:border-blue-600 focus:ring-blue-600" />
                </div>
            </div>

            <!-- Price & Payment Status -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Unit Price (Rs.)</label>
                    <input type="number" step="0.01" name="unit_price" placeholder="Optional"
                           class="w-full rounded-lg border-slate-300 text-xs focus:border-blue-600 focus:ring-blue-600" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Total Amount (Rs.) *</label>
                    <input type="number" step="0.01" name="total_amount" required placeholder="e.g. 1400"
                           class="w-full rounded-lg border-slate-300 text-xs font-bold font-mono focus:border-blue-600 focus:ring-blue-600" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Status *</label>
                    <select name="payment_status" required class="w-full rounded-lg border-slate-300 text-xs focus:border-blue-600 focus:ring-blue-600">
                        <option value="PAID_CASH">Paid in Cash</option>
                        <option value="ADDED_TO_KHATA">Added to Khata (Udhaar/Baqaya)</option>
                        <option value="SETTLED">Barter Settled</option>
                        <option value="PARTIAL">Partial Payment</option>
                    </select>
                </div>
            </div>

            <!-- MEDIA UPLOADING: LIVE CAMERA SNAP OR GALLERY UPLOAD -->
            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                <label class="block text-xs font-bold text-slate-800 mb-1.5 flex items-center justify-between">
                    <span>Receipt / Grocery Bill Photo</span>
                    <span class="text-[10px] text-blue-600 font-normal">Optional · Automatically saved into Media Vault</span>
                </label>

                <div class="grid grid-cols-2 gap-2">
                    <!-- Live Camera Trigger -->
                    <label class="cursor-pointer border-2 border-dashed border-amber-400 hover:border-amber-600 bg-white hover:bg-amber-50/50 p-2.5 rounded-xl flex items-center justify-center gap-2 text-center transition-colors">
                        <span class="material-symbols-outlined text-xl text-amber-600">photo_camera</span>
                        <div class="text-left">
                            <span class="text-xs font-bold text-slate-800 block">Take Photo</span>
                            <span class="text-[9px] text-slate-400 block">Opens phone camera</span>
                        </div>
                        <input type="file" name="receipt_photo" accept="image/*" capture="environment" class="hidden" onchange="previewLocalReceipt(this)" />
                    </label>

                    <!-- Gallery / File Upload Trigger -->
                    <label class="cursor-pointer border-2 border-dashed border-slate-300 hover:border-slate-500 bg-white hover:bg-slate-100 p-2.5 rounded-xl flex items-center justify-center gap-2 text-center transition-colors">
                        <span class="material-symbols-outlined text-xl text-slate-600">attach_file</span>
                        <div class="text-left">
                            <span class="text-xs font-bold text-slate-800 block">Choose File</span>
                            <span class="text-[9px] text-slate-400 block">JPG, PNG, PDF</span>
                        </div>
                        <input type="file" name="receipt_photo" accept="image/*,.pdf" class="hidden" onchange="previewLocalReceipt(this)" />
                    </label>
                </div>

                <div id="local-preview-box" class="hidden mt-2 p-2 rounded-lg bg-white border border-slate-200 flex items-center gap-3">
                    <img id="local-preview-img" src="" class="w-12 h-12 object-cover rounded border border-slate-200" />
                    <div class="flex-1 truncate">
                        <span id="local-preview-name" class="text-xs font-bold text-slate-800 block truncate"></span>
                        <span id="local-preview-size" class="text-[10px] text-slate-400"></span>
                    </div>
                </div>
            </div>

            <!-- Notes & Extra Details -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Notes / Narration</label>
                <textarea name="notes" rows="2" placeholder="e.g. Lunch for corrugation night shift crew"
                          class="w-full rounded-lg border-slate-300 text-xs focus:border-blue-600 focus:ring-blue-600"></textarea>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('modal-new-local-tx')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700 shadow-xs">
                    Save Local Transaction
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: EDIT LOCAL TRANSACTION -->
<div id="modal-edit-local-tx" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Edit Local Transaction</h3>
            <button onclick="closeModal('modal-edit-local-tx')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="form-edit-local-tx" method="POST" class="mt-4 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Shop / Party Name *</label>
                <input type="text" id="edit-shop-name" name="shop_name" required class="w-full rounded-lg border-slate-300 text-xs" />
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Direction *</label>
                <select id="edit-transaction-type" name="transaction_type" required class="w-full rounded-lg border-slate-300 text-xs">
                    <option value="WE_BOUGHT">We Bought (Factory Expense)</option>
                    <option value="THEY_BOUGHT">They Took (Shop Counter Sale)</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Item Description *</label>
                    <input type="text" id="edit-item-desc" name="item_description" required class="w-full rounded-lg border-slate-300 text-xs" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Quantity Details</label>
                    <input type="text" id="edit-qty-details" name="quantity_details" class="w-full rounded-lg border-slate-300 text-xs" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Total Amount (Rs.) *</label>
                    <input type="number" step="0.01" id="edit-total-amount" name="total_amount" required class="w-full rounded-lg border-slate-300 text-xs font-bold" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Status *</label>
                    <select id="edit-payment-status" name="payment_status" required class="w-full rounded-lg border-slate-300 text-xs">
                        <option value="PAID_CASH">Paid in Cash</option>
                        <option value="ADDED_TO_KHATA">Added to Khata (Udhaar)</option>
                        <option value="SETTLED">Barter Settled</option>
                        <option value="PARTIAL">Partial Payment</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Notes</label>
                <textarea id="edit-notes" name="notes" rows="2" class="w-full rounded-lg border-slate-300 text-xs"></textarea>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('modal-edit-local-tx')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 3: CREATE CUSTOM CARD / TABLE FOR LOCAL PARTY -->
<div id="modal-custom-local-card" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4 hidden overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Create Custom Local Party Table</h3>
                <p class="text-xs text-slate-400">Add custom metrics, columns, and headings for daily shop expenses</p>
            </div>
            <button onclick="closeModal('modal-custom-local-card')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="{{ route('custom_cards.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="page_key" value="khata_local_party" />
            <input type="hidden" name="card_type" value="DYNAMIC_GRID" />

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Table Heading / Title *</label>
                <input type="text" name="title" required placeholder="e.g. Daily Canteen Food & Mess Expense Table"
                       class="w-full rounded-lg border-slate-300 text-xs py-2 focus:border-blue-600 focus:ring-blue-600" />
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Subtitle / Purpose</label>
                <input type="text" name="subtitle" placeholder="e.g. Hotel meals, tea bills, and worker lunch records"
                       class="w-full rounded-lg border-slate-300 text-xs py-2" />
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Initial Column Headings (Comma Separated)</label>
                <input type="text" id="custom-headers-input" placeholder="Shop Name, Item, Quantity, Price, Paid By"
                       class="w-full rounded-lg border-slate-300 text-xs py-2"
                       onchange="parseCustomHeaders(this.value)" />
                <div id="headers-hidden-inputs"></div>
                <span class="text-[10px] text-slate-400 mt-1 block">You can change headings or add more columns anytime later</span>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-custom-local-card')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800">Create Table</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 4: FULL SCREEN IMAGE / BILL VIEWER -->
<div id="modal-image-viewer" class="fixed inset-0 z-50 hidden bg-slate-950/90 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative bg-slate-900 border border-slate-800 rounded-2xl max-w-4xl w-full max-h-[95vh] flex flex-col overflow-hidden shadow-2xl">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between bg-slate-900/90 text-white">
            <div>
                <h3 id="viewer-title" class="text-sm font-bold text-white"></h3>
                <p id="viewer-subtitle" class="text-xs text-slate-400 mt-0.5"></p>
            </div>
            <div class="flex items-center gap-2">
                <a id="viewer-download-link" href="#" download class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 inline-flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">download</span>
                    <span>Download</span>
                </a>
                <button onclick="window.print()" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 inline-flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">print</span>
                    <span>Print</span>
                </button>
                <button onclick="closeModal('modal-image-viewer')" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        <div class="p-4 flex-1 flex items-center justify-center overflow-auto bg-black/40">
            <img id="viewer-img" src="" class="max-h-[75vh] max-w-full object-contain rounded-lg shadow-lg hidden" />
            <iframe id="viewer-pdf" src="" class="w-full h-[75vh] rounded-lg hidden"></iframe>
        </div>
    </div>
</div>

<!-- Standard Add Party Modal -->
<div id="modal-new-party" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Add Customer, Mill or Local Vendor Shop</h3>
            <button onclick="closeModal('modal-new-party')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form action="{{ route('khata.party.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Shop / Business / Party Name *</label>
                <input type="text" name="name" required placeholder="e.g. Al-Madina Hardware & Tools / Irfan Pharma"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Account Classification *</label>
                    <select name="classification" id="party-classification-select" required onchange="toggleVendorCategory(this.value)"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                        <option value="LOCAL_VENDOR_SHOP">Local Vendor Shop (Hardware/Parts/Food)</option>
                        <option value="CORPORATE_CLIENT">Corporate Client (Carton Buyer)</option>
                        <option value="PAPER_MILL">Paper Mill (Kraft Supplier)</option>
                        <option value="TRANSPORTER">Transporter / Logistics</option>
                        <option value="GENERAL_SUPPLIER">General Supplier</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Account Type *</label>
                    <select name="type" id="party-type-select" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                        <option value="SUPPLIER">Supplier (Vendor / Kharch)</option>
                        <option value="CUSTOMER">Customer (Buyer)</option>
                    </select>
                </div>
            </div>

            <div id="vendor-category-wrap">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Local Shop Category</label>
                <input type="text" name="vendor_category" placeholder="e.g. Canteen/Food, Hardware, Auto Spares, Diesel Fuel"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Contact Person</label>
                    <input type="text" name="contact_person" placeholder="Owner / Manager name"
                        class="w-full rounded-lg border-slate-300 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Phone Number *</label>
                    <input type="text" name="phone" required placeholder="0300-1234567"
                        class="w-full rounded-lg border-slate-300 text-sm" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">City *</label>
                    <input type="text" name="city" value="Okara" required class="w-full rounded-lg border-slate-300 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Opening Balance (Rs.)</label>
                    <input type="number" step="0.01" name="opening_balance" placeholder="0" class="w-full rounded-lg border-slate-300 text-sm font-mono" />
                </div>
            </div>

            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-new-party')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs">Save Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Standard Voucher Modal -->
<div id="modal-voucher" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Record Voucher / Ledger Payment</h3>
            <button onclick="closeModal('modal-voucher')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form action="{{ route('khata.voucher.store') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Voucher Type *</label>
                <select name="voucher_type" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                    <option value="RECEIPT_VOUCHER">Payment Receipt from Customer (Cash/Online)</option>
                    <option value="PAYMENT_VOUCHER">Payment to Supplier / Mill / Vendor</option>
                    <option value="EXPENSE_VOUCHER">General Factory Overhead Expense</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Party / Vendor</label>
                    <select name="party_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                        <option value="">-- Factory Overhead (No Party) --</option>
                        @foreach($parties as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->city }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Mode *</label>
                    <select name="payment_mode" required class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="CASH">Cash (Office Till)</option>
                        <option value="ONLINE_BANK">Online Bank Transfer</option>
                        <option value="CHEQUE">Cheque</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Total Amount (Rs.) *</label>
                    <input type="number" step="0.01" name="total_amount" required placeholder="e.g. 50000"
                        class="w-full rounded-lg border-slate-300 text-sm font-bold font-mono focus:border-blue-600 focus:ring-blue-600" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Date</label>
                    <input type="date" name="voucher_date" value="{{ date('Y-m-d') }}" class="w-full rounded-lg border-slate-300 text-sm" />
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Description / Narration *</label>
                <input type="text" name="description" required placeholder="e.g. Paid for machine spare parts / Cash receipt"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
            </div>

            <!-- Option C: In-Form Media Photo Capture -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Receipt / Bank Slip / Photo (Optional)</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center justify-center gap-1.5 px-3 py-2 border-2 border-dashed border-blue-200 bg-blue-50/40 hover:bg-blue-50 rounded-xl cursor-pointer text-xs font-semibold text-blue-700 transition-colors">
                        <span class="material-symbols-outlined text-base">photo_camera</span>
                        <span>Camera Snap</span>
                        <input type="file" name="receipt_photo" accept="image/*" capture="environment" class="hidden" onchange="previewVoucherPhoto(this)" />
                    </label>
                    <label class="flex items-center justify-center gap-1.5 px-3 py-2 border border-slate-200 bg-slate-50 hover:bg-slate-100 rounded-xl cursor-pointer text-xs font-medium text-slate-700 transition-colors">
                        <span class="material-symbols-outlined text-base">upload_file</span>
                        <span>Upload File</span>
                        <input type="file" name="receipt_photo" accept="image/*,.pdf" class="hidden" onchange="previewVoucherPhoto(this)" />
                    </label>
                </div>
                <div id="voucher-preview-box" class="hidden mt-2 text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-2 items-center justify-between">
                    <div class="flex items-center gap-1.5 truncate">
                        <span class="material-symbols-outlined text-sm text-emerald-600">check_circle</span>
                        <span id="voucher-preview-filename" class="font-medium truncate"></span>
                    </div>
                    <button type="button" onclick="clearVoucherPhoto()" class="text-red-500 hover:text-red-700 text-sm font-bold ml-2">&times;</button>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Directly takes photo on phone or upload receipt from gallery. Auto-saved to Media Vault.</p>
            </div>

            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-voucher')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs">Post Voucher</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Balance Adjustment -->
<div id="modal-adjust-balance" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900" id="adjust-party-name">Adjust Khata Balance</h3>
            <button onclick="closeModal('modal-adjust-balance')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <p class="text-xs text-slate-500 mt-2">
            <strong>Audit Safe:</strong> Creates an official ledger entry so financial history remains uncorrupted.
        </p>
        <form id="adjust-party-form" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Adjustment Action *</label>
                <select name="adjustment_type" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                    <option value="DEBIT_INCREASE">Debit (+) Increase Receivable / Pay to Vendor</option>
                    <option value="CREDIT_DECREASE">Credit (-) Receive from Client / Discount Given</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Amount (Rs.) *</label>
                <input type="number" step="0.01" min="0.01" name="amount" required placeholder="e.g. 1500"
                    class="w-full rounded-lg border-slate-300 text-sm font-bold font-mono focus:border-blue-600 focus:ring-blue-600" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Reason / Justification *</label>
                <input type="text" name="reason" required placeholder="e.g. Rounding off settled"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600" />
            </div>
            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('modal-adjust-balance')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs">Post Audit Adjustment</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function filterLocalRows(type) {
    const tabs = document.querySelectorAll('.local-filter-tab');
    tabs.forEach(t => {
        t.classList.remove('bg-navy-900', 'text-white');
        t.classList.add('text-slate-600');
    });

    const activeTab = type === 'ALL' ? document.getElementById('local-tab-all')
        : (type === 'WE_BOUGHT' ? document.getElementById('local-tab-we-bought') : document.getElementById('local-tab-they-bought'));
    if (activeTab) {
        activeTab.classList.add('bg-navy-900', 'text-white');
        activeTab.classList.remove('text-slate-600');
    }

    const rows = document.querySelectorAll('.local-item-row');
    rows.forEach(r => {
        if (type === 'ALL' || r.getAttribute('data-type') === type) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}

function previewLocalReceipt(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const box = document.getElementById('local-preview-box');
        const img = document.getElementById('local-preview-img');
        const nameEl = document.getElementById('local-preview-name');
        const sizeEl = document.getElementById('local-preview-size');

        nameEl.textContent = file.name;
        sizeEl.textContent = (file.size / 1024).toFixed(1) + ' KB';

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
                img.classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        } else {
            img.classList.add('hidden');
        }

        box.classList.remove('hidden');
    }
}

function openEditLocalTxModal(tx) {
    document.getElementById('form-edit-local-tx').action = '/khata/local-transactions/' + tx.id;
    document.getElementById('edit-shop-name').value = tx.shop_name || '';
    document.getElementById('edit-transaction-type').value = tx.transaction_type || 'WE_BOUGHT';
    document.getElementById('edit-item-desc').value = tx.item_description || '';
    document.getElementById('edit-qty-details').value = tx.quantity_details || '';
    document.getElementById('edit-total-amount').value = tx.total_amount || 0;
    document.getElementById('edit-payment-status').value = tx.payment_status || 'PAID_CASH';
    document.getElementById('edit-notes').value = tx.notes || '';
    openModal('modal-edit-local-tx');
}

function parseCustomHeaders(val) {
    const container = document.getElementById('headers-hidden-inputs');
    container.innerHTML = '';
    const headers = val.split(',').map(s => s.trim()).filter(s => s.length > 0);
    headers.forEach((h, idx) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = `column_headers[${idx}]`;
        input.value = h;
        container.appendChild(input);
    });
}

function addCustomGridRow(cardId) {
    const table = document.getElementById('custom-table-' + cardId);
    if (!table) return;
    const tbody = table.querySelector('tbody');
    const headerCols = table.querySelectorAll('thead th').length - 1;

    const tr = document.createElement('tr');
    tr.className = 'hover:bg-slate-50 custom-row-data';

    for (let i = 0; i < headerCols; i++) {
        const td = document.createElement('td');
        td.className = 'p-1.5 border-r border-slate-100';
        td.innerHTML = `<input type="text" value="" onchange="saveCustomCardData(${cardId})" class="w-full bg-transparent border-0 p-1 text-xs focus:ring-1 focus:ring-blue-500 rounded" placeholder="..." />`;
        tr.appendChild(td);
    }

    const actionTd = document.createElement('td');
    actionTd.className = 'p-1 text-center';
    actionTd.innerHTML = `<button onclick="removeCustomGridRow(this, ${cardId})" class="text-slate-300 hover:text-red-500 p-0.5"><span class="material-symbols-outlined text-[14px]">remove_circle_outline</span></button>`;
    tr.appendChild(actionTd);

    tbody.appendChild(tr);
    saveCustomCardData(cardId);
}

function removeCustomGridRow(btn, cardId) {
    const tr = btn.closest('tr');
    if (tr) {
        tr.remove();
        saveCustomCardData(cardId);
    }
}

function saveCustomCardData(cardId) {
    const table = document.getElementById('custom-table-' + cardId);
    if (!table) return;

    // Collect headers
    const headerInputs = table.querySelectorAll('thead th input');
    const headers = Array.from(headerInputs).map(inp => inp.value);

    // Collect rows
    const rows = table.querySelectorAll('tbody tr.custom-row-data');
    const gridData = [];
    rows.forEach(r => {
        const rowInputs = r.querySelectorAll('td input');
        const rowVals = Array.from(rowInputs).map(inp => inp.value);
        gridData.push(rowVals);
    });

    fetch('/custom-cards/' + cardId, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            column_headers: headers,
            grid_data: gridData,
            rows_count: gridData.length,
            columns_count: headers.length,
        }),
    }).catch(err => console.log('Auto-save card error:', err));
}

function viewFullImage(src, title, type, category, amount, shop) {
    document.getElementById('viewer-title').textContent = title;
    let subtitle = category;
    if (shop) subtitle += ' · ' + shop;
    if (amount) subtitle += ' · ' + amount;
    document.getElementById('viewer-subtitle').textContent = subtitle;

    const imgEl = document.getElementById('viewer-img');
    const pdfEl = document.getElementById('viewer-pdf');
    const downloadEl = document.getElementById('viewer-download-link');

    downloadEl.href = src;

    if (type === 'pdf') {
        imgEl.classList.add('hidden');
        pdfEl.src = src;
        pdfEl.classList.remove('hidden');
    } else {
        pdfEl.classList.add('hidden');
        imgEl.src = src;
        imgEl.classList.remove('hidden');
    }

    openModal('modal-image-viewer');
}

function toggleVendorCategory(classification) {
    const wrap = document.getElementById('vendor-category-wrap');
    const typeSelect = document.getElementById('party-type-select');
    if (classification === 'LOCAL_VENDOR_SHOP') {
        wrap.classList.remove('hidden');
        typeSelect.value = 'SUPPLIER';
    } else if (classification === 'CORPORATE_CLIENT') {
        wrap.classList.add('hidden');
        typeSelect.value = 'CUSTOMER';
    } else {
        wrap.classList.add('hidden');
    }
}

function openAdjustModal(party) {
    document.getElementById('adjust-party-name').textContent = 'Audit Adjust: ' + party.name;
    document.getElementById('adjust-party-form').action = '/khata/party/' + party.id + '/adjust';
    openModal('modal-adjust-balance');
}

function previewVoucherPhoto(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const box = document.getElementById('voucher-preview-box');
        const filename = document.getElementById('voucher-preview-filename');
        if (box && filename) {
            filename.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
            box.classList.remove('hidden');
            box.classList.add('flex');
        }
    }
}

function clearVoucherPhoto() {
    const box = document.getElementById('voucher-preview-box');
    if (box) {
        box.classList.add('hidden');
        box.classList.remove('flex');
        const form = box.closest('form');
        if (form) {
            const inputs = form.querySelectorAll('input[type="file"]');
            inputs.forEach(i => i.value = '');
        }
    }
}
</script>
@endpush
