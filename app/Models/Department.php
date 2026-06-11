<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    /**
     * El ID proviene del código INEI.
     */
    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'name',
    ];

    /**
     * Provincias pertenecientes al departamento.
     */
    public function provinces(): HasMany
    {
        return $this->hasMany(Province::class);
    }
}
