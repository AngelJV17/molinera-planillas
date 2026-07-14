<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkShiftRule extends Model
{
    protected $fillable = [
        'work_shift_id',
        'day_of_week',
        'is_working_day',
        'start_time',
        'break_start_time',
        'break_end_time',
        'end_time',
        'tolerance_minutes',
        'expected_hours',
        'crosses_midnight',
        'counts_as_full_day',
        'overtime_pay_enabled',
        'overtime_after_hours',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_working_day' => 'boolean',
        'start_time' => 'datetime:H:i',
        'break_start_time' => 'datetime:H:i',
        'break_end_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'tolerance_minutes' => 'integer',
        'expected_hours' => 'decimal:2',
        'crosses_midnight' => 'boolean',
        'counts_as_full_day' => 'boolean',
        'overtime_pay_enabled' => 'boolean',
        'overtime_after_hours' => 'decimal:2',
    ];

    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }
}
