<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConversionRun extends Model
{
    protected $fillable = [
        'run_code',
        'run_date',
        'machine_name',
        'client_id',
        'carton_product_id',
        'reel_1_id',
        'reel_1_used_kg',
        'reel_1_rolls_produced',
        'reel_2_id',
        'reel_2_used_kg',
        'reel_2_rolls_produced',
        'total_rolls_produced',
        'rolls_remaining',
        'stage1_loss_kg',
        'rolls_used_for_sheets',
        'expected_sheets',
        'actual_sheets_cut',
        'sheets_remaining',
        'stage2_loss_sheets',
        'sheets_used_for_cartons',
        'sheets_per_carton_rule',
        'expected_cartons',
        'actual_cartons_produced',
        'stage3_loss_cartons',
        'status',
        'notes',
    ];

    protected $casts = [
        'run_date' => 'date',
        'reel_1_used_kg' => 'decimal:2',
        'reel_2_used_kg' => 'decimal:2',
        'stage1_loss_kg' => 'decimal:2',
        'sheets_per_carton_rule' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'client_id');
    }

    public function cartonProduct(): BelongsTo
    {
        return $this->belongsTo(CartonProduct::class);
    }

    public function reel1(): BelongsTo
    {
        return $this->belongsTo(PaperReel::class, 'reel_1_id');
    }

    public function reel2(): BelongsTo
    {
        return $this->belongsTo(PaperReel::class, 'reel_2_id');
    }

    public function sheetCuts(): HasMany
    {
        return $this->hasMany(ConversionSheetCut::class);
    }
}

