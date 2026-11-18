<?php

namespace App\Http\Controllers;

use App\Models\MediaVaultItem;
use App\Models\PettyCashTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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
            'transaction_date' => 'nullable|date',
            'receipt_photo' => 'nullable|file|max:20480',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt_photo') && $request->file('receipt_photo')->isValid()) {
            $file = $request->file('receipt_photo');

            // 1. Try local filesystem write first
            try {
                $uploadDir = public_path('uploads/receipts');
                if (! File::isDirectory($uploadDir)) {
                    @File::makeDirectory($uploadDir, 0777, true, true);
                }
                $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                $fileName = 'petty_' . time() . '_' . uniqid() . '.' . $ext;
                if (@$file->move($uploadDir, $fileName)) {
                    $receiptPath = '/uploads/receipts/' . $fileName;
                }
            } catch (\Throwable $e) {
                Log::warning('Disk upload failed, using safe fallback: ' . $e->getMessage());
            }

            // 2. Safe fallback for serverless/read-only hosting (e.g. Vercel)
            if (! $receiptPath) {
                try {
                    $realPath = $file->getRealPath();
                    if ($realPath && file_exists($realPath)) {
                        $mime = $file->getMimeType() ?: 'image/jpeg';
                        $data = file_get_contents($realPath);
                        $receiptPath = 'data:' . $mime . ';base64,' . base64_encode($data);
                    }
                } catch (\Throwable $e) {
                    Log::error('Base64 fallback encoding failed: ' . $e->getMessage());
                }
            }
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

        if (! empty($validated['transaction_date'])) {
            $pct->update(['transaction_date' => $validated['transaction_date']]);
        }

        // Mirror safely into Central Media Vault
        if ($receiptPath) {
            try {
                $vaultCategory = match ($validated['category']) {
                    'TEA_REFRESHMENT' => 'GROCERY_FOOD',
                    'PETROL_TRANSPORT' => 'PETROL_FUEL',
                    'HARDWARE_PURCHASE', 'SPARE_PARTS' => 'HARDWARE_TOOLS',
                    default => 'RECEIPT_VOUCHER',
                };

                $title = mb_substr('Safe Expense: ' . $pct->category . ' (' . $pct->description . ')', 0, 240);
                $fileName = str_starts_with($receiptPath, 'data:') ? 'slip_' . time() . '.jpg' : basename($receiptPath);

                MediaVaultItem::create([
                    'user_id' => $request->user()?->id,
                    'title' => $title,
                    'category' => $vaultCategory,
                    'file_path' => $receiptPath,
                    'file_name' => $fileName,
                    'linked_amount' => (float) $pct->amount,
                    'linked_party_or_shop' => $pct->spent_by_or_paid_to,
                    'source_module' => 'PETTY_CASH',
                    'source_id' => $pct->id,
                    'notes' => $pct->description,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Media vault auto-mirror skipped: ' . $e->getMessage());
            }
        }

        $actionWord = $validated['transaction_type'] === 'CASH_OUT' ? 'Expense recorded' : 'Cash received';
        return redirect()->route('petty_cash.index')
            ->with('success', "{$actionWord}: Rs. " . number_format($validated['amount']) . " ({$validated['category']}).");
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $pct = PettyCashTransaction::findOrFail($id);

        $validated = $request->validate([
            'category' => 'required|string|max:60',
            'amount' => 'required|numeric|min:1',
            'spent_by_or_paid_to' => 'nullable|string|max:100',
            'description' => 'required|string|max:500',
            'transaction_date' => 'required|date',
            'receipt_photo' => 'nullable|file|max:20480',
        ]);

        $receiptPath = $pct->receipt_photo_path;
        if ($request->hasFile('receipt_photo') && $request->file('receipt_photo')->isValid()) {
            $file = $request->file('receipt_photo');

            try {
                $uploadDir = public_path('uploads/receipts');
                if (! File::isDirectory($uploadDir)) {
                    @File::makeDirectory($uploadDir, 0777, true, true);
                }
                $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                $fileName = 'petty_' . time() . '_' . uniqid() . '.' . $ext;
                if (@$file->move($uploadDir, $fileName)) {
                    $receiptPath = '/uploads/receipts/' . $fileName;
                }
            } catch (\Throwable $e) {
                Log::warning('Disk upload failed on update: ' . $e->getMessage());
            }

            if (! $receiptPath || $receiptPath === $pct->receipt_photo_path) {
                try {
                    $realPath = $file->getRealPath();
                    if ($realPath && file_exists($realPath)) {
                        $mime = $file->getMimeType() ?: 'image/jpeg';
                        $data = file_get_contents($realPath);
                        $receiptPath = 'data:' . $mime . ';base64,' . base64_encode($data);
                    }
                } catch (\Throwable $e) {
                    Log::error('Base64 update fallback failed: ' . $e->getMessage());
                }
            }
        }

        $pct->update([
            'category' => $validated['category'],
            'amount' => (float) $validated['amount'],
            'spent_by_or_paid_to' => $validated['spent_by_or_paid_to'] ?? null,
            'description' => $validated['description'],
            'transaction_date' => $validated['transaction_date'],
            'receipt_photo_path' => $receiptPath,
        ]);

        return redirect()->route('petty_cash.index')
            ->with('success', "Petty Cash entry #{$pct->transaction_code} updated successfully.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $pct = PettyCashTransaction::findOrFail($id);
        $code = $pct->transaction_code;

        // Clean up physical file if it exists
        if ($pct->receipt_photo_path && ! str_starts_with($pct->receipt_photo_path, 'data:')) {
            $fullPath = public_path($pct->receipt_photo_path);
            if (File::exists($fullPath)) {
                @unlink($fullPath);
            }
        }

        // Delete mirrored vault item
        try {
            MediaVaultItem::where('source_module', 'PETTY_CASH')->where('source_id', $pct->id)->delete();
        } catch (\Throwable $e) {
            // Ignore
        }

        $pct->delete();

        return redirect()->route('petty_cash.index')
            ->with('success', "Petty Cash transaction [{$code}] deleted successfully.");
    }

    public function printSheet(Request $request): View
    {
        $date = $request->get('date', today()->toDateString());
        $transactions = PettyCashTransaction::whereDate('transaction_date', $date)->orderBy('id')->get();
        $closingBalance = PettyCashTransaction::currentCashBalance();

        return view('petty_cash.print_sheet', compact('date', 'transactions', 'closingBalance'));
    }
}
