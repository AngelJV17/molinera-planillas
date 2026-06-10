<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PayrollDetail extends Model
{
    protected $fillable = [
        'payroll_id',
        'employee_id',
        'basic_salary',
        'family_allowance_applied',
        'family_allowance_amount',
        'overtime_hours',
        'overtime_amount',
        'gross_income',
        'pension_system',
        'pension_deduction',
        'net_amount',
        'calculation_snapshot',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'family_allowance_applied' => 'boolean',
        'family_allowance_amount' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'gross_income' => 'decimal:2',
        'pension_deduction' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'calculation_snapshot' => 'array',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function paymentSlip(): HasOne
    {
        return $this->hasOne(PaymentSlip::class);
    }
}