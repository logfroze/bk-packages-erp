@extends('layouts.app')

@section('title', 'Overview')
@section('breadcrumb', 'Overview')

@section('content')
    <!-- Welcome & Quick Actions Strip -->
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-6 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Welcome back, {{ auth()->user()?->name ?? 'Mirza Sohail' }}</h1>
            <p class="text-sm text-slate-500 mt-1">Factory operations summary for BK Packages, Okara Plant</p>
        </div>

        <!-- 4 Clear, Prominent Quick Action Buttons -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @if(auth()->user()?->isFinancialAuthorized())
                <button onclick="openModal('modal-production')"
                        class="px-4 py-3 rounded-xl bg-navy-900 text-white hover:bg-navy-800 font-medium text-sm shadow-sm flex items-center justify-center gap-2 transition-colors">
                    <span class="material-symbols-outlined text-[18px]">add_box</span>
                    <span>+ Record Production</span>
                </button>
            @endif

            <button onclick="openModal('modal-gate')"
                    class="px-4 py-3 rounded-xl bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 font-medium text-sm flex items-center justify-center gap-2 transition-colors">
                <span class="material-symbols-outlined text-[18px] text-blue-600">local_shipping</span>
                <span>+ Vehicle Gate In</span>
            </button>

            @if(auth()->user()?->isFinancialAuthorized())
                <button onclick="openModal('modal-payment')"
                        class="px-4 py-3 rounded-xl bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 font-medium text-sm flex items-center justify-center gap-2 transition-colors">
                    <span class="material-symbols-outlined text-[18px] text-emerald-600">payments</span>
                    <span>+ Receive Payment</span>
                </button>
            @endif

            <button onclick="openModal('modal-challan')"
                    class="px-4 py-3 rounded-xl bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 font-medium text-sm flex items-center justify-center gap-2 transition-colors">
                <span class="material-symbols-outlined text-[18px] text-slate-600">receipt_long</span>
                <span>+ Delivery Challan</span>
            </button>
        </div>
    </div>

    <!-- 4 Clean KPI Cards (Spacious, Uncluttered) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- KPI 1 -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Today's Production</span>
                <span class="p-2 rounded-lg bg-blue-50 text-blue-600">
                    <span class="material-symbols-outlined text-[18px]">precision_manufacturing</span>
                </span>
            </div>
            <div class="my-3 text-3xl font-bold text-slate-900 num-tabular">{{ number_format($todayProduction) }} Pcs</div>
            <div class="text-xs text-slate-500">
                5-Ply & 3-Ply Cartons
            </div>
        </div>

        <!-- KPI 2 -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Trucks Dispatched</span>
                <span class="p-2 rounded-lg bg-slate-100 text-slate-700">
                    <span class="material-symbols-outlined text-[18px]">local_shipping</span>
                </span>
            </div>
            <div class="my-3 text-3xl font-bold text-slate-900 num-tabular">{{ $trucksDispatched }} Vehicles</div>
            <div class="text-xs text-slate-500">
                {{ number_format($boxesDispatched) }} Boxes Loaded Today
            </div>
        </div>

        <!-- KPI 3 -->
        @if(auth()->user()?->isFinancialAuthorized())
            <div class="bg-white rounded-xl border border-slate-200 p-6 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Cash in Hand</span>
                    <span class="p-2 rounded-lg bg-emerald-50 text-emerald-600">
                        <span class="material-symbols-outlined text-[18px]">account_balance_wallet</span>
                    </span>
                </div>
                <div class="my-3 text-3xl font-bold text-slate-900 num-tabular">Rs. {{ number_format($cashInHand) }}</div>
                <div class="text-xs text-emerald-700 font-medium">
                    Factory Cash Safe
                </div>
            </div>
        @endif

        <!-- KPI 4 -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Orders</span>
                <span class="p-2 rounded-lg bg-amber-50 text-amber-600">
                    <span class="material-symbols-outlined text-[18px]">assignment</span>
                </span>
            </div>
            <div class="my-3 text-3xl font-bold text-slate-900 num-tabular">{{ $pendingOrdersCount }} Orders</div>
            <div class="text-xs text-slate-500 truncate">
                Next: {{ $nextPendingBatch->client->name ?? 'All caught up' }}
            </div>
        </div>
    </div>

    <!-- Two Spacious 50/50 Panels -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Left Card: Factory Status at a Glance -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Factory Status at a Glance</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Live machine lines & godown stock totals</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Live
                    </span>
                </div>

                <div class="divide-y divide-slate-100 mt-2">
                    @forelse($runningBatches as $batch)
                        <div class="py-4 flex items-center justify-between gap-4">
                            <div>
                                <div class="text-sm font-semibold text-slate-800">{{ str_replace('_', ' ', $batch->current_machine) }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ $batch->cartonProduct->box_title ?? $batch->batch_number }} · {{ $batch->client->name ?? '' }}</div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                Running ({{ number_format($batch->produced_quantity) }}/{{ number_format($batch->target_quantity) }})
                            </span>
                        </div>
                    @empty
                        <div class="py-4 flex items-center justify-between">
                            <div>
                                <div class="text-sm font-semibold text-slate-800">Corrugator Lines</div>
                                <div class="text-xs text-slate-500 mt-0.5">No batches actively running right now</div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Standby</span>
                        </div>
                    @endforelse

                    <div class="py-4 flex items-center justify-between gap-4">
                        <div>
                            <div class="text-sm font-semibold text-slate-800">Finished Cartons in Godown</div>
                            <div class="text-xs text-slate-500 mt-0.5">Bundled & ready for truck loading</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold text-slate-900 num-tabular">{{ number_format($totalFinishedStock) }} Pcs</div>
                            <div class="text-xs text-emerald-600">Available Stock</div>
                        </div>
                    </div>

                    <div class="py-4 flex items-center justify-between gap-4">
                        <div>
                            <div class="text-sm font-semibold text-slate-800">Kraft Paper Reel Shed</div>
                            <div class="text-xs text-slate-500 mt-0.5">150 GSM Kraft & 120 GSM Fluting</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold text-slate-900 num-tabular">{{ $totalRawMaterialTons }} Tons</div>
                            <div class="text-xs text-blue-600">Healthy Stock</div>
                        </div>
                    </div>

                    <div class="py-4 flex items-center justify-between gap-4">
                        <div>
                            <div class="text-sm font-semibold text-slate-800">Workers Present Today</div>
                            <div class="text-xs text-slate-500 mt-0.5">Operators, stitchers & loading labor</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold text-slate-900 num-tabular">{{ $presentWorkersCount }} Present</div>
                            <a href="{{ route('attendance.index') }}" class="text-xs text-blue-600 hover:underline">Mark Attendance</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4 mt-2 border-t border-slate-100">
                <a href="{{ route('production.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700 inline-flex items-center gap-1">
                    Go to Production & Stock
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </a>
            </div>
        </div>

        <!-- Right Card: Today's Activity Log -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 flex flex-col justify-between">
            <div>
                <div class="pb-4 border-b border-slate-100">
                    <h2 class="text-lg font-semibold text-slate-900">Recent Activity Log</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Latest dispatches, cash receipts & gate arrivals</p>
                </div>

                <div class="mt-6 space-y-6">
                    @forelse($activityLog as $item)
                        <div class="flex items-start gap-4">
                            <span class="w-2.5 h-2.5 rounded-full {{ $item['color'] }} mt-1.5 shrink-0"></span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-semibold text-slate-800">{{ $item['title'] }}</span>
                                    <span class="text-xs text-slate-400 num-tabular shrink-0">{{ $item['time'] }}</span>
                                </div>
                                <p class="text-sm text-slate-600 mt-0.5">{{ $item['description'] }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 py-4">No activity logged yet today.</p>
                    @endforelse
                </div>
            </div>

            <div class="pt-4 mt-6 border-t border-slate-100 flex items-center justify-between">
                <a href="{{ route('reports.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700 inline-flex items-center gap-1">
                    View Day-End Summary (Roznamcha)
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </a>
                <a href="{{ route('gate.index') }}" class="text-xs text-slate-500 hover:text-slate-700">Gate Register →</a>
            </div>
        </div>
    </div>
@endsection

@section('modals')
    <!-- Modal 1: + Record Production -->
    <div id="modal-production" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-xl">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-lg font-semibold text-slate-900">Record New Production Batch</h3>
                <button onclick="closeModal('modal-production')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('production.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Carton / Box Specification</label>
                    <select name="carton_product_id" required class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach($cartonProducts as $carton)
                            <option value="{{ $carton->id }}">{{ $carton->box_title }} ({{ $carton->ply_type }} — {{ $carton->client->name ?? 'General' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Target Order Quantity (Pcs)</label>
                        <input type="number" name="target_quantity" value="5000" min="1" required class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Produced Right Now (Optional)</label>
                        <input type="number" name="produced_quantity" value="0" min="0" class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Machine / Stage</label>
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
                            <option value="SHIFT_A_DAY">Morning Shift (Day)</option>
                            <option value="SHIFT_B_NIGHT">Evening/Night Shift</option>
                        </select>
                    </div>
                </div>
                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-production')" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-sm font-medium hover:bg-navy-800">Save Production</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: + Vehicle Gate In -->
    <div id="modal-gate" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-xl">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-lg font-semibold text-slate-900">Vehicle Gate Entry (Inward / Outward)</h3>
                <button onclick="closeModal('modal-gate')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('gate.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Movement Type</label>
                        <select name="movement_type" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="OUTWARD_DISPATCH">Outward (Loading Cartons)</option>
                            <option value="INWARD_RAW_MATERIAL">Inward (Unloading Paper Reels)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Party / Mill Name</label>
                        <select name="party_id" required class="w-full rounded-lg border-slate-300 text-sm">
                            @foreach($allParties as $party)
                                <option value="{{ $party->id }}">{{ $party->name }} ({{ $party->type }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Vehicle Number</label>
                        <input type="text" name="vehicle_number" placeholder="e.g. LES-8492" required class="w-full rounded-lg border-slate-300 text-sm uppercase" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Vehicle Type</label>
                        <input type="text" name="vehicle_type" placeholder="Mazda / Shehzore / Truck" class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Driver Name</label>
                        <input type="text" name="driver_name" required class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Driver Phone</label>
                        <input type="text" name="driver_phone" placeholder="0300-XXXXXXX" class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cargo Description</label>
                    <input type="text" name="material_summary" placeholder="e.g. 16L Ghee Boxes or 150 GSM Kraft Reels" class="w-full rounded-lg border-slate-300 text-sm" />
                </div>
                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-gate')" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-sm font-medium hover:bg-navy-800">Issue Gate Pass</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 3: + Receive Payment -->
    <div id="modal-payment" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-xl">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-lg font-semibold text-slate-900">Record Payment Received (Cash / Bank)</h3>
                <button onclick="closeModal('modal-payment')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('khata.voucher.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="voucher_type" value="RECEIPT_VOUCHER" />
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Received From (Client Party)</label>
                    <select name="party_id" required class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach($customers as $cust)
                            <option value="{{ $cust->id }}">{{ $cust->name }} (Bal: Rs. {{ number_format($cust->current_balance) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Amount (Rs.)</label>
                        <input type="number" name="total_amount" min="1" step="0.01" required class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Mode</label>
                        <select name="payment_mode" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="CASH">Cash (Factory Office)</option>
                            <option value="CHEQUE">Bank Cheque</option>
                            <option value="ONLINE_BANK">Online Bank Transfer</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cheque / Slip Ref # (Optional)</label>
                    <input type="text" name="reference_number" placeholder="e.g. MCB Chq #492810" class="w-full rounded-lg border-slate-300 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Description / Narration</label>
                    <input type="text" name="description" value="Payment received by Mirza Sohail" required class="w-full rounded-lg border-slate-300 text-sm" />
                </div>
                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-payment')" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Save Receipt</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 4: + Create Delivery Challan / Dispatch Invoice -->
    <div id="modal-challan" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-xl">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-lg font-semibold text-slate-900">Create Delivery Challan & Dispatch</h3>
                <button onclick="closeModal('modal-challan')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="{{ route('gate.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="movement_type" value="OUTWARD_DISPATCH" />
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Client Party</label>
                        <select name="party_id" required class="w-full rounded-lg border-slate-300 text-sm">
                            @foreach($customers as $cust)
                                <option value="{{ $cust->id }}">{{ $cust->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Carton Product</label>
                        <select name="carton_product_id" required class="w-full rounded-lg border-slate-300 text-sm">
                            @foreach($cartonProducts as $carton)
                                <option value="{{ $carton->id }}">{{ $carton->box_title }} (Rs.{{ $carton->unit_selling_price }}/pc)</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Quantity Dispatched (Pcs)</label>
                        <input type="number" name="total_pieces" min="1" required class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Total Bundles</label>
                        <input type="number" name="total_bundles" min="1" class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Vehicle No.</label>
                        <input type="text" name="vehicle_number" required placeholder="e.g. SL-1190" class="w-full rounded-lg border-slate-300 text-sm uppercase" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Driver Name</label>
                        <input type="text" name="driver_name" required class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-700 pt-1">
                    <input type="checkbox" name="create_invoice" value="1" checked class="rounded border-slate-300 text-blue-600" />
                    <span>Automatically generate Sales Invoice & debit Client Khata</span>
                </label>
                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-challan')" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-sm font-medium hover:bg-navy-800">Dispatch & Generate Challan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

