<?php

namespace Tests\Feature\Concerns;

use App\Models\Bank;
use App\Models\Catalog;
use App\Models\Employee;
use App\Models\WorkShift;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

trait CreatesTestData
{
    protected function catalog(string $type, string $code, array $attributes = []): Catalog
    {
        return Catalog::create(array_merge([
            'type' => $type,
            'code' => $code,
            'name' => str($code)->replace('_', ' ')->title()->toString(),
            'description' => null,
            'status' => true,
        ], $attributes));
    }

    protected function role(string $name = 'Administrador'): Role
    {
        return Role::create([
            'name' => $name,
            'guard_name' => 'web',
        ]);
    }

    protected function permission(string $name): Permission
    {
        return Permission::create([
            'name' => $name,
            'guard_name' => 'web',
        ]);
    }

    protected function bank(array $attributes = []): Bank
    {
        return Bank::create(array_merge([
            'name' => 'Banco de Prueba',
            'code' => 'TEST',
            'status' => true,
        ], $attributes));
    }

    protected function workShift(array $attributes = []): WorkShift
    {
        return WorkShift::create(array_merge([
            'name' => 'Turno Dia',
            'description' => 'Jornada regular',
            'start_time' => '08:00',
            'break_start_time' => '12:00',
            'break_end_time' => '13:00',
            'end_time' => '17:00',
            'tolerance_minutes' => 10,
            'daily_hours' => 8,
            'crosses_midnight' => false,
            'status' => true,
        ], $attributes));
    }

    protected function employee(array $attributes = []): Employee
    {
        $documentTypeId = $attributes['document_type_id']
            ?? $this->catalog('DOCUMENT_TYPE', 'DNI')->id;

        return Employee::create(array_merge([
            'employee_code' => 'EMP-0001',
            'document_type_id' => $documentTypeId,
            'document_number' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Perez',
            'birth_date' => '1990-01-01',
            'email' => 'juan.perez@example.com',
            'hire_date' => '2024-01-15',
            'base_salary' => 1800,
            'status' => true,
        ], $attributes));
    }

    protected function attendanceCatalogs(): array
    {
        return [
            'draft' => $this->catalog('ATTENDANCE_MONTHLY_STATUS', 'DRAFT', ['name' => 'Borrador']),
            'closed' => $this->catalog('ATTENDANCE_MONTHLY_STATUS', 'CLOSED', ['name' => 'Cerrado']),
            'unmarked' => $this->catalog('ATTENDANCE_DAY_STATUS', 'UNMARKED', ['name' => 'Sin marcar']),
            'present' => $this->catalog('ATTENDANCE_DAY_STATUS', 'PRESENT', ['name' => 'Asistio']),
            'absent' => $this->catalog('ATTENDANCE_DAY_STATUS', 'ABSENT', ['name' => 'Falto']),
            'rest' => $this->catalog('ATTENDANCE_DAY_STATUS', 'REST', ['name' => 'Descanso']),
            'exchange_worked' => $this->catalog('ATTENDANCE_DAY_STATUS', 'EXCHANGE_WORKED', ['name' => 'Canje trabajado']),
            'exchange_applied' => $this->catalog('ATTENDANCE_EXCHANGE_STATUS', 'APPLIED', ['name' => 'Aplicado']),
        ];
    }

    protected function currentPeriodPayload(array $overrides = []): array
    {
        $date = Carbon::parse('2026-06-15');

        return array_merge([
            'month' => $date->month,
            'year' => $date->year,
            'observations' => 'Control mensual',
        ], $overrides);
    }
}
