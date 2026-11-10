<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Worker extends Model
{
    protected $fillable = [
        'worker_code',
        'full_name',
        'father_name',
        'cnic',
        'phone',
        'role',
        'wage_type',
        'rate_amount',
        'is_active',
        'joined_date',
    ];

    protected $casts = [
        'rate_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'joined_date' => 'date',
    ];

    public function attendances(): HasMany
    {
        return $this->hasMany(DailyAttendance::class);
    }

    public function advances(): HasMany
    {
        return $this->hasMany(WorkerAdvance::class);
    }
}
