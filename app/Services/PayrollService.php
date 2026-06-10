<?php

namespace App\Services;

use App\Contracts\EmployeeRepositoryInterface;
use App\Contracts\PayrollRepositoryInterface;
use App\DTOs\PayrollGenerationDTO;
use App\Models\Payroll;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly PayrollRepositoryInterface $payrolls,
        private readonly PayrollCalculationService $calculator,
    ) {
    }

    public function paginate(array $filters = [])
    {
        return $this->payrolls->paginate($filters);
    }

    public function generate(PayrollGenerationDTO $dto): Payroll
    {
        return DB::transaction(function () use ($dto) {
            $payroll = $this->payrolls->createHeader([
                'period_year' => $dto->periodYear,
                'period_month' => $dto->periodMonth,
                'status' => 'IN_REVIEW',
                'generated_by' => $dto->generatedBy,
            ]);

            $totals = [
                'total_income' => 0,
                'total_deductions' => 0,
                'total_net' => 0,
            ];

            foreach ($this->employees->active() as $employee) {
                $detail = $this->calculator->calculateEmployee($employee, $dto->periodYear, $dto->periodMonth);

                $totals['total_income'] += $detail['gross_income'];
                $totals['total_deductions'] += $detail['pension_deduction'];
                $totals['total_net'] += $detail['net_amount'];

                $this->payrolls->addDetail($payroll, $detail);
            }

            return $this->payrolls->updateTotals($payroll, [
                'total_income' => round($totals['total_income'], 2),
                'total_deductions' => round($totals['total_deductions'], 2),
                'total_net' => round($totals['total_net'], 2),
            ]);
        });
    }

    public function approve(Payroll $payroll, int $managerId): Payroll
    {
        $payroll->update([
            'status' => 'APPROVED',
            'approved_by' => $managerId,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return $payroll;
    }

    public function reject(Payroll $payroll, int $managerId, string $reason): Payroll
    {
        $payroll->update([
            'status' => 'REJECTED',
            'approved_by' => $managerId,
            'approved_at' => null,
            'rejection_reason' => $reason,
        ]);

        return $payroll;
    }
}
