<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceExchange extends Model
{
    use HasFactory;

    /**
     * Tipo de catálogo usado para los estados de canje.
     */
    public const CATALOG_TYPE_STATUS = 'ATTENDANCE_EXCHANGE_STATUS';

    /**
     * Códigos esperados en la tabla catalogs para el estado del canje.
     */
    public const STATUS_PENDING   = 'PENDING';
    public const STATUS_APPLIED   = 'APPLIED';
    public const STATUS_CANCELLED = 'CANCELLED';

    /**
     * Campos permitidos para asignación masiva.
     */
    protected $fillable = [
        'employee_id',
        'status_id',
        'absence_attendance_day_id',
        'exchange_attendance_day_id',
        'absence_date',
        'exchange_date',
        'observation',
        'registered_by',
    ];

    /**
     * Conversiones automáticas de tipos.
     */
    protected function casts(): array
    {
        return [
            'absence_date'  => 'date',
            'exchange_date' => 'date',
        ];
    }

    /**
     * Trabajador al que pertenece el canje.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Estado del canje obtenido desde catalogs.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'status_id');
    }

    /**
     * Día donde el trabajador faltó.
     */
    public function absenceDay(): BelongsTo
    {
        return $this->belongsTo(AttendanceDay::class, 'absence_attendance_day_id');
    }

    /**
     * Día trabajado para compensar la falta.
     */
    public function exchangeDay(): BelongsTo
    {
        return $this->belongsTo(AttendanceDay::class, 'exchange_attendance_day_id');
    }

    /**
     * Usuario que registró el canje.
     */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /**
     * Indica si el canje está aplicado.
     */
    public function isApplied(): bool
    {
        return $this->status?->code === self::STATUS_APPLIED;
    }

    /**
     * Indica si el canje fue anulado.
     */
    public function isCancelled(): bool
    {
        return $this->status?->code === self::STATUS_CANCELLED;
    }
}
