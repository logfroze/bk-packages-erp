<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocalPartyTransaction extends Model
{
    protected $fillable = [
        'party_id',
        'shop_name',
        'shop_city',
        'transaction_type',
        'item_description',
        'quantity_details',
        'unit_price',
        'total_amount',
        'payment_status',
        'receipt_photo_path',
        'transaction_datetime',
        'recorded_by_name',
        'user_id',
        'custom_fields',
        'custom_grid',
        'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'transaction_datetime' => 'datetime',
        'custom_fields' => 'array',
        'custom_grid' => 'array',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isWeBought(): bool
    {
        return $this->transaction_type === 'WE_BOUGHT';
    }

    public function isTheyBought(): bool
    {
        return $this->transaction_type === 'THEY_BOUGHT';
    }

    public function typeLabel(): string
    {
        return $this->isWeBought() ? 'Factory Bought (Ham Ne Liya)' : 'Shop Took (Unhon Ne Liya)';
    }

    public function typeBadgeClass(): string
    {
        return $this->isWeBought()
            ? 'bg-amber-100 text-amber-800 border border-amber-200'
            : 'bg-emerald-100 text-emerald-800 border border-emerald-200';
    }

    public function paymentBadgeClass(): string
    {
        return match ($this->payment_status) {
            'PAID_CASH' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            'ADDED_TO_KHATA' => 'bg-red-50 text-red-700 border border-red-200',
            'SETTLED' => 'bg-blue-50 text-blue-700 border border-blue-200',
            default => 'bg-slate-100 text-slate-700 border border-slate-200',
        };
    }
}
