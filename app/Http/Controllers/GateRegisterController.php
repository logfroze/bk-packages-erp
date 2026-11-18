<?php

namespace App\Http\Controllers;

use App\Models\CartonProduct;
use App\Models\DocumentSequence;
use App\Models\GateRegister;
use App\Models\MediaVaultItem;
use App\Models\Party;
use App\Models\PartyLedgerEntry;
use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class GateRegisterController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->get('filter', 'ALL');

        $query = GateRegister::with('party')->latest('time_in');

        if ($filter === 'INWARD') {
            $query->where('movement_type', 'INWARD_RAW_MATERIAL');
        } elseif ($filter === 'OUTWARD') {
            $query->where('movement_type', 'OUTWARD_DISPATCH');
        } elseif ($filter === 'IN_YARD') {
            $query->whereNotIn('status', ['COMPLETED', 'EXITED']);
        }

        $entries = $query->take(60)->get();
        $parties = Cache::remember('all_parties_sorted', 60, fn() => Party::orderBy('name')->get());
        $cartons = Cache::remember('all_cartons_with_client', 60, fn() => CartonProduct::with('client')->orderBy('box_title')->get());

        $todayDate = today()->toDateString();
        $stats = Cache::remember('gate_stats_' . $todayDate, 20, function () use ($todayDate) {
            $gateAggregates = GateRegister::selectRaw("
                COUNT(CASE WHEN DATE(time_in) = '{$todayDate}' THEN 1 END) as total_today,
                COUNT(CASE WHEN status NOT IN ('COMPLETED', 'EXITED') THEN 1 END) as in_yard,
                COALESCE(SUM(CASE WHEN movement_type = 'INWARD_RAW_MATERIAL' AND DATE(time_in) = '{$todayDate}' THEN net_weight_kg ELSE 0 END), 0) as reels_kg,
                COALESCE(SUM(CASE WHEN movement_type = 'OUTWARD_DISPATCH' AND DATE(time_in) = '{$todayDate}' THEN total_pieces ELSE 0 END), 0) as boxes_dispatched
            ")->first();

            return [
                'total_today' => (int) ($gateAggregates->total_today ?? 0),
                'in_yard' => (int) ($gateAggregates->in_yard ?? 0),
                'reels_unloaded_tons' => round(((float) ($gateAggregates->reels_kg ?? 0)) / 1000, 1),
                'boxes_dispatched' => (int) ($gateAggregates->boxes_dispatched ?? 0),
            ];
        });

        $previewGatePass = DocumentSequence::previewNextNumber('GATE_PASS');
        $previewChallan = DocumentSequence::previewNextNumber('DELIVERY_CHALLAN');
        $previewInvoice = DocumentSequence::previewNextNumber('SALES_INVOICE');

        return view('gate.index', compact('entries', 'parties', 'cartons', 'filter', 'stats', 'previewGatePass', 'previewChallan', 'previewInvoice'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'gate_pass_number' => 'nullable|string|max:50',
            'movement_type' => 'required|in:INWARD_RAW_MATERIAL,OUTWARD_DISPATCH,GENERAL',
            'vehicle_number' => 'required|string|max:50',
            'vehicle_type' => 'nullable|string|max:50',
            'driver_name' => 'required|string|max:100',
            'driver_phone' => 'nullable|string|max:50',
            'party_id' => 'required|exists:parties,id',
            'carton_product_id' => 'nullable|exists:carton_products,id',
            'material_summary' => 'nullable|string|max:255',
            'gross_weight_kg' => 'nullable|numeric|min:0',
            'tare_weight_kg' => 'nullable|numeric|min:0',
            'total_pieces' => 'nullable|integer|min:0',
            'total_bundles' => 'nullable|integer|min:0',
            'bilti_challan_number' => 'nullable|string|max:80',
            'invoice_number' => 'nullable|string|max:50',
            'create_invoice' => 'nullable|boolean',
            'receipt_photo' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:20480',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt_photo')) {
            $file = $request->file('receipt_photo');
            $receiptPath = $this->handleReceiptUpload($file, 'gate_');
        }

        DB::transaction(function () use ($validated, $receiptPath, $request) {
            $gpNumber = !empty($validated['gate_pass_number'])
                ? trim($validated['gate_pass_number'])
                : DocumentSequence::generateNextNumber('GATE_PASS');

            $challanNumber = !empty($validated['bilti_challan_number'])
                ? trim($validated['bilti_challan_number'])
                : DocumentSequence::generateNextNumber('DELIVERY_CHALLAN');

            $gross = $validated['gross_weight_kg'] ?? null;
            $tare = $validated['tare_weight_kg'] ?? null;
            $net = ($gross !== null && $tare !== null) ? abs($gross - $tare) : null;

            $materialSummary = $validated['material_summary'] ?? null;
            $carton = null;
            if (!empty($validated['carton_product_id'])) {
                $carton = CartonProduct::find($validated['carton_product_id']);
                $materialSummary = $materialSummary ?: "{$carton->box_title} ({$carton->ply_type})";
            }

            $status = ($net !== null && $validated['movement_type'] === 'OUTWARD_DISPATCH') ? 'EXITED' : ($validated['movement_type'] === 'INWARD_RAW_MATERIAL' ? 'UNLOADING' : 'LOADING');

            $gate = GateRegister::create([
                'gate_pass_number' => $gpNumber,
                'movement_type' => $validated['movement_type'],
                'vehicle_number' => strtoupper($validated['vehicle_number']),
                'vehicle_type' => $validated['vehicle_type'] ?? 'Truck',
                'driver_name' => $validated['driver_name'],
                'driver_phone' => $validated['driver_phone'] ?? null,
                'party_id' => $validated['party_id'],
                'bilti_challan_number' => $challanNumber,
                'material_summary' => $materialSummary,
                'gross_weight_kg' => $gross,
                'tare_weight_kg' => $tare,
                'net_weight_kg' => $net,
                'total_pieces' => $validated['total_pieces'] ?? null,
                'total_bundles' => $validated['total_bundles'] ?? null,
                'status' => $status,
                'receipt_photo_path' => $receiptPath,
                'time_in' => now(),
                'time_out' => $status === 'EXITED' ? now() : null,
            ]);

            $party = Party::find($validated['party_id']);

            // Deduct finished carton stock & auto-bill if outward dispatch with carton selected
            if ($carton && !empty($validated['total_pieces'])) {
                $pieces = (int) $validated['total_pieces'];
                $carton->decrement('stock_pieces', min($carton->stock_pieces, $pieces));

                if ($request->boolean('create_invoice') && $carton->unit_selling_price > 0) {
                    $amount = $pieces * $carton->unit_selling_price;
                    $party = Party::findOrFail($validated['party_id']);
                    $vNumber = !empty($validated['invoice_number'])
                        ? trim($validated['invoice_number'])
                        : DocumentSequence::generateNextNumber('SALES_INVOICE');

                    $voucher = Voucher::create([
                        'voucher_number' => $vNumber,
                        'voucher_type' => 'SALES_INVOICE',
                        'voucher_date' => now()->toDateString(),
                        'party_id' => $party->id,
                        'gate_register_id' => $gate->id,
                        'total_amount' => $amount,
                        'payment_mode' => 'CREDIT',
                        'description' => number_format($pieces) . " Pcs {$carton->box_title} ({$gpNumber}, {$gate->vehicle_number})",
                        'receipt_photo_path' => $receiptPath,
                    ]);

                    $newBalance = $party->current_balance + $amount;
                    PartyLedgerEntry::create([
                        'voucher_id' => $voucher->id,
                        'party_id' => $party->id,
                        'entry_date' => now()->toDateString(),
                        'narration' => $voucher->description,
                        'debit' => $amount,
                        'credit' => 0.00,
                        'running_balance' => $newBalance,
                    ]);

                    $party->update(['current_balance' => $newBalance]);
                }
            }

            // Mirror into Media Vault
            if ($receiptPath) {
                $this->mirrorToVault(
                    $receiptPath,
                    "Gate Challan: {$gpNumber} / {$gate->vehicle_number} (" . ($party ? $party->name : 'Gate') . ")",
                    'GATE_CHALLAN',
                    $party ? $party->name : null,
                    $gate->id,
                    "Vehicle {$gate->vehicle_number}, Challan/Bilti: {$challanNumber}"
                );
            }
        });

        Cache::forget('gate_stats_' . today()->toDateString());
        return redirect()->route('gate.index')->with('success', 'Vehicle entry logged at security gate.');
    }

    public function updateWeightAndExit(Request $request, GateRegister $gate): RedirectResponse
    {
        $validated = $request->validate([
            'gross_weight_kg' => 'required|numeric|min:1',
            'tare_weight_kg' => 'required|numeric|min:1',
            'receipt_photo' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:20480',
        ]);

        $receiptPath = $gate->receipt_photo_path;
        if ($request->hasFile('receipt_photo')) {
            $file = $request->file('receipt_photo');
            $newPath = $this->handleReceiptUpload($file, 'weigh_slip_');
            if ($newPath) {
                $receiptPath = $newPath;
                $this->mirrorToVault(
                    $receiptPath,
                    "Weighbridge Scale Slip: {$gate->gate_pass_number} ({$gate->vehicle_number})",
                    'GATE_CHALLAN',
                    $gate->party?->name,
                    $gate->id,
                    "Gross: {$validated['gross_weight_kg']} kg, Tare: {$validated['tare_weight_kg']} kg"
                );
            }
        }

        $net = abs($validated['gross_weight_kg'] - $validated['tare_weight_kg']);

        $gate->update([
            'gross_weight_kg' => $validated['gross_weight_kg'],
            'tare_weight_kg' => $validated['tare_weight_kg'],
            'net_weight_kg' => $net,
            'receipt_photo_path' => $receiptPath,
            'status' => $gate->movement_type === 'INWARD_RAW_MATERIAL' ? 'COMPLETED' : 'EXITED',
            'time_out' => now(),
        ]);

        Cache::forget('gate_stats_' . today()->toDateString());
        return back()->with('success', "Weighbridge ticket captured for {$gate->gate_pass_number} (Net: {$net} kg) & marked exited.");
    }

    public function update(Request $request, GateRegister $gate): RedirectResponse
    {
        $validated = $request->validate([
            'gate_pass_number' => 'nullable|string|max:50',
            'movement_type' => 'required|in:INWARD_RAW_MATERIAL,OUTWARD_DISPATCH,GENERAL',
            'vehicle_number' => 'required|string|max:50',
            'vehicle_type' => 'nullable|string|max:50',
            'driver_name' => 'required|string|max:100',
            'driver_phone' => 'nullable|string|max:50',
            'party_id' => 'required|exists:parties,id',
            'carton_product_id' => 'nullable|exists:carton_products,id',
            'material_summary' => 'nullable|string|max:255',
            'gross_weight_kg' => 'nullable|numeric|min:0',
            'tare_weight_kg' => 'nullable|numeric|min:0',
            'total_pieces' => 'nullable|integer|min:0',
            'total_bundles' => 'nullable|integer|min:0',
            'bilti_challan_number' => 'nullable|string|max:80',
            'status' => 'required|in:LOADING,UNLOADING,EXITED,COMPLETED,GENERAL',
            'receipt_photo' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:20480',
        ]);

        $receiptPath = $gate->receipt_photo_path;
        if ($request->hasFile('receipt_photo')) {
            $newPath = $this->handleReceiptUpload($request->file('receipt_photo'), 'gate_edit_');
            if ($newPath) {
                $receiptPath = $newPath;
            }
        }

        $gross = $validated['gross_weight_kg'] ?? null;
        $tare = $validated['tare_weight_kg'] ?? null;
        $net = ($gross !== null && $tare !== null) ? abs($gross - $tare) : $gate->net_weight_kg;

        $materialSummary = $validated['material_summary'] ?? null;
        if (!empty($validated['carton_product_id']) && empty($materialSummary)) {
            $carton = CartonProduct::find($validated['carton_product_id']);
            if ($carton) {
                $materialSummary = "{$carton->box_title} ({$carton->ply_type})";
            }
        }

        $gate->update([
            'gate_pass_number' => !empty($validated['gate_pass_number']) ? trim($validated['gate_pass_number']) : $gate->gate_pass_number,
            'movement_type' => $validated['movement_type'],
            'vehicle_number' => strtoupper($validated['vehicle_number']),
            'vehicle_type' => $validated['vehicle_type'] ?? 'Truck',
            'driver_name' => $validated['driver_name'],
            'driver_phone' => $validated['driver_phone'] ?? null,
            'party_id' => $validated['party_id'],
            'bilti_challan_number' => !empty($validated['bilti_challan_number']) ? trim($validated['bilti_challan_number']) : $gate->bilti_challan_number,
            'material_summary' => $materialSummary ?: $gate->material_summary,
            'gross_weight_kg' => $gross,
            'tare_weight_kg' => $tare,
            'net_weight_kg' => $net,
            'total_pieces' => $validated['total_pieces'] ?? null,
            'total_bundles' => $validated['total_bundles'] ?? null,
            'status' => $validated['status'],
            'receipt_photo_path' => $receiptPath,
            'time_out' => in_array($validated['status'], ['EXITED', 'COMPLETED']) ? ($gate->time_out ?? now()) : null,
        ]);

        Cache::forget('gate_stats_' . today()->toDateString());
        return redirect()->route('gate.index')->with('success', "Gate entry {$gate->gate_pass_number} updated successfully.");
    }

    public function destroy(GateRegister $gate): RedirectResponse
    {
        $gp = $gate->gate_pass_number;
        if ($gate->receipt_photo_path && !str_starts_with($gate->receipt_photo_path, 'data:') && File::exists(public_path($gate->receipt_photo_path))) {
            @unlink(public_path($gate->receipt_photo_path));
        }
        $gate->delete();

        Cache::forget('gate_stats_' . today()->toDateString());
        return redirect()->route('gate.index')->with('success', "Gate register record {$gp} deleted successfully.");
    }

    private function handleReceiptUpload(?\Illuminate\Http\UploadedFile $file, string $prefix = 'gate_'): ?string
    {
        if (!$file) {
            return null;
        }

        try {
            $uploadDir = public_path('uploads/receipts');
            if (!File::isDirectory($uploadDir)) {
                File::makeDirectory($uploadDir, 0777, true, true);
            }
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $fileName = $prefix . time() . '_' . uniqid() . '.' . $ext;
            $file->move($uploadDir, $fileName);
            return '/uploads/receipts/' . $fileName;
        } catch (\Throwable $e) {
            // Serverless/Vercel read-only filesystem fallback
            $mime = $file->getClientMimeType() ?: 'image/jpeg';
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
        }
    }

    private function mirrorToVault(?string $path, string $title, string $category, ?string $partyName, int $sourceId, string $notes): void
    {
        if (!$path) return;
        try {
            MediaVaultItem::create([
                'user_id' => Auth::id(),
                'title' => substr($title, 0, 240),
                'category' => $category,
                'file_path' => $path,
                'file_name' => str_starts_with($path, 'data:') ? 'camera_capture.jpg' : basename($path),
                'linked_party_or_shop' => $partyName ? substr($partyName, 0, 150) : null,
                'source_module' => 'GATE_REGISTER',
                'source_id' => $sourceId,
                'notes' => substr($notes, 0, 1000),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Media vault mirror failed: ' . $e->getMessage());
        }
    }

    public function slip(GateRegister $gate): View
    {
        $gate->load('party');
        return view('gate.slip', compact('gate'));
    }
}
