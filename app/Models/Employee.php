<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'work_shift_id',

        'employment_status_id',

        'base_salary',

        'pension_system_id',
        'cuspp',

        'photo',
        'signature',

        'status',
    ];

    protected $casts = [
        'birth_date'       => 'date',
        'hire_date'        => 'date',
        'termination_date' => 'date',
        'base_salary'      => 'decimal:2',
        'status'           => 'boolean',
    ];

    /**
     * Nombre completo.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Distrito.
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Turno.
     */
    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }

    /**
     * Cuentas bancarias.
     */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(EmployeeBankAccount::class);
    }

    /**
     * Cuenta bancaria principal.
     */
    public function primaryBankAccount()
    {
        return $this->hasOne(EmployeeBankAccount::class)
            ->where('is_primary', true);
    }
}
