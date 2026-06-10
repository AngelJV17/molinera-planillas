<?php

namespace App\Contracts;

use App\Models\Payroll;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PayrollRepositoryInterface
{
    public function paginate(array $filters = []): LengthAwarePaginator;

    public function createHeader(array $data): Payroll;

    public function addDetail(Payroll $payroll, array $detail): void;

    public function updateTotals(Payroll $payroll, array $totals): Payroll;

    public function findForPlame(int $payrollId): Payroll;
}