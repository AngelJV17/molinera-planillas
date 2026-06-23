<?php

namespace App\Http\Controllers;

use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para administrar trabajadores.
 */
class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeService $service
    ) {}

    /**
     * Lista trabajadores registrados.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Workers/Index', [
            'workers' => $this->service->paginate(
                search: $request->search,
                status: $request->status,
                workShiftId: $request->integer('work_shift_id') ?: null,
                perPage: $request->integer('per_page', 10)
            ),
            'workShifts' => $this->service->formOptions()['workShifts'],
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'work_shift_id' => $request->work_shift_id,
                'per_page' => $request->per_page,
            ],
        ]);
    }

    /**
     * Muestra el formulario de creación.
     */
    public function create(): Response
    {
        return Inertia::render('Workers/Create', [
            'options' => $this->service->formOptions(),
        ]);
    }

    /**
     * Guarda un trabajador.
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()
            ->route('workers.index')
            ->with('success', 'Trabajador registrado correctamente.');
    }

    /**
     * Muestra el formulario de edición.
     */
    public function edit(Employee $worker): Response
    {
        return Inertia::render('Workers/Edit', [
            'worker' => $worker->load('district.province.department'),
            'options' => $this->service->formOptions(),
        ]);
    }

    /**
     * Actualiza un trabajador.
     */
    public function update(UpdateEmployeeRequest $request, Employee $worker): RedirectResponse
    {
        $this->service->update($worker, $request->validated());

        return redirect()
            ->route('workers.index')
            ->with('success', 'Trabajador actualizado correctamente.');
    }

    /**
     * Activa o desactiva un trabajador.
     */
    public function toggleStatus(Employee $worker): RedirectResponse
    {
        $this->service->toggleStatus($worker);

        return back()->with('success', 'Estado del trabajador actualizado.');
    }
}
