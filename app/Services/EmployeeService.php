<?php
namespace App\Services;

use App\Models\Catalog;
use App\Models\Department;
use App\Models\District;
use App\Models\Employee;
use App\Models\Province;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Gestiona la consulta y persistencia de trabajadores.
 */
class EmployeeService
{
    /**
     * Lista trabajadores con relaciones necesarias para el módulo.
     */
    public function paginate(?string $search, ?string $status, ?int $workShiftId, ?int $workAreaId, int $perPage): LengthAwarePaginator
    {
        return Employee::query()
            ->with([
                'user:id,name,email',
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
            ->when($status !== null && $status !== '', fn($query) => $query->where('status', (bool) $status))
            ->when($workShiftId, fn($query) => $query->where('work_shift_id', $workShiftId))
            ->when($workAreaId, fn($query) => $query->where('work_area_id', $workAreaId))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(min($perPage, 100))
            ->withQueryString();
    }

    /**
     * Opciones reutilizadas por los formularios del módulo.
     */
    public function formOptions(): array
    {
        return [
            'catalogs'    => $this->catalogOptions(),
            'workShifts'  => WorkShift::active()
                ->orderBy('name')
                ->get(['id', 'name', 'start_time', 'end_time']),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'provinces'   => Province::orderBy('name')->get(['id', 'department_id', 'name']),
            'districts'   => District::orderBy('name')->get(['id', 'province_id', 'name']),
        ];
    }

    /**
     * Registra un trabajador.
     */
    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {

            $hasSystemAccess = $data['has_system_access'] ?? false;

            $employeeData = Arr::except($data, [
                'has_system_access',
            ]);

            $employeeData['employee_code'] = $this->generateEmployeeCode();

            if ($hasSystemAccess) {

                $user = User::create([
                    'name'     => trim(
                        $employeeData['first_name'] . ' ' .
                        $employeeData['last_name']
                    ),

                    'username' => $employeeData['document_number'],

                    'email'    => $employeeData['email'] ?: null,

                    // contraseña inicial = DNI
                    'password' => Hash::make(
                        $employeeData['document_number']
                    ),

                    'status'   => true,
                ]);

                $employeeData['user_id'] = $user->id;
            }

            return Employee::create($employeeData);
        });
    }

    /**
     * Actualiza un trabajador existente.
     */
    public function update(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data) {

            $hasSystemAccess = $data['has_system_access'] ?? false;

            $employeeData = Arr::except($data, [
                'has_system_access',
            ]);

            // Crear usuario si antes no tenía
            if ($hasSystemAccess && ! $employee->user_id) {

                $user = User::create([
                    'name'     => trim(
                        $employeeData['first_name'] . ' ' .
                        $employeeData['last_name']
                    ),

                    'username' => $employeeData['document_number'],

                    'email'    => $employeeData['email'] ?: null,

                    'password' => Hash::make(
                        $employeeData['document_number']
                    ),

                    'status'   => true,
                ]);

                $employeeData['user_id'] = $user->id;
            }

            // Actualizar usuario existente
            if ($hasSystemAccess && $employee->user_id) {

                $employee->user->update([
                    'name'     => trim(
                        $employeeData['first_name'] . ' ' .
                        $employeeData['last_name']
                    ),

                    'username' => $employeeData['document_number'],

                    'email'    => $employeeData['email'] ?: null,
                ]);
            }

            // Quitar acceso
            if (! $hasSystemAccess && $employee->user_id) {

                $employee->user->update([
                    'status' => false,
                ]);

                $employeeData['user_id'] = null;
            }

            $employee->update($employeeData);

            return $employee;
        });
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

    /**
     * Genera el código interno del trabajador.
     */
    private function generateEmployeeCode(): string
    {
        $lastId = Employee::withTrashed()->max('id') + 1;

        return 'EMP-' . str_pad($lastId, 4, '0', STR_PAD_LEFT);
    }
}
