<?php

namespace Database\Seeders;

use App\Models\Catalog;
use App\Models\Employee;
use App\Models\Bank;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = fn (string $type, string $code) => Catalog::query()
            ->where('type', $type)
            ->where('code', $code)
            ->firstOrFail();

        $dni = $catalog('DOCUMENT_TYPE', 'DNI');
        $active = $catalog('WORKER_STATUS', 'ACTIVE');
        $adminArea = $catalog('WORK_AREA', 'ADMIN');
        $accountingArea = $catalog('WORK_AREA', 'ACCOUNTING');
        $productionArea = $catalog('WORK_AREA', 'PRODUCTION');
        $securityArea = $catalog('WORK_AREA', 'SECURITY');
        $adminPayroll = $catalog('PAYROLL_GROUP', 'ADMIN');
        $productionPayroll = $catalog('PAYROLL_GROUP', 'PRODUCTION');
        $savingsAccount = $catalog('ACCOUNT_TYPE', 'SAVINGS');
        $onp = $catalog('PENSION_SYSTEM', 'ONP');
        $afpIntegra = $catalog('PENSION_SYSTEM', 'AFP_INTEGRA');
        $afpPrima = $catalog('PENSION_SYSTEM', 'AFP_PRIMA');
        $afpHabitat = $catalog('PENSION_SYSTEM', 'AFP_HABITAT');
        $morningShift = WorkShift::query()->where('name', 'Turno Mañana')->firstOrFail();
        $nightShift = WorkShift::query()->where('name', 'Turno Noche Rotativo 6x1')->firstOrFail();
        $bcp = Bank::query()->where('code', 'BCP')->firstOrFail();

        $administrativeEmployees = [
            [
                'employee_code' => 'ADM0001',
                'document_number' => '70000001',
                'first_name' => 'Carlos Eduardo',
                'last_name' => 'Ramos Flores',
                'email' => 'carlos.ramos@molicente.com',
                'phone' => '970000001',
                'position' => 'MANAGER',
                'area_id' => $adminArea->id,
                'role' => 'Administrador',
                'base_salary' => 3500,
                'pension_id' => $afpIntegra->id,
            ],
            [
                'employee_code' => 'ADM0002',
                'document_number' => '70000002',
                'first_name' => 'Mariana Isabel',
                'last_name' => 'Torres Aguilar',
                'email' => 'mariana.torres@molicente.com',
                'phone' => '970000002',
                'position' => 'HUMAN_RESOURCES',
                'area_id' => $adminArea->id,
                'role' => 'RRHH',
                'base_salary' => 2800,
                'pension_id' => $onp->id,
            ],
            [
                'employee_code' => 'ADM0003',
                'document_number' => '70000003',
                'first_name' => 'Lucia Fernanda',
                'last_name' => 'Salas Mendoza',
                'email' => 'lucia.salas@molicente.com',
                'phone' => '970000003',
                'position' => 'ACCOUNTANT',
                'area_id' => $accountingArea->id,
                'role' => 'Contabilidad',
                'base_salary' => 3000,
                'pension_id' => $afpPrima->id,
            ],
            [
                'employee_code' => 'ADM0004',
                'document_number' => '70000004',
                'first_name' => 'Jorge Antonio',
                'last_name' => 'Vargas Leon',
                'email' => 'jorge.vargas@molicente.com',
                'phone' => '970000004',
                'position' => 'GENERAL_MANAGER',
                'area_id' => $adminArea->id,
                'role' => 'Gerente',
                'base_salary' => 5200,
                'pension_id' => $afpHabitat->id,
            ],
        ];

        foreach ($administrativeEmployees as $employee) {
            $user = User::updateOrCreate(
                ['username' => $employee['document_number']],
                [
                    'name' => "{$employee['first_name']} {$employee['last_name']}",
                    'email' => $employee['email'],
                    'password' => $employee['document_number'],
                    'status' => true,
                    'must_change_password' => true,
                ]
            );

            $user->syncRoles([$employee['role']]);

            $createdEmployee = Employee::updateOrCreate(
                ['document_number' => $employee['document_number']],
                [
                    'employee_code' => $employee['employee_code'],
                    'document_type_id' => $dni->id,
                    'first_name' => $employee['first_name'],
                    'last_name' => $employee['last_name'],
                    'birth_date' => '1988-01-15',
                    'phone' => $employee['phone'],
                    'email' => $employee['email'],
                    'address' => 'Huanuco',
                    'hire_date' => '2026-01-01',
                    'position_id' => $catalog('POSITION', $employee['position'])->id,
                    'work_area_id' => $employee['area_id'],
                    'payroll_group_id' => $adminPayroll->id,
                    'work_shift_id' => $morningShift->id,
                    'employment_status_id' => $active->id,
                    'base_salary' => $employee['base_salary'],
                    'pension_system_id' => $employee['pension_id'],
                    'status' => true,
                    'user_id' => $user->id,
                ]
            );

            $this->syncBcpAccount($createdEmployee, $bcp->id, $savingsAccount->id);
        }

        $productionEmployees = [
            ['EMP0001', '72145896', 'Juan Carlos', 'Ramirez Flores', 'SUPERVISOR', $productionArea->id, 2200, $afpIntegra->id, $morningShift->id],
            ['EMP0002', '74125896', 'Pedro Luis', 'Torres Vega', 'MILL_OPERATOR', $productionArea->id, 1800, $onp->id, $morningShift->id],
            ['EMP0003', '75236984', 'Miguel Angel', 'Soto Rojas', 'MILL_OPERATOR', $productionArea->id, 1800, $afpPrima->id, $morningShift->id],
            ['EMP0004', '76325874', 'Luis Alberto', 'Mendoza Diaz', 'MILL_OPERATOR', $productionArea->id, 1850, $onp->id, $morningShift->id],
            ['EMP0005', '77412589', 'Jorge Antonio', 'Castillo Ruiz', 'MILL_OPERATOR', $productionArea->id, 1800, $afpIntegra->id, $morningShift->id],
            ['EMP0006', '78523691', 'Carlos Enrique', 'Vasquez Paredes', 'SUPERVISOR', $productionArea->id, 2400, $onp->id, $morningShift->id],
            ['EMP0007', '79632584', 'Ricardo Jose', 'Herrera Silva', 'WAREHOUSE_ASSISTANT', $productionArea->id, 1750, $afpHabitat->id, $morningShift->id],
            ['EMP0008', '70741258', 'Manuel Jesus', 'Salazar Torres', 'MILL_OPERATOR', $productionArea->id, 1800, $onp->id, $morningShift->id],
            ['EMP0009', '71852369', 'Fernando David', 'Lopez Ramos', 'MILL_OPERATOR', $productionArea->id, 1800, $afpPrima->id, $morningShift->id],
            ['EMP0010', '72987415', 'Roberto', 'Quispe Mendoza', 'WATCHMAN', $securityArea->id, 2200, $afpIntegra->id, $nightShift->id],
        ];

        foreach ($productionEmployees as [$code, $document, $firstName, $lastName, $positionCode, $areaId, $salary, $pensionId, $shiftId]) {
            $createdEmployee = Employee::updateOrCreate(
                ['document_number' => $document],
                [
                    'employee_code' => $code,
                    'document_type_id' => $dni->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'birth_date' => '1990-06-10',
                    'phone' => '98'.$document,
                    'email' => strtolower(str_replace(' ', '.', "{$firstName}.{$lastName}")).'@molicente.com',
                    'address' => 'Huanuco',
                    'hire_date' => '2026-01-01',
                    'position_id' => $catalog('POSITION', $positionCode)->id,
                    'work_area_id' => $areaId,
                    'payroll_group_id' => $productionPayroll->id,
                    'work_shift_id' => $shiftId,
                    'employment_status_id' => $active->id,
                    'base_salary' => $salary,
                    'pension_system_id' => $pensionId,
                    'status' => true,
                    'user_id' => null,
                ]
            );

            $this->syncBcpAccount($createdEmployee, $bcp->id, $savingsAccount->id);
        }
    }

    private function syncBcpAccount(Employee $employee, int $bankId, int $accountTypeId): void
    {
        $document = preg_replace('/\D+/', '', $employee->document_number);
        $accountNumber = '191'.$document.str_pad((string) ($employee->id % 100), 2, '0', STR_PAD_LEFT);
        $cci = '002191'.str_pad($document, 12, '0', STR_PAD_LEFT).str_pad((string) ($employee->id % 100), 2, '0', STR_PAD_LEFT);

        $employee->bankAccounts()->updateOrCreate(
            ['is_primary' => true],
            [
                'bank_id' => $bankId,
                'account_type_id' => $accountTypeId,
                'account_number' => $accountNumber,
                'cci' => $cci,
                'status' => true,
            ]
        );
    }
}
