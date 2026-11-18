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
        'supplier_name',
        'sourcing_city',
        'stock_pieces',
        'minimum_threshold',
        'unit_cost',
        'location',
        'received_date',
        'notes',
        'custom_fields',
        'custom_grid',
    ];

    protected $casts = [
        'received_date' => 'date',
        'custom_fields' => 'array',
        'custom_grid' => 'array',
        'unit_cost' => 'decimal:2',
        'stock_pieces' => 'integer',
        'minimum_threshold' => 'integer',
    ];

    public function cartonProduct(): BelongsTo
    {
        return $this->belongsTo(CartonProduct::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'supplier_id');
    }

    public function getEffectiveSupplierNameAttribute(): string
    {
        if (!empty($this->supplier_name)) {
            return $this->supplier_name;
        }
        if ($this->supplier) {
            return $this->supplier->name;
        }
        return 'Custom Press';
    }
}
