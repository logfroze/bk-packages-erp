<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    protected $fillable = [
        'voucher_number',
        'voucher_type',
        'voucher_date',
        'party_id',
        'gate_register_id',
        'total_amount',
        'payment_mode',
        'reference_number',
        'description',
        'created_by',
    ];

    protected $casts = [
        'voucher_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function gateRegister(): BelongsTo
    {
        return $this->belongsTo(GateRegister::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(PartyLedgerEntry::class);
    }
}
