<?php

namespace App\Services;

use App\Models\Catalog;
use App\Models\Department;
use App\Models\District;
use App\Models\Employee;
use App\Models\Province;
use App\Models\WorkShift;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Gestiona la consulta y persistencia de trabajadores.
 */
class EmployeeService
{
    /**
     * Lista trabajadores con relaciones necesarias para el módulo.
     */
    public function paginate(?string $search, ?string $status, ?int $workShiftId, int $perPage): LengthAwarePaginator
    {
        return Employee::query()
            ->with([
                'documentType:id,name,code',
                'district.province.department',
                'workShift:id,name,start_time,end_time',
                'position:id,name',
                'workArea:id,name',
                'employmentStatus:id,name',
                'pensionSystem:id,name',
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('employee_code', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status !== null && $status !== '', fn ($query) => $query->where('status', (bool) $status))
            ->when($workShiftId, fn ($query) => $query->where('work_shift_id', $workShiftId))
            ->latest()
            ->paginate(min($perPage, 100))
            ->withQueryString();
    }

    /**
     * Opciones reutilizadas por los formularios del módulo.
     */
    public function formOptions(): array
    {
        return [
            'catalogs' => $this->catalogOptions(),
            'workShifts' => WorkShift::active()
                ->orderBy('name')
                ->get(['id', 'name', 'start_time', 'end_time']),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'provinces' => Province::orderBy('name')->get(['id', 'department_id', 'name']),
            'districts' => District::orderBy('name')->get(['id', 'province_id', 'name']),
        ];
    }

    /**
     * Registra un trabajador.
     */
    public function create(array $data): Employee
    {
        return Employee::create($data);
    }

    /**
     * Actualiza un trabajador existente.
     */
    public function update(Employee $employee, array $data): Employee
    {
        $employee->update($data);

        return $employee;
    }

    /**
     * Activa o desactiva el trabajador sin eliminarlo físicamente.
     */
    public function toggleStatus(Employee $employee): Employee
    {
        $employee->update([
            'status' => ! $employee->status,
        ]);

        return $employee;
    }

    /**
     * Agrupa catálogos activos por tipo para los selects.
     */
    private function catalogOptions(): Collection
    {
        return Catalog::active()
            ->whereIn('type', [
                'DOCUMENT_TYPE',
                'GENDER',
                'MARITAL_STATUS',
                'POSITION',
                'WORK_AREA',
                'WORKER_STATUS',
                'PENSION_SYSTEM',
            ])
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'type', 'code', 'name'])
            ->groupBy('type');
    }
}
