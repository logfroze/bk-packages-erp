<?php

namespace App\Http\Controllers;

use App\Models\MediaVaultItem;
use App\Models\PettyCashTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class PettyCashController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->get('type', 'ALL');
        $query = PettyCashTransaction::latest('id');

        if ($type !== 'ALL') {
            $query->where('transaction_type', $type);
        }

        $transactions = $query->paginate(25);
        $currentBalance = PettyCashTransaction::currentCashBalance();

        $todayIn = PettyCashTransaction::whereDate('transaction_date', today())
            ->whereIn('transaction_type', ['CASH_IN', 'COUNTER_SALE'])
            ->sum('amount');

        $todayOut = PettyCashTransaction::whereDate('transaction_date', today())
            ->where('transaction_type', 'CASH_OUT')
            ->sum('amount');

        $categories = [
            'TEA_REFRESHMENT' => 'Tea & Refreshments (Chai/Khana)',
            'PETROL_TRANSPORT' => 'Petrol & Fuel (Gaadi/Generator)',
            'HARDWARE_PURCHASE' => 'Hardware & Small Tools',
            'OFFICE_SUPPLIES' => 'Office Supplies & Stationery',
            'SPARE_PARTS' => 'Machine Spare Parts (Chhotay Parts)',
            'CHEMICAL_SALE' => 'Chemical Cash Sale (Spot Sale)',
            'ROLL_SALE' => 'Paper Roll Cash Sale (Minor Sale)',
            'OPENING_FLOAT' => 'Opening Safe Float / Cash Deposit',
            'MISCELLANEOUS' => 'Miscellaneous Small Expense',
        ];

        return view('petty_cash.index', compact(
            'transactions',
            'currentBalance',
            'todayIn',
            'todayOut',
            'type',
            'categories'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'transaction_type' => 'required|in:CASH_IN,CASH_OUT,COUNTER_SALE',
            'category' => 'required|string|max:60',
            'amount' => 'required|numeric|min:1',
            'spent_by_or_paid_to' => 'nullable|string|max:100',
            'description' => 'required|string|max:500',
            'receipt_photo' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:20480',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt_photo')) {
            $file = $request->file('receipt_photo');
            $uploadDir = public_path('uploads/receipts');
            if (! File::isDirectory($uploadDir)) {
                File::makeDirectory($uploadDir, 0777, true, true);
            }
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $fileName = 'petty_'.time().'_'.uniqid().'.'.$ext;
            $file->move($uploadDir, $fileName);
            $receiptPath = '/uploads/receipts/'.$fileName;
        }

        $pct = PettyCashTransaction::record(
            $validated['transaction_type'],
            $validated['category'],
            (float) $validated['amount'],
            $validated['description'],
            $validated['spent_by_or_paid_to'] ?? null,
            null,
            $request->user()?->name ?? 'Mirza Sohail',
            $receiptPath
        );

        // Mirror automatically into Central Media Vault
        if ($receiptPath) {
            $vaultCategory = match ($validated['category']) {
                'TEA_REFRESHMENT' => 'GROCERY_FOOD',
                'PETROL_TRANSPORT' => 'PETROL_FUEL',
                'HARDWARE_PURCHASE', 'SPARE_PARTS' => 'HARDWARE_TOOLS',
                default => 'RECEIPT_VOUCHER',
            };

            MediaVaultItem::create([
                'user_id' => $request->user()?->id,
                'title' => 'Safe Expense: ' . $pct->category . ' (' . $pct->description . ')',
                'category' => $vaultCategory,
                'file_path' => $receiptPath,
                'file_name' => basename($receiptPath),
                'linked_amount' => (float) $pct->amount,
                'linked_party_or_shop' => $pct->spent_by_or_paid_to,
                'source_module' => 'PETTY_CASH',
                'source_id' => $pct->id,
                'notes' => $pct->description,
            ]);
        }

        $actionWord = $validated['transaction_type'] === 'CASH_OUT' ? 'Expense recorded' : 'Cash received';
        return redirect()->route('petty_cash.index')
            ->with('success', "{$actionWord}: Rs. " . number_format($validated['amount']) . " ({$validated['category']}).");
    }

    public function printSheet(Request $request): View
    {
        $date = $request->get('date', today()->toDateString());
        $transactions = PettyCashTransaction::whereDate('transaction_date', $date)->orderBy('id')->get();
        $closingBalance = PettyCashTransaction::currentCashBalance();

        return view('petty_cash.print_sheet', compact('date', 'transactions', 'closingBalance'));
    }
}
