<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;

/**
 * ==========================================================================
 * MODELO: Bank
 * ==========================================================================
 *
 * Representa las entidades bancarias registradas en el sistema.
 *
 * Ejemplos:
 * - Banco de Crédito del Perú (BCP)
 * - BBVA
 * - Interbank
 * - Scotiabank
 *
 * Esta tabla es utilizada principalmente por:
 *
 * - Trabajadores
 * - Cuentas bancarias
 * - Planillas
 * - CTS
 * - Transferencias
 *
 * Relaciones:
 * Bank
 *  └── EmployeeBankAccount (1:N)
 *
 * @package App\Models
 */
class Bank extends Model
{
    use SoftDeletes;

    /**
     * ----------------------------------------------------------------------
     * Asignación masiva
     * ----------------------------------------------------------------------
     *
     * Campos permitidos para create() y update().
     */
    protected $fillable = [
        'name',
        'code',
        'status',
    ];

    /**
     * ----------------------------------------------------------------------
     * Conversión automática de tipos
     * ----------------------------------------------------------------------
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * ----------------------------------------------------------------------
     * Relación:
     * Banco → Cuentas Bancarias de Trabajadores
     * ----------------------------------------------------------------------
     *
     * Un banco puede estar asociado a múltiples cuentas.
     *
     * Ejemplo:
     *
     * BCP
     * ├── Cuenta Sueldo Juan
     * ├── Cuenta CTS Juan
     * └── Cuenta Sueldo María
     */
    public function employeeBankAccounts(): HasMany
    {
        return $this->hasMany(EmployeeBankAccount::class);
    }

    /**
     * ----------------------------------------------------------------------
     * Scope:
     * Solo registros activos
     * ----------------------------------------------------------------------
     *
     * Uso:
     *
     * Bank::active()->get();
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    /**
     * ----------------------------------------------------------------------
     * Accessor:
     * Estado legible para vistas o reportes
     * ----------------------------------------------------------------------
     *
     * Retorna:
     * - Activo
     * - Inactivo
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status
            ? 'Activo'
            : 'Inactivo';
    }
}
