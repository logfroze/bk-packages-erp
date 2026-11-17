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
        'paper_grade',
        'deckle_size_inches',
        'initial_weight_kg',
        'remaining_weight_kg',
        'received_date',
        'status',
    ];

    protected $casts = [
        'deckle_size_inches' => 'decimal:1',
        'initial_weight_kg' => 'decimal:2',
        'remaining_weight_kg' => 'decimal:2',
        'received_date' => 'date',
    ];

    public static function availableDeckleSizes(): array
    {
        $sizes = [];
        for ($i = 15.0; $i <= 50.0; $i += 0.5) {
            $sizes[] = number_format($i, 1);
        }
        return $sizes;
    }

    public static function availablePaperGrades(): array
    {
        return [
            'TESTLINER' => 'Testliner (TL)',
            'SUPER_FLUTING' => 'Super Fluting (SF)',
            'CMP_ULTRA' => 'CMP Ultra',
        ];
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'supplier_id');
    }
}
