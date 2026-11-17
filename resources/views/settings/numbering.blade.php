@extends('layouts.app')

@section('title', 'Document Numbering & Bill Book Settings')
@section('breadcrumb', 'Bill Book Settings')

@section('header_actions')
    <a href="{{ route('dashboard') }}"
        class="px-3.5 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
        Back to Dashboard
    </a>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header Notice -->
        <div
            class="p-5 rounded-2xl bg-gradient-to-r from-navy-900 to-slate-800 text-white shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-400">format_list_numbered</span>
                    <h1 class="text-xl font-bold tracking-tight">Factory Bill Book & Document Sequences</h1>
                </div>
                <p class="text-xs text-slate-300 mt-1 max-w-2xl leading-relaxed">
                    Configure running serial numbers, prefixes, and padding for Delivery Challans, Gate Passes, and Sales
                    Invoices. Numbers advance automatically upon generation but can also be manually entered on vouchers.
                </p>
            </div>
            <div class="flex items-center gap-2 text-xs bg-navy-800/80 border border-slate-700 px-3.5 py-2 rounded-xl">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Independent Sequences Active</span>
            </div>
        </div>

        <!-- 3 Core Documents Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @php
                $docs = [
                    'DELIVERY_CHALLAN' => [
                        'title' => 'Delivery Challan (DC)',
                        'desc' => 'Issued for dispatched carton orders and partial shipments.',
                        'icon' => 'local_shipping',
                        'color' => 'blue',
                        'sample_usage' => 'Used by Storekeeper for outbound goods',
                    ],
                    'GATE_PASS' => [
                        'title' => 'Gate Pass (GP)',
                        'desc' => 'Issued at the factory weighbridge for truck entry and exit.',
                        'icon' => 'door_sliding',
                        'color' => 'emerald',
                        'sample_usage' => 'Used by Gate Operator at Weighbridge',
                    ],
                    'SALES_INVOICE' => [
                        'title' => 'Sales Invoice (INV)',
                        'desc' => 'Commercial sales bill posted to customer khata ledger.',
                        'icon' => 'receipt_long',
                        'color' => 'amber',
                        'sample_usage' => 'Used by Factory Accountant Mirza Sohail',
                    ],
                ];
            @endphp

            @foreach($docs as $type => $info)
                @php
                    $seq = $sequences[$type];
                    $preview = $seq->preview();
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="w-10 h-10 rounded-xl bg-{{ $info['color'] }}-50 text-{{ $info['color'] }}-600 flex items-center justify-center font-bold">
                                    <span class="material-symbols-outlined">{{ $info['icon'] }}</span>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">{{ $info['title'] }}</h3>
                                    <p class="text-[11px] text-slate-500">{{ $info['sample_usage'] }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Next Sequence Preview Box -->
                        <div class="my-4 p-4 rounded-xl bg-slate-50 border border-slate-200 text-center">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Next Document Number
                            </div>
                            <div class="text-2xl font-extrabold text-navy-900 tracking-wider num-tabular mt-1 font-mono">
                                {{ $preview }}
                            </div>
                        </div>

                        <form action="{{ route('settings.numbering.update') }}" method="POST" class="space-y-4">
                            @csrf
                            <input type="hidden" name="document_type" value="{{ $type }}" />

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Prefix (Hurouf)</label>
                                <input type="text" name="prefix" value="{{ old('prefix', $seq->prefix) }}" required
                                    class="w-full rounded-lg border-slate-300 text-sm font-semibold uppercase tracking-wider focus:border-blue-600 focus:ring-blue-600 font-mono" />
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Next Number</label>
                                    <input type="number" name="next_number" value="{{ old('next_number', $seq->next_number) }}"
                                        min="1" required
                                        class="w-full rounded-lg border-slate-300 text-sm font-semibold focus:border-blue-600 focus:ring-blue-600 font-mono" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Zero Padding</label>
                                    <select name="padding"
                                        class="w-full rounded-lg border-slate-300 text-sm font-medium focus:border-blue-600 focus:ring-blue-600">
                                        <option value="3" {{ $seq->padding == 3 ? 'selected' : '' }}>3 digits (001)</option>
                                        <option value="4" {{ $seq->padding == 4 ? 'selected' : '' }}>4 digits (0001)</option>
                                        <option value="5" {{ $seq->padding == 5 ? 'selected' : '' }}>5 digits (00001)</option>
                                        <option value="6" {{ $seq->padding == 6 ? 'selected' : '' }}>6 digits (000001)</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Suffix (Optional / e.g.
                                    /26)</label>
                                <input type="text" name="suffix" value="{{ old('suffix', $seq->suffix) }}"
                                    placeholder="e.g. /26 or leave blank"
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600 font-mono" />
                            </div>

                            <button type="submit"
                                class="w-full py-2.5 rounded-xl bg-navy-900 hover:bg-navy-800 text-white text-xs font-semibold transition-colors flex items-center justify-center gap-1.5 shadow-xs">
                                <span class="material-symbols-outlined text-[16px]">save</span>
                                <span>Save Sequence Format</span>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Client Guidelines Box -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200">
            <h4 class="text-sm font-bold text-slate-900 mb-2 flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-600">info</span>
                Factory Billing Rules (Mirza Sohail's Configuration)
            </h4>
            <ul class="text-xs text-slate-600 space-y-1.5 list-disc list-inside">
                <li><strong>Editable Anytime:</strong> If physical bill books change or the factory accountant needs to
                    reset to a new book starting number (e.g. starting from 5001), update it here with 1 click without
                    touching source code.</li>
                <li><strong>Manual Override:</strong> While auto-numbering generates sequential numbers, gate operators and
                    accountants can still type an exact custom reference number directly on the entry modal.</li>
                <li><strong>Audit Safe:</strong> Existing saved vouchers and gate passes retain their historical issued
                    numbers and are never altered when settings are modified.</li>
            </ul>
        </div>
    </div>
@endsection