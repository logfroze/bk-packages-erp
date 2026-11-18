<?php

namespace App\Http\Controllers;

use App\Models\MediaVaultItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class MediaVaultController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->query('category', 'ALL');
        $search = $request->query('q', '');

        $query = MediaVaultItem::with('user')->latest('id');

        if ($category && $category !== 'ALL') {
            $query->where('category', $category);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('linked_party_or_shop', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $items = $query->paginate(24);

        $totalItemsCount = MediaVaultItem::count();
        $todayUploadsCount = MediaVaultItem::whereDate('created_at', today())->count();
        $totalLinkedAmount = (float) MediaVaultItem::sum('linked_amount');

        $categories = [
            'ALL' => 'All Media & Bills',
            'LOCAL_PARTY_BILL' => 'Local Shop / Party Bills',
            'GROCERY_FOOD' => 'Daily Food & Grocery Packs',
            'PETROL_FUEL' => 'Petrol & Generator Fuel',
            'HARDWARE_TOOLS' => 'Hardware & Machine Tools',
            'GATE_CHALLAN' => 'Gate Weigh / Delivery Challans',
            'RECEIPT_VOUCHER' => 'Payment Receipt Vouchers',
            'GENERAL' => 'General Receipts / Photos',
        ];

        return view('media_vault.index', compact(
            'items',
            'category',
            'search',
            'totalItemsCount',
            'todayUploadsCount',
            'totalLinkedAmount',
            'categories'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'category' => 'required|string|max:60',
            'media_file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf,gif|max:20480', // max 20MB
            'linked_amount' => 'nullable|numeric|min:0',
            'linked_party_or_shop' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:1000',
        ]);

        $file = $request->file('media_file');
        $uploadInfo = $this->handleFileUpload($file);

        MediaVaultItem::create([
            'user_id' => Auth::id(),
            'title' => trim($validated['title']),
            'category' => $validated['category'],
            'file_path' => $uploadInfo['path'],
            'file_name' => $uploadInfo['name'],
            'mime_type' => $uploadInfo['mime'],
            'file_size_bytes' => $uploadInfo['size'],
            'linked_amount' => ! empty($validated['linked_amount']) ? (float) $validated['linked_amount'] : null,
            'linked_party_or_shop' => $validated['linked_party_or_shop'] ?? null,
            'source_module' => 'VAULT',
            'source_id' => null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('media_vault.index')->with('success', "Media uploaded to Vault successfully: {$validated['title']}");
    }

    public function update(Request $request, MediaVaultItem $item): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'category' => 'required|string|max:60',
            'media_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,gif|max:20480',
            'linked_amount' => 'nullable|numeric|min:0',
            'linked_party_or_shop' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:1000',
        ]);

        $updateData = [
            'title' => trim($validated['title']),
            'category' => $validated['category'],
            'linked_amount' => ! empty($validated['linked_amount']) ? (float) $validated['linked_amount'] : null,
            'linked_party_or_shop' => $validated['linked_party_or_shop'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];

        if ($request->hasFile('media_file')) {
            $file = $request->file('media_file');
            $uploadInfo = $this->handleFileUpload($file);
            if ($uploadInfo && !empty($uploadInfo['path'])) {
                if ($item->file_path && !str_starts_with($item->file_path, 'data:') && File::exists(public_path($item->file_path))) {
                    @unlink(public_path($item->file_path));
                }
                $updateData['file_path'] = $uploadInfo['path'];
                $updateData['file_name'] = $uploadInfo['name'];
                $updateData['mime_type'] = $uploadInfo['mime'];
                $updateData['file_size_bytes'] = $uploadInfo['size'];
            }
        }

        $item->update($updateData);

        return redirect()->route('media_vault.index')->with('success', "Media item '{$item->title}' updated successfully.");
    }

    public function destroy(MediaVaultItem $item): RedirectResponse
    {
        if ($item->file_path && !str_starts_with($item->file_path, 'data:') && File::exists(public_path($item->file_path))) {
            @unlink(public_path($item->file_path));
        }

        $item->delete();

        return redirect()->route('media_vault.index')->with('success', 'Media item removed from Vault.');
    }

    private function handleFileUpload($file): array
    {
        $origName = $file->getClientOriginalName();
        $mime = $file->getClientMimeType() ?: $file->getMimeType();
        $size = $file->getSize();
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');

        try {
            $uploadDir = public_path('uploads/receipts');
            if (!File::isDirectory($uploadDir)) {
                @File::makeDirectory($uploadDir, 0777, true, true);
            }
            $fileName = 'vault_' . time() . '_' . uniqid() . '.' . $ext;
            $file->move($uploadDir, $fileName);
            return [
                'path' => '/uploads/receipts/' . $fileName,
                'name' => $origName,
                'mime' => $mime,
                'size' => $size,
            ];
        } catch (\Throwable $e) {
            // Read-only filesystem fallback: base64 Data URI
            try {
                $content = @file_get_contents($file->getRealPath());
                if ($content !== false) {
                    $b64 = 'data:' . ($mime ?: 'image/jpeg') . ';base64,' . base64_encode($content);
                    return [
                        'path' => $b64,
                        'name' => $origName,
                        'mime' => $mime,
                        'size' => $size,
                    ];
                }
            } catch (\Throwable $ex) {}
            return [
                'path' => '',
                'name' => $origName,
                'mime' => $mime,
                'size' => $size,
            ];
        }
    }
}
