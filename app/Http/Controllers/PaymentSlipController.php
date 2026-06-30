<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Services\PayrollService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentSlipController extends Controller
{
    public function __construct(
        private readonly PayrollService $payrollService
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->input('search', ''),
            'period' => $request->input('period', ''),
            'per_page' => $request->input('per_page', 10),
        ];

        $period = $this->payrollService->parsePeriod($filters['period']);

        $slips = PayrollDetail::query()
            ->with(['payroll.status:id,code,name'])
            ->whereHas('payroll.status', fn(Builder $query) => $query->whereIn('code', [
                Payroll::STATUS_APPROVED,
                Payroll::STATUS_PAID,
            ]))
            ->when($filters['search'], function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('employee_name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%");
                });
            })
            ->when($period['month'], fn(Builder $query) => $query->whereHas('payroll', fn(Builder $payrollQuery) => $payrollQuery->where('month', $period['month'])))
            ->when($period['year'], fn(Builder $query) => $query->whereHas('payroll', fn(Builder $payrollQuery) => $payrollQuery->where('year', $period['year'])))
            ->latest()
            ->paginate(min((int) ($filters['per_page'] ?? 10), 100))
            ->withQueryString()
            ->through(fn(PayrollDetail $detail) => [
                'id' => $detail->id,
                'employee_name' => $detail->employee_name,
                'employee_code' => $detail->employee_code,
                'document_number' => $detail->document_number,
                'period' => $this->payrollService->monthName($detail->payroll->month) . ' ' . $detail->payroll->year,
                'payroll_code' => $detail->payroll->code,
                'status' => [
                    'code' => $detail->payroll->status?->code,
                    'name' => $detail->payroll->status?->name,
                ],
                'total_income' => $detail->total_income,
                'total_discount' => $detail->total_discount,
                'net_pay' => $detail->net_pay,
            ]);

        return Inertia::render('PaymentSlips/Index', [
            'slips' => $slips,
            'filters' => $filters,
        ]);
    }
}
