<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrugationRun extends Model
{
    protected $fillable = [
        'run_code',
        'board_type',
        'flute_ply_count',
        'plain_paper_reel_1_id',
        'fluting_wavy_reel_1_id',
        'plain_paper_reel_2_id',
        'fluting_wavy_reel_2_id',
        'top_plain_paper_reel_id',
        'deckle_size_inches',
        'total_paper_weight_kg',
        'waste_trim_kg',
        'nalis_produced',
        'completed_sheets_produced',
        'sheet_size',
        'operator_name',
        'shift',
        'status',
        'notes',
        'custom_fields',
        'custom_grid',
    ];

    protected $casts = [
        'custom_fields' => 'array',
        'custom_grid' => 'array',
        'deckle_size_inches' => 'decimal:1',
        'total_paper_weight_kg' => 'decimal:2',
        'waste_trim_kg' => 'decimal:2',
    ];

    public function plainPaperReel1(): BelongsTo
    {
        return $this->belongsTo(PaperReel::class, 'plain_paper_reel_1_id');
    }

    public function flutingWavyReel1(): BelongsTo
    {
        return $this->belongsTo(PaperReel::class, 'fluting_wavy_reel_1_id');
    }

    public function plainPaperReel2(): BelongsTo
    {
        return $this->belongsTo(PaperReel::class, 'plain_paper_reel_2_id');
    }

    public function flutingWavyReel2(): BelongsTo
    {
        return $this->belongsTo(PaperReel::class, 'fluting_wavy_reel_2_id');
    }

    public function topPlainPaperReel(): BelongsTo
    {
        return $this->belongsTo(PaperReel::class, 'top_plain_paper_reel_id');
    }

    public function boardTypeLabel(): string
    {
        return match ($this->board_type) {
            'SINGLE_FACE' => 'Single Face (1 Nali / 2-Ply)',
            'SINGLE_WALL' => 'Single Wall (1 Nali + 1 Plain / 3-Ply)',
            'DOUBLE_WALL' => 'Double Wall (2 Nalis + 1 Plain / 5-Ply)',
            'TRIPLE_WALL' => 'Triple Wall (3 Nalis + 1 Plain / 7-Ply)',
            default => str_replace('_', ' ', $this->board_type),
        };
    }

    public function boardTypeBadgeColor(): string
    {
        return match ($this->board_type) {
            'SINGLE_FACE' => 'bg-amber-100 text-amber-800 border-amber-200',
            'SINGLE_WALL' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'DOUBLE_WALL' => 'bg-blue-100 text-blue-800 border-blue-200',
            'TRIPLE_WALL' => 'bg-purple-100 text-purple-800 border-purple-200',
            default => 'bg-slate-100 text-slate-800 border-slate-200',
        };
    }
}
