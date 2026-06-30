<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    public const CATALOG_TYPE_STATUS = 'PAYROLL_STATUS';
    public const STATUS_IN_REVIEW = 'IN_REVIEW';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_PAID = 'PAID';

    protected $fillable = [
        'code',
        'status_id',
        'month',
        'year',
        'payment_date',
        'employee_count',
        'total_base_salary',
        'total_income',
        'total_discount',
        'total_employer_contribution',
        'total_net',
        'observations',
        'rejection_reason',
        'generated_by',
        'reviewed_by',
        'paid_by',
        'reviewed_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'payment_date' => 'date',
            'employee_count' => 'integer',
            'total_base_salary' => 'decimal:2',
            'total_income' => 'decimal:2',
            'total_discount' => 'decimal:2',
            'total_employer_contribution' => 'decimal:2',
            'total_net' => 'decimal:2',
            'reviewed_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'status_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PayrollDetail::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function scopePeriod(Builder $query, int $month, int $year): Builder
    {
        return $query->where('month', $month)->where('year', $year);
    }

    public function isInReview(): bool
    {
        return $this->status?->code === self::STATUS_IN_REVIEW;
    }

    public function isApproved(): bool
    {
        return $this->status?->code === self::STATUS_APPROVED;
    }

    public function isPaid(): bool
    {
        return $this->status?->code === self::STATUS_PAID;
    }
}
