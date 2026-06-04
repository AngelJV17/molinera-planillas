<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Catalog extends Model
{
    protected $fillable = [
        'type',
        'code',
        'name',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Filtra los catálogos por tipo.
     *
     * Ejemplo:
     * Catalog::byType('DOCUMENT_TYPE')->get();
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Filtra únicamente catálogos activos.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    /**
     * Obtiene una opción específica por tipo y código.
     *
     * Ejemplo:
     * Catalog::findByCode('WORKER_STATUS', 'ACTIVE');
     */
    public static function findByCode(string $type, string $code): ?self
    {
        return self::where('type', $type)
            ->where('code', $code)
            ->first();
    }
}
