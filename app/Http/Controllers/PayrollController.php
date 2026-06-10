<?php

namespace App\Http\Controllers;

use App\DTOs\PayrollGenerationDTO;
use App\Http\Requests\GeneratePayrollRequest;
use App\Http\Requests\RejectPayrollRequest;
use App\Models\Payroll;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollService $payrolls,
    ) {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Payrolls/Index', [
            'payrolls' => $this->payrolls->paginate($request->only(['status', 'per_page'])),
        ]);
    }

    public function store(GeneratePayrollRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['generated_by'] = $request->user()?->id;

        $this->payrolls->generate(PayrollGenerationDTO::fromArray($data));

        return back()->with('success', 'Planilla generada y enviada a revision.');
    }

    public function approve(Request $request, Payroll $payroll): RedirectResponse
    {
        $this->payrolls->approve($payroll, $request->user()->id);

        return back()->with('success', 'Planilla aprobada por gerencia.');
    }

    public function reject(RejectPayrollRequest $request, Payroll $payroll): RedirectResponse
    {
        $this->payrolls->reject($payroll, $request->user()->id, $request->validated('reason'));

        return back()->with('success', 'Planilla rechazada por gerencia.');
    }
}
