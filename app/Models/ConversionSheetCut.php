<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversionSheetCut extends Model
{
    protected $fillable = [
        'conversion_run_id',
        'rolls_consumed',
        'sheet_size_label',
        'cut_length_inches',
        'cut_width_inches',
        'sheets_per_roll_formula',
        'expected_sheets',
        'actual_sheets_cut',
        'waste_sheets',
    ];

    protected $casts = [
        'cut_length_inches' => 'decimal:2',
        'cut_width_inches' => 'decimal:2',
        'sheets_per_roll_formula' => 'decimal:2',
    ];

    public function conversionRun(): BelongsTo
    {
        return $this->belongsTo(ConversionRun::class);
    }
}

