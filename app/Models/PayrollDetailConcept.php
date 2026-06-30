<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollDetailConcept extends Model
{
    public const CATALOG_TYPE = 'PAYMENT_CONCEPT_TYPE';
    public const TYPE_INCOME = 'INCOME';
    public const TYPE_DISCOUNT = 'DISCOUNT';
    public const TYPE_EMPLOYER_CONTRIBUTION = 'EMPLOYER_CONTRIBUTION';

    protected $fillable = [
        'payroll_detail_id',
        'concept_type_id',
        'code',
        'name',
        'quantity',
        'rate',
        'amount',
        'taxable',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'rate' => 'decimal:4',
            'amount' => 'decimal:2',
            'taxable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function detail(): BelongsTo
    {
        return $this->belongsTo(PayrollDetail::class, 'payroll_detail_id');
    }

    public function conceptType(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'concept_type_id');
    }
}
