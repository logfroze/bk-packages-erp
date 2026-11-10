<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CartonProduct extends Model
{
    protected $fillable = [
        'sku',
        'client_id',
        'box_title',
        'ply_type',
        'flute_type',
        'length_mm',
        'width_mm',
        'height_mm',
        'joints_type',
        'printing_colors',
        'bundle_pack_size',
        'unit_selling_price',
        'stock_pieces',
        'godown_shed',
    ];

    protected $casts = [
        'length_mm' => 'decimal:2',
        'width_mm' => 'decimal:2',
        'height_mm' => 'decimal:2',
        'unit_selling_price' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'client_id');
    }

    public function productionBatches(): HasMany
    {
        return $this->hasMany(ProductionBatch::class);
    }
}
