<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class District extends Model
{
    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'province_id',
        'name',
    ];

    /**
     * Provincia a la que pertenece.
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * Obtener el departamento a través de la provincia.
     */
    public function department()
    {
        return $this->province?->department();
    }
}
