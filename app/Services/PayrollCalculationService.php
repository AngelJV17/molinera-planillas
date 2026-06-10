<?php

namespace App\Services;

use App\Contracts\AttendanceRepositoryInterface;
use App\Models\Employee;

class PayrollCalculationService
{
    private const FAMILY_ALLOWANCE_AMOUNT = 113.00;
    private const ONP_RATE = 0.13;
    private const AFP_RATE = 0.1184;
    private const MONTHLY_HOURS = 240;
    private const OVERTIME_MULTIPLIER = 1.25;

    public function __construct(
        private readonly AttendanceRepositoryInterface $attendances,
    ) {
    }

    public function calculateEmployee(Employee $employee, int $year, int $month): array
    {
        $basicSalary = (float) $employee->basic_salary;
        $familyAllowance = $employee->family_allowance ? self::FAMILY_ALLOWANCE_AMOUNT : 0;
        $overtimeHours = $this->attendances->employeeOvertimeForPeriod($employee->id, $year, $month);
        $hourValue = $basicSalary / self::MONTHLY_HOURS;
        $overtimeAmount = round($overtimeHours * $hourValue * self::OVERTIME_MULTIPLIER, 2);
        $grossIncome = round($basicSalary + $familyAllowance + $overtimeAmount, 2);
        $pensionDeduction = round($grossIncome * $this->pensionRate($employee->pension_system), 2);
        $netAmount = round($grossIncome - $pensionDeduction, 2);

        return [
            'employee_id' => $employee->id,
            'basic_salary' => $basicSalary,
            'family_allowance_applied' => $employee->family_allowance,
            'family_allowance_amount' => $familyAllowance,
            'overtime_hours' => $overtimeHours,
            'overtime_amount' => $overtimeAmount,
            'gross_income' => $grossIncome,
            'pension_system' => $employee->pension_system,
            'pension_deduction' => $pensionDeduction,
            'net_amount' => $netAmount,
            'calculation_snapshot' => [
                'family_allowance_amount' => self::FAMILY_ALLOWANCE_AMOUNT,
                'monthly_hours' => self::MONTHLY_HOURS,
                'overtime_multiplier' => self::OVERTIME_MULTIPLIER,
                'pension_rate' => $this->pensionRate($employee->pension_system),
            ],
        ];
    }

    private function pensionRate(string $pensionSystem): float
    {
        return match ($pensionSystem) {
            'AFP' => self::AFP_RATE,
            'NONE' => 0,
            default => self::ONP_RATE,
        };
    }
}
