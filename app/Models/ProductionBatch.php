<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionBatch extends Model
{
    protected $fillable = [
        'batch_number',
        'carton_product_id',
        'client_id',
        'shift',
        'target_quantity',
        'produced_quantity',
        'rejected_waste_pieces',
        'current_machine',
        'status',
        'due_date',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function cartonProduct(): BelongsTo
    {
        return $this->belongsTo(CartonProduct::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'client_id');
    }
}
