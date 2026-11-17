<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintedTopSheet extends Model
{
    protected $fillable = [
        'item_code',
        'item_name',
        'carton_product_id',
        'supplier_id',
        'sourcing_city',
        'stock_pieces',
        'minimum_threshold',
        'unit_cost',
        'location',
        'notes',
    ];

    public function cartonProduct(): BelongsTo
    {
        return $this->belongsTo(CartonProduct::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'supplier_id');
    }
}
