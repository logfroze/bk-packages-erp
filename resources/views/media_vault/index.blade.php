@extends('layouts.app')

@section('title', 'Factory Media & Receipts Vault')
@section('breadcrumb', 'Media Vault')

@section('header_actions')
    @if(!auth()->user()?->isReadOnly())
        <div class="flex items-center gap-2">
            <button onclick="openModal('modal-upload-media')" class="px-3.5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs flex items-center gap-1.5 transition-colors">
                <span class="material-symbols-outlined text-[16px]">photo_camera</span>
                <span>+ Take Photo / Upload Bill</span>
            </button>
        </div>
    @endif
@endsection

@section('content')
<div class="space-y-6">

    <!-- Top Banner & Description -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider bg-blue-100 text-blue-800 border border-blue-200">
                        Visual Document Archive
                    </span>
                    <span class="text-xs text-slate-500">Live Camera Snap & Multi-Device Bill Uploads</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 mt-2">Factory Media & Receipts Vault</h1>
                <p class="text-xs text-slate-500 mt-1 max-w-2xl">
                    Every physical bill, daily factory food grocery pack photo, vendor receipt, weighbridge slip, and diesel voucher uploaded from mobile camera or PC in one central, audit-ready gallery.
                </p>
            </div>

            <!-- Category Filter Pills -->
            <div class="flex items-center gap-1.5 flex-wrap">
                @foreach($categories as $key => $label)
                    <a href="{{ route('media_vault.index', ['category' => $key, 'q' => $search]) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $category === $key ? 'bg-navy-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- 3 Quick Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Vault Receipts</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">photo_library</span>
                </div>
            </div>
            <div class="mt-2 text-2xl font-bold text-navy-900 num-tabular">
                {{ number_format($totalItemsCount) }} Photos / Slips
            </div>
            <div class="text-xs text-slate-400 mt-1">Archived in factory storage</div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Today's New Uploads</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">today</span>
                </div>
            </div>
            <div class="mt-2 text-2xl font-bold text-emerald-600 num-tabular">
                {{ number_format($todayUploadsCount) }} Snaps
            </div>
            <div class="text-xs text-slate-400 mt-1">Captured today on {{ now()->format('d M') }}</div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Linked Expense Bills</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                </div>
            </div>
            <div class="mt-2 text-2xl font-bold text-amber-700 num-tabular">
                Rs. {{ number_format($totalLinkedAmount) }}
            </div>
            <div class="text-xs text-slate-400 mt-1">Total physical voucher value</div>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
        <form method="GET" action="{{ route('media_vault.index') }}" class="flex flex-col sm:flex-row gap-3">
            <input type="hidden" name="category" value="{{ $category }}" />
            <div class="relative flex-1">
                <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-[18px]">search</span>
                <input type="text" name="q" value="{{ $search }}" placeholder="Search by shop/party, grocery title, note, or file name..."
                       class="w-full pl-9 pr-4 py-2 rounded-lg border-slate-300 text-xs bg-slate-50 focus:bg-white focus:border-blue-600 focus:ring-blue-600" />
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs">
                    Filter Vault
                </button>
                @if($search || $category !== 'ALL')
                    <a href="{{ route('media_vault.index') }}" class="px-3 py-2 rounded-lg border border-slate-300 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Media Vault Gallery Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
        @forelse($items as $item)
            @php
                $isPdf = str_ends_with(strtolower($item->file_path), '.pdf') || $item->mime_type === 'application/pdf';
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs hover:border-blue-400 transition-all flex flex-col justify-between group">
                <!-- Thumbnail Preview / PDF Icon -->
                <div class="relative aspect-4/3 bg-slate-100 flex items-center justify-center overflow-hidden cursor-pointer"
                     onclick="viewFullImage('{{ asset($item->file_path) }}', '{{ addslashes($item->title) }}', '{{ $isPdf ? 'pdf' : 'image' }}', '{{ addslashes($item->categoryLabel()) }}', '{{ $item->linked_amount ? 'Rs. ' . number_format($item->linked_amount) : '' }}', '{{ addslashes($item->linked_party_or_shop ?? '') }}')">
                    @if($isPdf)
                        <div class="flex flex-col items-center justify-center text-red-600 p-4">
                            <span class="material-symbols-outlined text-5xl">picture_as_pdf</span>
                            <span class="text-[11px] font-bold mt-1 text-slate-700 truncate max-w-full">{{ $item->file_name }}</span>
                            <span class="text-[10px] text-slate-400 mt-0.5">Click to Open PDF</span>
                        </div>
                    @else
                        <img src="{{ asset($item->file_path) }}" alt="{{ $item->title }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                             loading="lazy" />
                        <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <span class="p-2 rounded-full bg-white/90 text-slate-900 shadow-md">
                                <span class="material-symbols-outlined text-[20px]">zoom_in</span>
                            </span>
                        </div>
                    @endif

                    <!-- Category Pill Badge Overlaid -->
                    <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded text-[10px] font-bold border shadow-xs {{ $item->categoryBadgeColor() }}">
                        {{ $item->categoryLabel() }}
                    </span>
                </div>

                <!-- Card Details -->
                <div class="p-4 flex-1 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between text-[11px] text-slate-400 mb-1">
                            <span>{{ $item->created_at?->format('d M Y, H:i') }}</span>
                            <span>{{ $item->formattedSize() }}</span>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 line-clamp-1" title="{{ $item->title }}">{{ $item->title }}</h3>

                        @if($item->linked_party_or_shop)
                            <p class="text-xs text-blue-600 font-semibold mt-0.5 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[13px]">storefront</span>
                                <span>{{ $item->linked_party_or_shop }}</span>
                            </p>
                        @endif

                        @if($item->notes)
                            <p class="text-[11px] text-slate-500 mt-1 line-clamp-2">{{ $item->notes }}</p>
                        @endif
                    </div>

                    <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between">
                        <div>
                            @if($item->linked_amount)
                                <span class="text-xs font-bold text-emerald-700 num-tabular">
                                    Rs. {{ number_format($item->linked_amount) }}
                                </span>
                            @else
                                <span class="text-[11px] text-slate-400 font-medium">No Amount</span>
                            @endif
                        </div>

                        <div class="flex items-center gap-1">
                            <a href="{{ asset($item->file_path) }}" download="{{ $item->file_name }}"
                               class="p-1 rounded text-slate-400 hover:text-blue-600 hover:bg-slate-50 transition-colors" title="Download">
                                <span class="material-symbols-outlined text-[16px]">download</span>
                            </a>
                            @if(!auth()->user()?->isReadOnly())
                                <form action="{{ route('media_vault.destroy', $item) }}" method="POST" onsubmit="return confirm('Delete this receipt from vault?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 rounded text-slate-400 hover:text-red-600 hover:bg-slate-50 transition-colors" title="Delete">
                                        <span class="material-symbols-outlined text-[16px]">delete</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-400">
                <span class="material-symbols-outlined text-4xl block mb-2 text-slate-300">photo_library</span>
                No media receipts uploaded yet. Click "+ Take Photo / Upload Bill" above or upload directly from the Local Party section!
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($items->hasPages())
        <div class="mt-6">
            {{ $items->links() }}
        </div>
    @endif

