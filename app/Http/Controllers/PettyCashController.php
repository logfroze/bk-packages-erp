<?php

namespace App\Http\Controllers;

use App\Models\PettyCashTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ]);

        PettyCashTransaction::record(
            $validated['transaction_type'],
            $validated['category'],
            (float) $validated['amount'],
            $validated['description'],
            $validated['spent_by_or_paid_to'] ?? null,
            null,
            $request->user()?->name ?? 'Mirza Sohail'
        );

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
