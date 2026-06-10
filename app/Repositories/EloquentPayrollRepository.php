<?php

namespace App\Repositories;

use App\Contracts\PayrollRepositoryInterface;
use App\Models\Payroll;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentPayrollRepository implements PayrollRepositoryInterface
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Payroll::query()
            ->withCount('details')
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();
    }

    public function createHeader(array $data): Payroll
    {
        return Payroll::create($data);
    }

    public function addDetail(Payroll $payroll, array $detail): void
    {
        $payroll->details()->create($detail);
    }

    public function updateTotals(Payroll $payroll, array $totals): Payroll
    {
        $payroll->update($totals);

        return $payroll;
    }

    public function findForPlame(int $payrollId): Payroll
    {
        return Payroll::query()
            ->with(['details.employee'])
            ->findOrFail($payrollId);
    }
}
