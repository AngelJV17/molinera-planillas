<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollDetail extends Model
{
    protected $fillable = [
        'payroll_id',
        'employee_id',
        'monthly_attendance_id',
        'employee_code',
        'employee_name',
        'document_number',
        'pension_system_code',
        'pension_system_name',
        'base_salary',
        'worked_days',
        'absence_days',
        'uncompensated_absence_days',
        'rest_days',
        'overtime_hours',
        'daily_rate',
        'hourly_rate',
        'total_income',
        'total_discount',
        'total_employer_contribution',
        'net_pay',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'daily_rate' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'total_income' => 'decimal:2',
            'total_discount' => 'decimal:2',
            'total_employer_contribution' => 'decimal:2',
            'net_pay' => 'decimal:2',
        ];
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function monthlyAttendance(): BelongsTo
    {
        return $this->belongsTo(MonthlyAttendance::class);
    }

    public function concepts(): HasMany
    {
        return $this->hasMany(PayrollDetailConcept::class);
    }
}
