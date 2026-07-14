<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payroll\StorePayrollRequest;
use App\Models\Payroll;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollService $service
    ) {
    }

    /**
     * Muestra el listado y los controles principales de planillas.
     */
    public function index(Request $request): Response
    {
        $filters = [
            'period' => $request->input('period', ''),
            'status_id' => $request->input('status_id', ''),
            'per_page' => $request->input('per_page', 10),
        ];

        $payrolls = $this->service
            ->paginate($filters)
            ->through(fn(Payroll $payroll) => $this->payrollPayload($payroll));

        return Inertia::render('Payrolls/Index', [
            'payrolls' => $payrolls,
            'filters' => $filters,
            'statuses' => $this->service->statuses(),
            'monthOptions' => $this->service->monthOptions(),
            'yearOptions' => $this->service->yearOptions(),
            'defaultPeriod' => now()->subMonthNoOverflow()->format('Y-m'),
        ]);
    }

    /**
     * Genera una planilla desde asistencias cerradas.
     */
    public function store(StorePayrollRequest $request): RedirectResponse
    {
        $payroll = $this->service->generate(
            $request->validated(),
            $request->user()?->id
        );

        return redirect()
            ->route('payrolls.index', ['period' => sprintf('%04d-%02d', $payroll->year, $payroll->month)])
            ->with('success', 'Planilla generada correctamente y enviada a revision.');
    }

    public function approve(Payroll $payroll, Request $request): RedirectResponse
    {
        $this->service->approve($payroll, $request->user()?->id);

        return back()->with('success', 'Planilla aprobada correctamente.');
    }

    public function reject(Payroll $payroll, Request $request): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $this->service->reject(
            $payroll,
            $request->user()?->id,
            $request->input('reason')
        );

        return back()->with('success', 'Planilla rechazada correctamente.');
    }

    public function observe(Payroll $payroll, Request $request): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $this->service->observe(
            $payroll,
            $request->user()?->id,
            $request->input('reason')
        );

        return back()->with('success', 'Planilla observada correctamente. Corrige las asistencias necesarias y recalcula la planilla.');
    }

    public function recalculate(Payroll $payroll, Request $request): RedirectResponse
    {
        $this->service->recalculate($payroll, $request->user()?->id);

        return back()->with('success', 'Planilla recalculada correctamente y enviada nuevamente a revision.');
    }

    public function pay(Payroll $payroll, Request $request): RedirectResponse
    {
        $this->service->markAsPaid($payroll, $request->user()?->id);

        return back()->with('success', 'Planilla marcada como pagada.');
    }

    private function payrollPayload(Payroll $payroll): array
    {
        $payroll->loadMissing([
            'status:id,code,name',
            'details.concepts.conceptType:id,code,name',
        ]);

        return [
            'id' => $payroll->id,
            'code' => $payroll->code,
            'period' => $this->service->monthName($payroll->month) . ' ' . $payroll->year,
            'month' => $payroll->month,
            'year' => $payroll->year,
            'payment_date' => $payroll->payment_date?->toDateString(),
            'status' => [
                'id' => $payroll->status?->id,
                'code' => $payroll->status?->code,
                'name' => $payroll->status?->name,
            ],
            'employee_count' => $payroll->employee_count,
            'total_base_salary' => $payroll->total_base_salary,
            'total_income' => $payroll->total_income,
            'total_discount' => $payroll->total_discount,
            'total_employer_contribution' => $payroll->total_employer_contribution,
            'total_net' => $payroll->total_net,
            'observations' => $payroll->observations,
            'rejection_reason' => $payroll->rejection_reason,
            'can_approve' => $payroll->isInReview(),
            'can_observe' => $payroll->isInReview(),
            'can_reject' => $payroll->isInReview(),
            'can_pay' => $payroll->isApproved(),
            'can_recalculate' => $payroll->isObserved() || $payroll->isRejected(),
            'details' => $payroll->details
                ->sortBy('employee_name')
                ->values()
                ->map(fn($detail) => [
                    'id' => $detail->id,
                    'employee_name' => $detail->employee_name,
                    'employee_code' => $detail->employee_code,
                    'document_number' => $detail->document_number,
                    'pension_system_name' => $detail->pension_system_name ?? 'Sin regimen',
                    'base_salary' => $detail->base_salary,
                    'worked_days' => $detail->worked_days,
                    'absence_days' => $detail->absence_days,
                    'uncompensated_absence_days' => $detail->uncompensated_absence_days,
                    'overtime_hours' => $detail->overtime_hours,
                    'total_income' => $detail->total_income,
                    'total_discount' => $detail->total_discount,
                    'total_employer_contribution' => $detail->total_employer_contribution,
                    'net_pay' => $detail->net_pay,
                    'concepts' => $detail->concepts
                        ->sortBy('sort_order')
                        ->values()
                        ->map(fn($concept) => [
                            'id' => $concept->id,
                            'type' => [
                                'code' => $concept->conceptType?->code,
                                'name' => $concept->conceptType?->name,
                            ],
                            'code' => $concept->code,
                            'name' => $concept->name,
                            'quantity' => $concept->quantity,
                            'rate' => $concept->rate,
                            'amount' => $concept->amount,
                        ]),
                ]),
        ];
    }
}
