<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkShift extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'start_time',
        'end_time',
        'grace_minutes',
        'daily_hours',
        'cross_midnight',
        'status',
    ];

    /**
     * Trabajadores asignados al turno.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
