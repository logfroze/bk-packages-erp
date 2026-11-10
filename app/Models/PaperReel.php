<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaperReel extends Model
{
    protected $fillable = [
        'reel_number',
        'raw_material_id',
        'supplier_id',
        'initial_weight_kg',
        'remaining_weight_kg',
        'received_date',
        'status',
    ];

    protected $casts = [
        'initial_weight_kg' => 'decimal:2',
        'remaining_weight_kg' => 'decimal:2',
        'received_date' => 'date',
    ];

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'supplier_id');
    }
}
