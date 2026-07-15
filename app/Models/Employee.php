<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_code',
        'document_type_id',
        'document_number',
        'first_name',
        'last_name',
        'birth_date',
        'gender_id',
        'marital_status_id',
        'phone',
        'email',
        'address',
        'district_id',
        'hire_date',
        'termination_date',
        'position_id',
        'work_area_id',
        'payroll_group_id',
        'work_shift_id',
        'employment_status_id',
        'base_salary',
        'pension_system_id',
        'cuspp',
        'photo',
        'signature',
        'status',
        'user_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'base_salary' => 'decimal:2',
        'status' => 'boolean',
    ];

    /**
     * Filtra únicamente trabajadores activos.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    /**
     * Nombre completo del trabajador.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Tipo de documento.
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'document_type_id');
    }

    /**
     * Género.
     */
    public function gender(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'gender_id');
    }

    /**
     * Estado civil.
     */
    public function maritalStatus(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'marital_status_id');
    }

    /**
     * Cargo laboral.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'position_id');
    }

    /**
     * Área de trabajo.
     */
    public function workArea(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'work_area_id');
    }

    /**
     * Grupo usado para generar planillas.
     */
    public function payrollGroup(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'payroll_group_id');
    }

    /**
     * Estado laboral.
     */
    public function employmentStatus(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'employment_status_id');
    }

    /**
     * Sistema pensionario.
     */
    public function pensionSystem(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'pension_system_id');
    }

    /**
     * Distrito de residencia.
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Turno laboral asignado.
     */
    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }

    /**
     * Cuentas bancarias del trabajador.
     */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(EmployeeBankAccount::class);
    }

    /**
     * Cuenta bancaria principal.
     */
    public function primaryBankAccount(): HasOne
    {
        return $this->hasOne(EmployeeBankAccount::class)
            ->where('is_primary', true);
    }

    /**
     * Usuario vinculado al trabajador.
     *
     * Esta relación es opcional:
     * - Un trabajador puede no tener acceso al sistema.
     * - Un trabajador administrativo puede tener usuario.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Asistencias mensuales registradas para el trabajador.
     */
    public function monthlyAttendances(): HasMany
    {
        return $this->hasMany(MonthlyAttendance::class);
    }

    /**
     * Detalles de planilla calculados para el trabajador.
     */
    public function payrollDetails(): HasMany
    {
        return $this->hasMany(PayrollDetail::class);
    }

    /**
     * Canjes de asistencia registrados para el trabajador.
     */
    public function attendanceExchanges(): HasMany
    {
        return $this->hasMany(AttendanceExchange::class);
    }
}
