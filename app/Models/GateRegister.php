<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GateRegister extends Model
{
    protected $fillable = [
        'gate_pass_number',
        'movement_type',
        'vehicle_number',
        'vehicle_type',
        'driver_name',
        'driver_phone',
        'party_id',
        'bilti_challan_number',
        'material_summary',
        'gross_weight_kg',
        'tare_weight_kg',
        'net_weight_kg',
        'total_bundles',
        'total_pieces',
        'remarks',
        'status',
        'time_in',
        'time_out',
        'security_officer',
    ];

    protected $casts = [
        'gross_weight_kg' => 'decimal:2',
        'tare_weight_kg' => 'decimal:2',
        'net_weight_kg' => 'decimal:2',
        'time_in' => 'datetime',
        'time_out' => 'datetime',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
