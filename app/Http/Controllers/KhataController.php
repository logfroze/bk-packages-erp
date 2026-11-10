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
        $search = $request->get('q', '');

        $query = Party::query()->orderBy('name');

        if ($type !== 'ALL') {
            $query->where('type', $type);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $parties = $query->get();

        $totalReceivables = Party::where('type', 'CUSTOMER')->where('current_balance', '>', 0)->sum('current_balance');
        $totalPayables = abs(Party::where('type', 'SUPPLIER')->where('current_balance', '<', 0)->sum('current_balance'));

        $cashReceipts = Voucher::where('voucher_type', 'RECEIPT_VOUCHER')->where('payment_mode', 'CASH')->sum('total_amount');
        $cashPayments = Voucher::whereIn('voucher_type', ['PAYMENT_VOUCHER', 'EXPENSE_VOUCHER'])->where('payment_mode', 'CASH')->sum('total_amount');
        $cashInHand = max(0, $cashReceipts - $cashPayments);

        return view('khata.index', compact(
            'parties',
            'type',
            'search',
            'totalReceivables',
            'totalPayables',
            'cashInHand'
        ));
    }

    public function show(Party $party): View
    {
        $entries = $party->ledgerEntries()->with('voucher')->get();
        $totalDebit = $entries->sum('debit');
        $totalCredit = $entries->sum('credit');

        return view('khata.show', compact('party', 'entries', 'totalDebit', 'totalCredit'));
    }

    public function storeParty(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:CUSTOMER,SUPPLIER,TRANSPORTER',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'required|string|max:50',
            'city' => 'required|string|max:100',
            'address' => 'nullable|string',
            'opening_balance' => 'nullable|numeric',
        ]);

        DB::transaction(function () use ($validated) {
            $opening = (float) ($validated['opening_balance'] ?? 0);
            if ($validated['type'] === 'SUPPLIER' && $opening > 0) {
                $opening = -$opening; // Supplier balances are credit/payable by convention
            }

            $prefix = substr($validated['type'], 0, 4);
            $code = $prefix . '-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['name']), 0, 5)) . '-' . rand(10, 99);

            $party = Party::create([
                'code' => $code,
                'name' => $validated['name'],
                'type' => $validated['type'],
                'contact_person' => $validated['contact_person'] ?? null,
                'phone' => $validated['phone'],
                'city' => $validated['city'],
                'address' => $validated['address'] ?? null,
                'opening_balance' => $opening,
                'current_balance' => $opening,
            ]);

            if ($opening != 0) {
                PartyLedgerEntry::create([
                    'party_id' => $party->id,
                    'entry_date' => now()->toDateString(),
                    'narration' => 'Opening Balance B/F',
                    'debit' => $opening > 0 ? $opening : 0,
                    'credit' => $opening < 0 ? abs($opening) : 0,
                    'running_balance' => $opening,
                ]);
            }
        });

        return back()->with('success', "Party account '{$validated['name']}' created.");
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
