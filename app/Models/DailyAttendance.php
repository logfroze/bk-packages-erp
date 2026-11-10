<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyAttendance extends Model
{
    protected $table = 'daily_attendance';

    protected $fillable = [
        'worker_id',
        'attendance_date',
        'status',
        'overtime_hours',
        'pieces_completed',
        'daily_calculated_wage',
        'recorded_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'overtime_hours' => 'decimal:2',
        'daily_calculated_wage' => 'decimal:2',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}
