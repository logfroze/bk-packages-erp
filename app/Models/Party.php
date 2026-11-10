<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Party extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'contact_person',
        'phone',
        'address',
        'city',
        'ntn_strn',
        'opening_balance',
        'current_balance',
        'credit_limit',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(PartyLedgerEntry::class)->orderBy('entry_date', 'desc')->orderBy('id', 'desc');
    }

    public function gateRegisters(): HasMany
    {
        return $this->hasMany(GateRegister::class);
    }

    public function cartons(): HasMany
    {
        return $this->hasMany(CartonProduct::class, 'client_id');
    }
}
