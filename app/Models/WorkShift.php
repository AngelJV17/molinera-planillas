<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkShift extends Model
{
    use SoftDeletes;

    /**
     * Campos permitidos para asignación masiva.
     */
    protected $fillable = [
        'name',
        'description',
        'start_time',
        'break_start_time',
        'break_end_time',
        'end_time',
        'tolerance_minutes',
        'daily_hours',
        'uses_daily_rules',
        'crosses_midnight',
        'rotation_enabled',
        'rotation_work_days',
        'rotation_rest_days',
        'rotation_start_date',
        'status',
    ];

    /**
     * Conversión automática de tipos.
     */
    protected $casts = [
        'start_time'        => 'datetime:H:i',
        'break_start_time'  => 'datetime:H:i',
        'break_end_time'    => 'datetime:H:i',
        'end_time'          => 'datetime:H:i',
        'tolerance_minutes' => 'integer',
        'daily_hours'       => 'decimal:2',
        'uses_daily_rules'  => 'boolean',
        'crosses_midnight'  => 'boolean',
        'rotation_enabled'  => 'boolean',
        'rotation_work_days' => 'integer',
        'rotation_rest_days' => 'integer',
        'rotation_start_date' => 'date',
        'status'            => 'boolean',
    ];

    /**
     * Scope para obtener únicamente turnos activos.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    /**
     * Relación: un turno puede estar asignado a varios trabajadores.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(WorkShiftRule::class);
    }
}
