<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PettyCashTransaction extends Model
{
    protected $fillable = [
        'transaction_code',
        'transaction_type',
        'category',
        'amount',
        'running_cash_balance',
        'spent_by_or_paid_to',
        'description',
        'receipt_voucher_id',
        'transaction_date',
        'recorded_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'running_cash_balance' => 'decimal:2',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'receipt_voucher_id');
    }

    public static function currentCashBalance(): float
    {
        $last = self::latest('id')->first();
        return $last ? (float) $last->running_cash_balance : 0.00;
    }

    public static function record(
        string $type,
        string $category,
        float $amount,
        string $description,
        ?string $spentByOrPaidTo = null,
        ?int $voucherId = null,
        ?string $recordedBy = null
    ): self {
        $current = self::currentCashBalance();
        $newBalance = $type === 'CASH_IN' || $type === 'COUNTER_SALE'
            ? $current + $amount
            : max(0.00, $current - $amount);

        $count = self::count() + 1;
        $code = 'PC-' . date('Ymd') . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);

        return self::create([
            'transaction_code' => $code,
            'transaction_type' => $type,
            'category' => $category,
            'amount' => $amount,
            'running_cash_balance' => $newBalance,
            'spent_by_or_paid_to' => $spentByOrPaidTo,
            'description' => $description,
            'receipt_voucher_id' => $voucherId,
            'transaction_date' => now()->toDateString(),
            'recorded_by' => $recordedBy ?? auth()->user()?->name ?? 'Mirza Sohail',
        ]);
    }
}
