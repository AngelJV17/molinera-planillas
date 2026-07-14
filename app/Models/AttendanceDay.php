<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AttendanceDay extends Model
{
    /**
     * Tipo de catálogo usado para los estados diarios.
     */
    public const CATALOG_TYPE_STATUS = 'ATTENDANCE_DAY_STATUS';

    /**
     * Estados diarios permitidos.
     */
    public const STATUS_UNMARKED        = 'UNMARKED';
    public const STATUS_PRESENT         = 'PRESENT';
    public const STATUS_ABSENT          = 'ABSENT';
    public const STATUS_EXCHANGE_WORKED = 'EXCHANGE_WORKED';
    public const STATUS_REST            = 'REST';

    /**
     * Campos permitidos para asignación masiva.
     */
    protected $fillable = [
        'monthly_attendance_id',
        'status_id',
        'attendance_date',
        'work_shift_id',
        'entry_time',
        'exit_time',
        'worked_hours',
        'overtime_hours',
        'payable_overtime_hours',
        'observation',
    ];

    /**
     * Conversión automática de tipos.
     */
    protected $casts = [
        'attendance_date' => 'date',
        'worked_hours'    => 'decimal:2',
        'overtime_hours'  => 'decimal:2',
        'payable_overtime_hours' => 'decimal:2',
    ];

    /**
     * Asistencia mensual a la que pertenece este día.
     */
    public function monthlyAttendance(): BelongsTo
    {
        return $this->belongsTo(MonthlyAttendance::class);
    }

    /**
     * Estado diario del calendario.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'status_id');
    }

    /**
     * Turno usado para calcular horas trabajadas y horas extras.
     */
    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }

    /**
     * Canje donde este día representa la falta.
     */
    public function absenceExchange(): HasOne
    {
        return $this->hasOne(AttendanceExchange::class, 'absence_attendance_day_id');
    }

    /**
     * Canje donde este día representa el día trabajado para compensar.
     */
    public function workedExchange(): HasOne
    {
        return $this->hasOne(AttendanceExchange::class, 'exchange_attendance_day_id');
    }

    /**
     * Indica si el día está marcado como falta.
     */
    public function isAbsent(): bool
    {
        return $this->status?->code === self::STATUS_ABSENT;
    }

    /**
     * Indica si el día está marcado como trabajo por canje.
     */
    public function isExchangeWorked(): bool
    {
        return $this->status?->code === self::STATUS_EXCHANGE_WORKED;
    }

    /**
     * Indica si el día requiere hora de ingreso y salida.
     */
    public function requiresWorkingHours(): bool
    {
        return in_array($this->status?->code, [
            self::STATUS_PRESENT,
            self::STATUS_EXCHANGE_WORKED,
        ], true);
    }
}
