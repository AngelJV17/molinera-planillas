<?php

namespace App\Http\Controllers;

use App\DTOs\EmployeeStoreDTO;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employees,
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'per_page']);

        return Inertia::render('Workers/Index', [
            'employees' => $this->employees->paginate($filters),
            'filters' => $filters,
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $this->employees->create(EmployeeStoreDTO::fromArray($request->validated()));

        return back()->with('success', 'Trabajador registrado correctamente.');
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->employees->update($employee, EmployeeStoreDTO::fromArray($request->validated()));

        return back()->with('success', 'Trabajador actualizado correctamente.');
    }
}
