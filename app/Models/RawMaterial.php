<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RawMaterial extends Model
{
    protected $fillable = [
        'item_code',
        'name',
        'category',
        'gsm',
        'deckle_size_inches',
        'unit_of_measure',
        'total_stock_qty',
        'minimum_threshold',
        'unit_cost',
        'godown_location',
    ];

    protected $casts = [
        'deckle_size_inches' => 'decimal:2',
        'total_stock_qty' => 'decimal:2',
        'minimum_threshold' => 'decimal:2',
        'unit_cost' => 'decimal:2',
    ];

    public function paperReels(): HasMany
    {
        return $this->hasMany(PaperReel::class);
    }
}
