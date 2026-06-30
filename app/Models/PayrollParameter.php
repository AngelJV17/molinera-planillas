<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PayrollParameter extends Model
{
    protected $fillable = [
        'code',
        'name',
        'value',
        'description',
        'effective_from',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'effective_from' => 'date',
            'status' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }
}
