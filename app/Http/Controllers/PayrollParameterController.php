<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayrollParameter\StorePayrollParameterRequest;
use App\Http\Requests\PayrollParameter\UpdatePayrollParameterRequest;
use App\Models\PayrollParameter;
use App\Services\PayrollParameterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollParameterController extends Controller
{
    public function __construct(
        private readonly PayrollParameterService $service
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'per_page']);

        return Inertia::render('PayrollParameters/Index', [
            'parameters' => $this->service->paginate($filters),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('PayrollParameters/Create');
    }

    public function store(StorePayrollParameterRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()
            ->route('payroll-parameters.index')
            ->with('success', 'Parametro de planilla registrado correctamente.');
    }

    public function edit(PayrollParameter $payroll_parameter): Response
    {
        return Inertia::render('PayrollParameters/Edit', [
            'parameter' => $payroll_parameter,
        ]);
    }

    public function update(UpdatePayrollParameterRequest $request, PayrollParameter $payroll_parameter): RedirectResponse
    {
        $this->service->update($payroll_parameter, $request->validated());

        return redirect()
            ->route('payroll-parameters.index')
            ->with('success', 'Parametro de planilla actualizado correctamente.');
    }

    public function toggleStatus(PayrollParameter $payroll_parameter): RedirectResponse
    {
        $this->service->toggleStatus($payroll_parameter);

        return back()->with('success', 'Estado del parametro actualizado correctamente.');
    }
}