</div>
@endsection

@section('modals')
<!-- Modal: Take Photo / Upload Bill -->
<div id="modal-upload-media" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-2xl max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">add_photo_alternate</span>
                </div>
                <h3 class="text-base font-bold text-slate-900">Upload Receipt / Take Live Photo</h3>
            </div>
            <button onclick="closeModal('modal-upload-media')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="{{ route('media_vault.store') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf

            <!-- Live Camera Snap vs File Picker Trigger -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Select Picture or Snap with Camera *</label>
                <div class="grid grid-cols-2 gap-2 mb-2">
                    <label class="cursor-pointer border-2 border-dashed border-blue-400 hover:border-blue-600 bg-blue-50/50 hover:bg-blue-50 p-3 rounded-xl flex flex-col items-center justify-center text-center transition-colors">
                        <span class="material-symbols-outlined text-2xl text-blue-600">photo_camera</span>
                        <span class="text-xs font-bold text-blue-900 mt-1">Take Live Photo</span>
                        <span class="text-[10px] text-slate-500">Opens Camera</span>
                        <input type="file" name="media_file" accept="image/*" capture="environment" class="hidden" onchange="handleVaultPreview(this)" />
                    </label>

                    <label class="cursor-pointer border-2 border-dashed border-slate-300 hover:border-slate-500 bg-slate-50 hover:bg-slate-100 p-3 rounded-xl flex flex-col items-center justify-center text-center transition-colors">
                        <span class="material-symbols-outlined text-2xl text-slate-600">upload_file</span>
                        <span class="text-xs font-bold text-slate-800 mt-1">Browse Gallery</span>
                        <span class="text-[10px] text-slate-500">JPG, PNG, PDF</span>
                        <input type="file" name="media_file" accept="image/*,.pdf" class="hidden" onchange="handleVaultPreview(this)" />
                    </label>
                </div>

                <div id="vault-preview-box" class="hidden p-2 rounded-xl bg-slate-50 border border-slate-200 flex items-center gap-3">
                    <img id="vault-preview-img" src="" class="w-14 h-14 object-cover rounded-lg border border-slate-200" />
                    <div class="flex-1 truncate">
                        <span id="vault-preview-name" class="text-xs font-bold text-slate-900 block truncate"></span>
                        <span id="vault-preview-size" class="text-[11px] text-slate-500"></span>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Receipt Title / Description *</label>
                <input type="text" name="title" required placeholder="e.g. Daily factory lunch grocery pack / Al-Madina Hardware invoice"
                       class="w-full rounded-lg border-slate-300 text-xs focus:border-blue-600 focus:ring-blue-600" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Receipt Category *</label>
                    <select name="category" required class="w-full rounded-lg border-slate-300 text-xs focus:border-blue-600 focus:ring-blue-600">
                        <option value="LOCAL_PARTY_BILL">Local Shop / Party Bill</option>
                        <option value="GROCERY_FOOD">Daily Food & Grocery Pack</option>
                        <option value="PETROL_FUEL">Petrol & Generator Fuel</option>
                        <option value="HARDWARE_TOOLS">Hardware & Machine Tools</option>
                        <option value="GATE_CHALLAN">Gate Weigh / Delivery Challan</option>
                        <option value="RECEIPT_VOUCHER">Payment Receipt Voucher</option>
                        <option value="GENERAL">General Receipt / Photo</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Bill Amount (Rs.)</label>
                    <input type="number" step="0.01" name="linked_amount" placeholder="e.g. 1500"
                           class="w-full rounded-lg border-slate-300 text-xs focus:border-blue-600 focus:ring-blue-600" />
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Vendor / Shop Name (Optional)</label>
                <input type="text" name="linked_party_or_shop" placeholder="e.g. Butt Karahi Hotel / Asif Autos Okara"
                       class="w-full rounded-lg border-slate-300 text-xs focus:border-blue-600 focus:ring-blue-600" />
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Notes / Extra Particulars</label>
                <textarea name="notes" rows="2" placeholder="e.g. 4 rotian, 2 daal plates for workers overtime shift"
                          class="w-full rounded-lg border-slate-300 text-xs focus:border-blue-600 focus:ring-blue-600"></textarea>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('modal-upload-media')" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-navy-900 text-white text-xs font-semibold hover:bg-navy-800 shadow-xs">
                    Save into Vault
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Full Screen Image / PDF Viewer -->
<div id="modal-image-viewer" class="fixed inset-0 z-50 hidden bg-slate-950/90 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative bg-slate-900 border border-slate-800 rounded-2xl max-w-4xl w-full max-h-[95vh] flex flex-col overflow-hidden shadow-2xl">
        <!-- Viewer Header -->
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

        <!-- Viewer Content Area -->
        <div class="p-4 flex-1 flex items-center justify-center overflow-auto bg-black/40">
            <img id="viewer-img" src="" class="max-h-[75vh] max-w-full object-contain rounded-lg shadow-lg hidden" />
            <iframe id="viewer-pdf" src="" class="w-full h-[75vh] rounded-lg hidden"></iframe>
        </div>
    </div>
</div>

<script>
function handleVaultPreview(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const previewBox = document.getElementById('vault-preview-box');
        const previewImg = document.getElementById('vault-preview-img');
        const previewName = document.getElementById('vault-preview-name');
        const previewSize = document.getElementById('vault-preview-size');

        previewName.textContent = file.name;
        previewSize.textContent = (file.size / 1024).toFixed(1) + ' KB';

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewImg.classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        } else {
            previewImg.classList.add('hidden');
        }

        previewBox.classList.remove('hidden');
    }
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
</script>
@endsection
