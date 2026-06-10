<?php

namespace App\Services;

use App\Contracts\PayrollRepositoryInterface;

class PlameExportService
{
    public function __construct(
        private readonly PayrollRepositoryInterface $payrolls,
    ) {
    }

    public function buildTxt(int $payrollId): string
    {
        $payroll = $this->payrolls->findForPlame($payrollId);

        return $payroll->details
            ->map(function ($detail) use ($payroll) {
                $employee = $detail->employee;

                return implode('|', [
                    $employee->document_type,
                    $employee->document_number,
                    $employee->last_name,
                    $employee->first_name,
                    str_pad((string) $payroll->period_month, 2, '0', STR_PAD_LEFT),
                    $payroll->period_year,
                    number_format((float) $detail->gross_income, 2, '.', ''),
                    number_format((float) $detail->pension_deduction, 2, '.', ''),
                    number_format((float) $detail->net_amount, 2, '.', ''),
                ]);
            })
            ->implode(PHP_EOL);
    }

    public function filename(int $year, int $month): string
    {
        return sprintf('plame_molicente_%04d_%02d.txt', $year, $month);
    }
}
