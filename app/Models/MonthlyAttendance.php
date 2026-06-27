<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyAttendance extends Model
{
    use HasFactory;

    /**
     * Tipo de catálogo usado para los estados de asistencia mensual.
     */
    public const CATALOG_TYPE_STATUS = 'ATTENDANCE_MONTHLY_STATUS';

    /**
     * Códigos esperados en la tabla catalogs para el estado mensual.
     */
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_CLOSED = 'CLOSED';

    /**
     * Campos permitidos para asignación masiva.
     */
    protected $fillable = [
        'employee_id',
        'status_id',
        'month',
        'year',
        'worked_days',
        'absence_days',
        'compensated_absence_days',
        'uncompensated_absence_days',
        'exchange_days',
        'rest_days',
        'overtime_hours',
        'observations',
        'created_by',
        'closed_by',
        'closed_at',
    ];

    /**
     * Conversiones automáticas de tipos.
     */
    protected function casts(): array
    {
        return [
            'month'                      => 'integer',
            'year'                       => 'integer',
            'worked_days'                => 'integer',
            'absence_days'               => 'integer',
            'compensated_absence_days'   => 'integer',
            'uncompensated_absence_days' => 'integer',
            'exchange_days'              => 'integer',
            'rest_days'                  => 'integer',
            'overtime_hours'             => 'decimal:2',
            'closed_at'                  => 'datetime',
        ];
    }

     /**
     * Trabajador al que pertenece la asistencia mensual.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Estado de la asistencia mensual obtenido desde catalogs.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'status_id');
    }

    /**
     * Días registrados dentro del calendario mensual.
     */
    public function days(): HasMany
    {
        return $this->hasMany(AttendanceDay::class);
    }

    /**
     * Usuario que creó la asistencia mensual.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Usuario que cerró la asistencia mensual.
     */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Indica si la asistencia mensual todavía puede editarse.
     */
    public function isEditable(): bool
    {
        return $this->status?->code === self::STATUS_DRAFT;
    }

    /**
     * Indica si la asistencia mensual ya fue cerrada.
     */
    public function isClosed(): bool
    {
        return $this->status?->code === self::STATUS_CLOSED;
    }
}
