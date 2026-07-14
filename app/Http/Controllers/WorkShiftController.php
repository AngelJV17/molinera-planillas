<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkShift\StoreWorkShiftRequest;
use App\Http\Requests\WorkShift\UpdateWorkShiftRequest;
use App\Models\WorkShift;
use App\Services\WorkShiftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para administrar turnos laborales.
 */
class WorkShiftController extends Controller
{
    public function __construct(
        protected WorkShiftService $service
    ) {}

    /**
     * Lista los turnos laborales registrados.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('WorkShifts/Index', [
            'workShifts' => $this->service->paginate(
                search: $request->search,
                status: $request->status,
                perPage: $request->integer('per_page', 10)
            ),
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'per_page' => $request->per_page,
            ],
        ]);
    }

    /**
     * Muestra el formulario de creación.
     */
    public function create(): Response
    {
        return Inertia::render('WorkShifts/Create');
    }

    /**
     * Guarda un nuevo turno laboral.
     */
    public function store(StoreWorkShiftRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()
            ->route('organizational-structure.index')
            ->with('success', 'Turno registrado correctamente.');
    }

    /**
     * Muestra el formulario de edición.
     */
    public function edit(WorkShift $workShift): Response
    {
        $workShift->load('rules');

        return Inertia::render('WorkShifts/Edit', [
            'workShift' => $workShift,
        ]);
    }

    /**
     * Actualiza un turno laboral existente.
     */
    public function update(UpdateWorkShiftRequest $request, WorkShift $workShift): RedirectResponse
    {
        $this->service->update($workShift, $request->validated());

        return redirect()
            ->route('organizational-structure.index')
            ->with('success', 'Turno actualizado correctamente.');
    }

    /**
     * Activa o desactiva un turno laboral.
     */
    public function toggleStatus(WorkShift $workShift): RedirectResponse
    {
        $this->service->toggleStatus($workShift);

        return back()->with('success', 'Estado del turno actualizado.');
    }
}
