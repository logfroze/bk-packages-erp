<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\PartyLedgerEntry;
use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KhataController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->get('type', 'CUSTOMER');
        $classification = $request->get('classification', 'ALL');
        $search = $request->get('q', '');

        $query = Party::query()->orderBy('name');

        if ($type !== 'ALL') {
            $query->where('type', $type);
        }

        if ($classification !== 'ALL') {
            $query->where('classification', $classification);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('vendor_category', 'like', "%{$search}%");
            });
        }

        $parties = $query->get();

        $totalReceivables = Party::where('current_balance', '>', 0)->sum('current_balance');
        $totalPayables = abs(Party::where('current_balance', '<', 0)->sum('current_balance'));
        $localVendorPayables = abs(Party::where('classification', 'LOCAL_VENDOR_SHOP')->where('current_balance', '<', 0)->sum('current_balance'));

        $cashReceipts = Voucher::where('voucher_type', 'RECEIPT_VOUCHER')->where('payment_mode', 'CASH')->sum('total_amount');
        $cashPayments = Voucher::whereIn('voucher_type', ['PAYMENT_VOUCHER', 'EXPENSE_VOUCHER'])->where('payment_mode', 'CASH')->sum('total_amount');
        $cashInHand = max(0, $cashReceipts - $cashPayments);

        return view('khata.index', compact(
            'parties',
            'type',
            'classification',
            'search',
            'totalReceivables',
            'totalPayables',
            'localVendorPayables',
            'cashInHand'
        ));
    }

    public function show(Party $party): View
    {
        $entries = $party->ledgerEntries()->with('voucher')->latest('entry_date')->latest('id')->get();
        $totalDebit = $entries->sum('debit');
        $totalCredit = $entries->sum('credit');

        return view('khata.show', compact('party', 'entries', 'totalDebit', 'totalCredit'));
    }

    public function storeParty(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:CUSTOMER,SUPPLIER,TRANSPORTER',
            'classification' => 'nullable|in:CORPORATE_CLIENT,PAPER_MILL,LOCAL_VENDOR_SHOP,GENERAL_SUPPLIER,TRANSPORTER',
            'vendor_category' => 'nullable|string|max:60',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'required|string|max:50',
            'city' => 'required|string|max:100',
            'address' => 'nullable|string',
            'opening_balance' => 'nullable|numeric',
            'balance_type' => 'nullable|in:RECEIVABLE,PAYABLE',
        ]);

        DB::transaction(function () use ($validated) {
            $rawOpening = abs((float) ($validated['opening_balance'] ?? 0));
            $balanceType = $validated['balance_type'] ?? ($validated['type'] === 'SUPPLIER' ? 'PAYABLE' : 'RECEIVABLE');
            $opening = $balanceType === 'PAYABLE' ? -$rawOpening : $rawOpening;

            $prefix = match ($validated['classification'] ?? '') {
                'LOCAL_VENDOR_SHOP' => 'VND',
                'PAPER_MILL' => 'MILL',
                default => substr($validated['type'], 0, 4),
            };

            $code = $prefix . '-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['name']), 0, 5)) . '-' . rand(10, 99);

            $classification = $validated['classification'] ?? ($validated['type'] === 'SUPPLIER' ? 'LOCAL_VENDOR_SHOP' : 'CORPORATE_CLIENT');

            $party = Party::create([
                'code' => $code,
                'name' => trim($validated['name']),
                'type' => $validated['type'],
                'classification' => $classification,
                'vendor_category' => $validated['vendor_category'] ?? null,
                'contact_person' => $validated['contact_person'] ?? null,
                'phone' => trim($validated['phone']),
                'city' => trim($validated['city']),
                'address' => $validated['address'] ?? null,
                'opening_balance' => $opening,
                'current_balance' => $opening,
            ]);

            if ($opening != 0) {
                PartyLedgerEntry::create([
                    'party_id' => $party->id,
                    'entry_date' => now()->toDateString(),
                    'narration' => 'Opening Balance B/F (' . ($opening > 0 ? 'Receivable/Lena' : 'Payable/Dena') . ')',
                    'debit' => $opening > 0 ? $opening : 0,
                    'credit' => $opening < 0 ? abs($opening) : 0,
                    'running_balance' => $opening,
                ]);
            }
        });

        return back()->with('success', "Party / Vendor account '{$validated['name']}' created successfully.");
    }

    public function updateParty(Request $request, Party $party): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'required|string|max:50',
            'city' => 'required|string|max:100',
            'address' => 'nullable|string',
            'classification' => 'required|in:CORPORATE_CLIENT,PAPER_MILL,LOCAL_VENDOR_SHOP,GENERAL_SUPPLIER,TRANSPORTER',
            'vendor_category' => 'nullable|string|max:60',
        ]);

        $party->update([
            'name' => trim($validated['name']),
            'contact_person' => $validated['contact_person'] ?? null,
            'phone' => trim($validated['phone']),
            'city' => trim($validated['city']),
            'address' => $validated['address'] ?? null,
            'classification' => $validated['classification'],
            'vendor_category' => $validated['vendor_category'] ?? null,
        ]);

        return back()->with('success', "Party details for '{$party->name}' updated.");
    }

    public function adjustBalance(Request $request, Party $party): RedirectResponse
    {
        $validated = $request->validate([
            'adjustment_type' => 'required|in:DEBIT_INCREASE,CREDIT_DECREASE',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $party) {
            $amount = (float) $validated['amount'];
            $isDebit = $validated['adjustment_type'] === 'DEBIT_INCREASE';
            $debit = $isDebit ? $amount : 0.00;
            $credit = $isDebit ? 0.00 : $amount;

            $newBalance = $party->current_balance + $debit - $credit;

            PartyLedgerEntry::create([
                'party_id' => $party->id,
                'entry_date' => now()->toDateString(),
                'narration' => 'Audit Balance Adjustment: ' . trim($validated['reason']),
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $newBalance,
            ]);

            $party->update(['current_balance' => $newBalance]);
        });

        return back()->with('success', "Balance for '{$party->name}' adjusted safely with ledger audit trail.");
    }

    public function storeVoucher(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'voucher_type' => 'required|in:RECEIPT_VOUCHER,PAYMENT_VOUCHER,SALES_INVOICE,PURCHASE_BILL,EXPENSE_VOUCHER',
            'party_id' => 'nullable|exists:parties,id',
            'total_amount' => 'required|numeric|min:1',
            'payment_mode' => 'required|in:CASH,CHEQUE,ONLINE_BANK,CREDIT',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'required|string|max:500',
            'voucher_date' => 'nullable|date',
        ]);

        DB::transaction(function () use ($validated) {
            $lastId = Voucher::max('id') ?? 0;
            $prefix = match ($validated['voucher_type']) {
                'RECEIPT_VOUCHER' => 'CR-',
                'PAYMENT_VOUCHER' => 'PV-',
                'SALES_INVOICE' => 'INV-',
                'PURCHASE_BILL' => 'PB-',
                default => 'EXP-',
            };
            $voucherNumber = $prefix . (1053 + $lastId);

            $voucher = Voucher::create([
                'voucher_number' => $voucherNumber,
                'voucher_type' => $validated['voucher_type'],
                'voucher_date' => $validated['voucher_date'] ?? now()->toDateString(),
                'party_id' => $validated['party_id'] ?? null,
                'total_amount' => $validated['total_amount'],
                'payment_mode' => $validated['payment_mode'],
                'reference_number' => $validated['reference_number'] ?? null,
                'description' => $validated['description'],
            ]);

            if (!empty($validated['party_id'])) {
                $party = Party::lockForUpdate()->findOrFail($validated['party_id']);
                $amount = (float) $validated['total_amount'];

                // Debit increases balance (Receivable / Payment made to supplier); Credit decreases balance (Receipt from customer / Purchase bill)
                $isDebit = in_array($validated['voucher_type'], ['SALES_INVOICE', 'PAYMENT_VOUCHER']);
                $debit = $isDebit ? $amount : 0.00;
                $credit = $isDebit ? 0.00 : $amount;

                $newBalance = $party->current_balance + $debit - $credit;

                $refNote = !empty($validated['reference_number']) ? " (Ref: {$validated['reference_number']})" : '';
                PartyLedgerEntry::create([
                    'voucher_id' => $voucher->id,
                    'party_id' => $party->id,
                    'entry_date' => $voucher->voucher_date,
                    'narration' => $validated['description'] . $refNote,
                    'debit' => $debit,
                    'credit' => $credit,
                    'running_balance' => $newBalance,
                ]);

                $party->update(['current_balance' => $newBalance]);
            }
        });

        return back()->with('success', 'Voucher recorded & party ledger updated.');
    }
}
