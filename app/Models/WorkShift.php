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
        'crosses_midnight',
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
        'crosses_midnight'  => 'boolean',
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
}
