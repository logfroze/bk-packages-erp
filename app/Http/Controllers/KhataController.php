<?php

namespace App\Http\Controllers;

use App\Models\CustomErpCard;
use App\Models\LocalPartyTransaction;
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

        // Consolidated single query for party balance aggregates (cached 20s)
        $balanceStats = Cache::remember('khata_balance_stats', 20, function () {
            return Party::selectRaw("
                COALESCE(SUM(CASE WHEN current_balance > 0 THEN current_balance ELSE 0 END), 0) as receivables,
                COALESCE(SUM(CASE WHEN current_balance < 0 THEN ABS(current_balance) ELSE 0 END), 0) as payables,
                COALESCE(SUM(CASE WHEN classification = 'LOCAL_VENDOR_SHOP' AND current_balance < 0 THEN ABS(current_balance) ELSE 0 END), 0) as local_payables
            ")->first();
        });

        $totalReceivables = (float) ($balanceStats->receivables ?? 0);
        $totalPayables = (float) ($balanceStats->payables ?? 0);
        $localVendorPayables = (float) ($balanceStats->local_payables ?? 0);

        // Consolidated single query for cash in hand (cached 20s)
        $cashStats = Cache::remember('khata_cash_stats', 20, function () {
            return Voucher::where('payment_mode', 'CASH')->selectRaw("
                COALESCE(SUM(CASE WHEN voucher_type = 'RECEIPT_VOUCHER' THEN total_amount ELSE 0 END), 0) as receipts,
                COALESCE(SUM(CASE WHEN voucher_type IN ('PAYMENT_VOUCHER', 'EXPENSE_VOUCHER') THEN total_amount ELSE 0 END), 0) as payments
            ")->first();
        });

        $cashReceipts = (float) ($cashStats->receipts ?? 0);
        $cashPayments = (float) ($cashStats->payments ?? 0);
        $cashInHand = max(0, $cashReceipts - $cashPayments);

        // Local Party & Small Shop Barter / Expense Transactions
        $localTransactions = LocalPartyTransaction::with('party', 'user')
            ->latest('transaction_datetime')
            ->latest('id')
            ->take(30)
            ->get();

        // Consolidated single query for barter totals (cached 20s)
        $localBarterStats = Cache::remember('khata_local_barter_stats', 20, function () {
            return LocalPartyTransaction::selectRaw("
                COALESCE(SUM(CASE WHEN transaction_type = 'WE_BOUGHT' THEN total_amount ELSE 0 END), 0) as we_bought,
                COALESCE(SUM(CASE WHEN transaction_type = 'THEY_BOUGHT' THEN total_amount ELSE 0 END), 0) as they_bought
            ")->first();
        });

        $totalWeBought = (float) ($localBarterStats->we_bought ?? 0);
        $totalTheyBought = (float) ($localBarterStats->they_bought ?? 0);
        $netLocalBalance = $totalTheyBought - $totalWeBought;

        $localCustomCards = CustomErpCard::forPage('khata_local_party');
        $localShopList = Cache::remember('khata_local_shops', 60, fn() => Party::where('classification', 'LOCAL_VENDOR_SHOP')->orWhere('type', 'SUPPLIER')->orderBy('name')->get(['id', 'name', 'city']));

        return view('khata.index', compact(
            'parties',
            'type',
            'classification',
            'search',
            'totalReceivables',
            'totalPayables',
            'localVendorPayables',
            'cashInHand',
            'localTransactions',
            'totalWeBought',
            'totalTheyBought',
            'netLocalBalance',
            'localCustomCards',
            'localShopList'
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

        $this->clearKhataCaches();

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

        $this->clearKhataCaches();

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

        $this->clearKhataCaches();

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
            'receipt_photo' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:20480',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt_photo')) {
            $receiptPath = $this->handleReceiptUpload($request->file('receipt_photo'), 'voucher_');
        }

        DB::transaction(function () use ($validated, $receiptPath) {
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
                'receipt_photo_path' => $receiptPath,
            ]);

            $party = null;
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

            if ($receiptPath) {
                $this->mirrorToVault(
                    $receiptPath,
                    "Voucher {$voucherNumber}: " . ($party ? $party->name : $validated['description']),
                    'RECEIPT_VOUCHER',
                    (float) $validated['total_amount'],
                    $party ? $party->name : null,
                    'VOUCHER',
                    $voucher->id,
                    $validated['description']
                );
            }
        });

        $this->clearKhataCaches();

        return back()->with('success', 'Voucher recorded & party ledger updated.');
    }

    public function updateVoucher(Request $request, Voucher $voucher): RedirectResponse
    {
        $validated = $request->validate([
            'voucher_type' => 'required|in:RECEIPT_VOUCHER,PAYMENT_VOUCHER,SALES_INVOICE,PURCHASE_BILL,EXPENSE_VOUCHER',
            'party_id' => 'nullable|exists:parties,id',
            'total_amount' => 'required|numeric|min:1',
            'payment_mode' => 'required|in:CASH,CHEQUE,ONLINE_BANK,CREDIT',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'required|string|max:500',
            'voucher_date' => 'nullable|date',
            'receipt_photo' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:20480',
        ]);

        $receiptPath = $voucher->receipt_photo_path;
        if ($request->hasFile('receipt_photo')) {
            $newPath = $this->handleReceiptUpload($request->file('receipt_photo'), 'voucher_edit_');
            if ($newPath) {
                $receiptPath = $newPath;
            }
        }

        DB::transaction(function () use ($validated, $voucher, $receiptPath) {
            $oldPartyId = $voucher->party_id;
            $oldAmount = (float) $voucher->total_amount;
            $oldType = $voucher->voucher_type;

            // Revert old effect if there was a party ledger entry
            if ($oldPartyId) {
                $oldParty = Party::lockForUpdate()->find($oldPartyId);
                if ($oldParty) {
                    $wasDebit = in_array($oldType, ['SALES_INVOICE', 'PAYMENT_VOUCHER']);
                    $revertedBalance = $oldParty->current_balance - ($wasDebit ? $oldAmount : -$oldAmount);
                    $oldParty->update(['current_balance' => $revertedBalance]);
                }
                PartyLedgerEntry::where('voucher_id', $voucher->id)->delete();
            }

            $voucher->update([
                'voucher_type' => $validated['voucher_type'],
                'party_id' => $validated['party_id'] ?? null,
                'total_amount' => $validated['total_amount'],
                'payment_mode' => $validated['payment_mode'],
                'reference_number' => $validated['reference_number'] ?? null,
                'description' => $validated['description'],
                'voucher_date' => $validated['voucher_date'] ?? $voucher->voucher_date,
                'receipt_photo_path' => $receiptPath,
            ]);

            // Apply new effect if linked to a party
            if (!empty($validated['party_id'])) {
                $party = Party::lockForUpdate()->findOrFail($validated['party_id']);
                $newAmount = (float) $validated['total_amount'];
                $isDebit = in_array($validated['voucher_type'], ['SALES_INVOICE', 'PAYMENT_VOUCHER']);
                $debit = $isDebit ? $newAmount : 0.00;
                $credit = $isDebit ? 0.00 : $newAmount;
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

        $this->clearKhataCaches();

        return back()->with('success', "Voucher {$voucher->voucher_number} updated & party ledger adjusted.");
    }

    public function destroyVoucher(Voucher $voucher): RedirectResponse
    {
        DB::transaction(function () use ($voucher) {
            if ($voucher->party_id) {
                $party = Party::lockForUpdate()->find($voucher->party_id);
                if ($party) {
                    $wasDebit = in_array($voucher->voucher_type, ['SALES_INVOICE', 'PAYMENT_VOUCHER']);
                    $revertedBalance = $party->current_balance - ($wasDebit ? $voucher->total_amount : -$voucher->total_amount);
                    $party->update(['current_balance' => $revertedBalance]);
                }
                PartyLedgerEntry::where('voucher_id', $voucher->id)->delete();
            }

            if ($voucher->receipt_photo_path && !str_starts_with($voucher->receipt_photo_path, 'data:') && File::exists(public_path($voucher->receipt_photo_path))) {
                @unlink(public_path($voucher->receipt_photo_path));
            }

            $voucher->delete();
        });

        $this->clearKhataCaches();

        return back()->with('success', 'Voucher deleted and party balance reversed.');
    }

    public function storeLocalTransaction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shop_name' => 'required|string|max:150',
            'shop_city' => 'nullable|string|max:100',
            'party_id' => 'nullable|exists:parties,id',
            'transaction_type' => 'required|in:WE_BOUGHT,THEY_BOUGHT',
            'item_description' => 'required|string|max:255',
            'quantity_details' => 'nullable|string|max:100',
            'unit_price' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'payment_status' => 'required|in:PAID_CASH,ADDED_TO_KHATA,SETTLED,PARTIAL',
            'transaction_datetime' => 'nullable|date',
            'receipt_photo' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:20480',
            'notes' => 'nullable|string|max:1000',
            'custom_fields' => 'nullable|array',
            'custom_grid' => 'nullable|array',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt_photo')) {
            $receiptPath = $this->handleReceiptUpload($request->file('receipt_photo'), 'local_');
        }

        $tx = LocalPartyTransaction::create([
            'party_id' => $validated['party_id'] ?? null,
            'shop_name' => trim($validated['shop_name']),
            'shop_city' => $validated['shop_city'] ? trim($validated['shop_city']) : 'Okara',
            'transaction_type' => $validated['transaction_type'],
            'item_description' => trim($validated['item_description']),
            'quantity_details' => $validated['quantity_details'] ?? null,
            'unit_price' => (float) ($validated['unit_price'] ?? 0),
            'total_amount' => (float) $validated['total_amount'],
            'payment_status' => $validated['payment_status'],
            'receipt_photo_path' => $receiptPath,
            'transaction_datetime' => $validated['transaction_datetime'] ?? now(),
            'recorded_by_name' => Auth::user()?->name ?? 'Staff',
            'user_id' => Auth::id(),
            'custom_fields' => $validated['custom_fields'] ?? null,
            'custom_grid' => $validated['custom_grid'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Automatically feed into the unified Factory Media Vault
        if ($receiptPath) {
            $this->mirrorToVault(
                $receiptPath,
                'Local Shop: ' . $tx->shop_name . ' — ' . $tx->item_description,
                'LOCAL_PARTY_BILL',
                (float) $tx->total_amount,
                $tx->shop_name,
                'LOCAL_PARTY',
                $tx->id,
                $tx->notes
            );
        }

        $this->clearKhataCaches();

        return redirect()->route('khata.index')->with('success', "Local party entry for '{$tx->shop_name}' saved successfully!");
    }

    public function updateLocalTransaction(Request $request, $id): RedirectResponse
    {
        $tx = LocalPartyTransaction::findOrFail($id);
        $validated = $request->validate([
            'shop_name' => 'required|string|max:150',
            'transaction_type' => 'required|in:WE_BOUGHT,THEY_BOUGHT',
            'item_description' => 'required|string|max:255',
            'quantity_details' => 'nullable|string|max:100',
            'unit_price' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'payment_status' => 'required|in:PAID_CASH,ADDED_TO_KHATA,SETTLED,PARTIAL',
            'notes' => 'nullable|string',
            'custom_fields' => 'nullable|array',
            'custom_grid' => 'nullable|array',
            'receipt_photo' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:20480',
        ]);

        $receiptPath = $tx->receipt_photo_path;
        if ($request->hasFile('receipt_photo')) {
            $newPath = $this->handleReceiptUpload($request->file('receipt_photo'), 'local_edit_');
            if ($newPath) {
                $receiptPath = $newPath;
            }
        }

        $tx->update([
            'shop_name' => trim($validated['shop_name']),
            'transaction_type' => $validated['transaction_type'],
            'item_description' => trim($validated['item_description']),
            'quantity_details' => $validated['quantity_details'] ?? null,
            'unit_price' => (float) ($validated['unit_price'] ?? 0),
            'total_amount' => (float) $validated['total_amount'],
            'payment_status' => $validated['payment_status'],
            'notes' => $validated['notes'] ?? null,
            'receipt_photo_path' => $receiptPath,
            'custom_fields' => $validated['custom_fields'] ?? $tx->custom_fields,
            'custom_grid' => $validated['custom_grid'] ?? $tx->custom_grid,
        ]);

        $this->clearKhataCaches();

        return redirect()->route('khata.index')->with('success', "Local party entry #{$tx->id} updated.");
    }

    public function destroyLocalTransaction($id): RedirectResponse
    {
        $tx = LocalPartyTransaction::findOrFail($id);
        if ($tx->receipt_photo_path && !str_starts_with($tx->receipt_photo_path, 'data:') && File::exists(public_path($tx->receipt_photo_path))) {
            @unlink(public_path($tx->receipt_photo_path));
        }
        $tx->delete();

        $this->clearKhataCaches();

        return redirect()->route('khata.index')->with('success', 'Local party transaction deleted.');
    }

    private function handleReceiptUpload(?\Illuminate\Http\UploadedFile $file, string $prefix = 'voucher_'): ?string
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

    private function mirrorToVault(?string $path, string $title, string $category, float $amount, ?string $partyName, string $sourceModule, int $sourceId, ?string $notes = null): void
    {
        if (!$path) return;
        try {
            MediaVaultItem::create([
                'user_id' => Auth::id(),
                'title' => substr($title, 0, 240),
                'category' => $category,
                'file_path' => $path,
                'file_name' => str_starts_with($path, 'data:') ? 'camera_capture.jpg' : basename($path),
                'linked_amount' => $amount,
                'linked_party_or_shop' => $partyName ? substr($partyName, 0, 150) : null,
                'source_module' => $sourceModule,
                'source_id' => $sourceId,
                'notes' => $notes ? substr($notes, 0, 1000) : null,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Media vault mirror failed in Khata: ' . $e->getMessage());
        }
    }

    private function clearKhataCaches(): void
    {
        Cache::forget('khata_balance_stats');
        Cache::forget('khata_cash_stats');
        Cache::forget('khata_local_barter_stats');
        Cache::forget('khata_local_shops');
        Cache::forget('dashboard_numerical_stats_' . today()->toDateString());
    }
}
