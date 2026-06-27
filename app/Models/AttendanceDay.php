<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AttendanceDay extends Model
{
    use HasFactory;

    /**
     * Tipo de catálogo usado para los estados diarios.
     */
    public const CATALOG_TYPE_STATUS = 'ATTENDANCE_DAY_STATUS';

    /**
     * Códigos esperados en la tabla catalogs para los estados del calendario.
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
        'overtime_hours',
        'observation',
    ];

    /**
     * Conversiones automáticas de tipos.
     */
    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'overtime_hours'  => 'decimal:2',
        ];
    }

    /**
     * Cabecera mensual a la que pertenece el día.
     */
    public function monthlyAttendance(): BelongsTo
    {
        return $this->belongsTo(MonthlyAttendance::class);
    }

    /**
     * Estado del día obtenido desde catalogs.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'status_id');
    }

    /**
     * Canje donde este día funciona como falta compensada.
     */
    public function absenceExchange(): HasOne
    {
        return $this->hasOne(AttendanceExchange::class, 'absence_attendance_day_id');
    }

    /**
     * Canje donde este día funciona como día trabajado para compensar.
     */
    public function workedExchange(): HasOne
    {
        return $this->hasOne(AttendanceExchange::class, 'exchange_attendance_day_id');
    }

    /**
     * Indica si este día está marcado como falta.
     */
    public function isAbsent(): bool
    {
        return $this->status?->code === self::STATUS_ABSENT;
    }

    /**
     * Indica si este día está marcado como trabajado para canje.
     */
    public function isExchangeWorked(): bool
    {
        return $this->status?->code === self::STATUS_EXCHANGE_WORKED;
    }
}
