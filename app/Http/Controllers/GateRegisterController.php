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

        $entries = $query->get();
        $parties = Party::orderBy('name')->get();
        $cartons = CartonProduct::with('client')->orderBy('box_title')->get();

        $stats = [
            'total_today' => GateRegister::whereDate('time_in', today())->count(),
            'in_yard' => GateRegister::whereNotIn('status', ['COMPLETED', 'EXITED'])->count(),
            'reels_unloaded_tons' => round(GateRegister::where('movement_type', 'INWARD_RAW_MATERIAL')->whereDate('time_in', today())->sum('net_weight_kg') / 1000, 1),
            'boxes_dispatched' => GateRegister::where('movement_type', 'OUTWARD_DISPATCH')->whereDate('time_in', today())->sum('total_pieces'),
        ];

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
            $uploadDir = public_path('uploads/receipts');
            if (!File::isDirectory($uploadDir)) {
                File::makeDirectory($uploadDir, 0777, true, true);
            }
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $fileName = 'gate_' . time() . '_' . uniqid() . '.' . $ext;
            $file->move($uploadDir, $fileName);
            $receiptPath = '/uploads/receipts/' . $fileName;
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
                MediaVaultItem::create([
                    'user_id' => Auth::id(),
                    'title' => "Gate Challan: {$gpNumber} / {$gate->vehicle_number} (" . ($party ? $party->name : 'Gate') . ")",
                    'category' => 'GATE_CHALLAN',
                    'file_path' => $receiptPath,
                    'file_name' => basename($receiptPath),
                    'linked_party_or_shop' => $party ? $party->name : null,
                    'source_module' => 'GATE_REGISTER',
                    'source_id' => $gate->id,
                    'notes' => "Vehicle {$gate->vehicle_number}, Challan/Bilti: {$challanNumber}",
                ]);
            }
        });

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
            $uploadDir = public_path('uploads/receipts');
            if (!File::isDirectory($uploadDir)) {
                File::makeDirectory($uploadDir, 0777, true, true);
            }
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $fileName = 'weigh_slip_' . time() . '_' . uniqid() . '.' . $ext;
            $file->move($uploadDir, $fileName);
            $receiptPath = '/uploads/receipts/' . $fileName;

            // Mirror weighbridge scale slip to vault
            MediaVaultItem::create([
                'user_id' => Auth::id(),
                'title' => "Weighbridge Scale Slip: {$gate->gate_pass_number} ({$gate->vehicle_number})",
                'category' => 'GATE_CHALLAN',
                'file_path' => $receiptPath,
                'file_name' => basename($receiptPath),
                'linked_party_or_shop' => $gate->party?->name,
                'source_module' => 'GATE_REGISTER',
                'source_id' => $gate->id,
                'notes' => "Gross: {$validated['gross_weight_kg']} kg, Tare: {$validated['tare_weight_kg']} kg",
            ]);
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

        return back()->with('success', "Weighbridge ticket captured for {$gate->gate_pass_number} (Net: {$net} kg) & marked exited.");
    }

    public function slip(GateRegister $gate): View
    {
        $gate->load('party');
        return view('gate.slip', compact('gate'));
    }
}
