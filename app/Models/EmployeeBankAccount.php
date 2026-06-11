<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeBankAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'bank_id',
        'account_type_id',
        'account_number',
        'cci',
        'is_primary',
        'status',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'status' => 'boolean',
    ];

    /**
     * Trabajador.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Banco.
     */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    /**
     * Tipo de cuenta.
     */
    public function accountType(): BelongsTo
    {
        return $this->belongsTo(
            Catalog::class,
            'account_type_id'
        );
    }
}
