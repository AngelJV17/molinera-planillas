<?php
namespace Database\Seeders;

use App\Models\Catalog;
use App\Models\Employee;
use App\Models\WorkShift;
use Illuminate\Database\Seeder;
use RuntimeException;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $catalogs = Catalog::query()
            ->whereIn('type', [
                'DOCUMENT_TYPE',
                'PENSION_SYSTEM',
                'WORKER_STATUS',
                'GENDER',
                'MARITAL_STATUS',
                'POSITION',
                'WORK_AREA',
            ])
            ->whereIn('code', [
                'DNI',
                'CE',
                'PASSPORT',
                'MALE',
                'FEMALE',
                'SINGLE',
                'MARRIED',
                'COHABITANT',
                'DIVORCED',
                'WIDOWED',
                'ACTIVE',
                'ONP',
                'AFP_INTEGRA',
                'AFP_PRIMA',
                'AFP_PROFUTURO',
                'AFP_HABITAT',
                'NONE',
                'ADMIN',
                'PRODUCTION',
                'WAREHOUSE',
                'SECURITY',
                'ACCOUNTING',
                'MANAGER',
                'ACCOUNTANT',
                'SUPERVISOR',
                'MILL_OPERATOR',
                'WAREHOUSE_ASSISTANT',
                'ADMIN_ASSISTANT',
                'WATCHMAN',
            ])
            ->get()
            ->groupBy('type')
            ->mapWithKeys(fn ($items, $type) => [
                $type => $items->keyBy('code')->map(fn ($item) => $item->id)->all(),
            ])
            ->all();

        $defaultWorkShifts = [
            'Turno Día' => [
                'description' => 'Jornada diurna estándar con descanso al mediodía.',
                'start_time' => '08:00',
                'break_start_time' => '12:00',
                'break_end_time' => '13:00',
                'end_time' => '17:00',
                'tolerance_minutes' => 10,
                'daily_hours' => 8,
                'crosses_midnight' => false,
                'status' => true,
            ],
            'Turno Tarde' => [
                'description' => 'Jornada vespertina con descanso intermedio.',
                'start_time' => '13:00',
                'break_start_time' => '17:00',
                'break_end_time' => '17:30',
                'end_time' => '22:00',
                'tolerance_minutes' => 10,
                'daily_hours' => 8,
                'crosses_midnight' => false,
                'status' => true,
            ],
        ];

        $workShiftIds = collect($defaultWorkShifts)
            ->mapWithKeys(fn ($attributes, $name) => [
                $name => WorkShift::firstOrCreate(
                    ['name' => $name],
                    array_merge(['name' => $name], $attributes)
                )->id,
            ])
            ->all();

        $employees = [
            [
                'employee_code' => 'EMP0001',
                'document_type_id' => $this->catalogId($catalogs, 'DOCUMENT_TYPE', 'DNI'),
                'document_number' => '72145896',
                'first_name' => 'Juan Carlos',
                'last_name' => 'Ramirez Flores',
                'birth_date' => '1990-05-15',
                'gender_id' => $this->catalogId($catalogs, 'GENDER', 'MALE'),
                'marital_status_id' => $this->catalogId($catalogs, 'MARITAL_STATUS', 'SINGLE'),
                'phone' => '987654321',
                'email' => 'juan.ramirez@molicente.com',
                'address' => 'Jr. Los Laureles 245, Amarilis',
                'hire_date' => '2025-01-10',
                'work_shift_id' => $workShiftIds['Turno Día'],
                'position_id' => $this->catalogId($catalogs, 'POSITION', 'MANAGER'),
                'work_area_id' => $this->catalogId($catalogs, 'WORK_AREA', 'ADMIN'),
                'employment_status_id' => $this->catalogId($catalogs, 'WORKER_STATUS', 'ACTIVE'),
                'base_salary' => 1800,
                'pension_system_id' => $this->catalogId($catalogs, 'PENSION_SYSTEM', 'AFP_HABITAT'),
                'status' => true,
            ],
            [
                'employee_code' => 'EMP0002',
                'document_type_id' => $this->catalogId($catalogs, 'DOCUMENT_TYPE', 'DNI'),
                'document_number' => '74125896',
                'first_name' => 'Pedro Luis',
                'last_name' => 'Torres Vega',
                'birth_date' => '1988-08-22',
                'gender_id' => $this->catalogId($catalogs, 'GENDER', 'MALE'),
                'marital_status_id' => $this->catalogId($catalogs, 'MARITAL_STATUS', 'MARRIED'),
                'phone' => '986321547',
                'email' => 'pedro.torres@molicente.com',
                'address' => 'Av. Universitaria 512, Huánuco',
                'hire_date' => '2025-01-12',
                'work_shift_id' => $workShiftIds['Turno Día'],
                'position_id' => $this->catalogId($catalogs, 'POSITION', 'ADMIN_ASSISTANT'),
                'work_area_id' => $this->catalogId($catalogs, 'WORK_AREA', 'ACCOUNTING'),
                'employment_status_id' => $this->catalogId($catalogs, 'WORKER_STATUS', 'ACTIVE'),
                'base_salary' => 1700,
                'pension_system_id' => $this->catalogId($catalogs, 'PENSION_SYSTEM', 'AFP_INTEGRA'),
                'status' => true,
            ],

            [
                'employee_code' => 'EMP0003',
                'document_type_id' => $this->catalogId($catalogs, 'DOCUMENT_TYPE', 'DNI'),
                'document_number' => '75236984',
                'first_name' => 'Miguel Angel',
                'last_name' => 'Soto Rojas',
                'birth_date' => '1992-01-18',
                'gender_id' => $this->catalogId($catalogs, 'GENDER', 'MALE'),
                'marital_status_id' => $this->catalogId($catalogs, 'MARITAL_STATUS', 'SINGLE'),
                'phone' => '985214785',
                'email' => 'miguel.soto@molicente.com',
                'address' => 'Jr. Dos de Mayo 879, Huánuco',
                'hire_date' => '2025-01-15',
                'work_shift_id' => $workShiftIds['Turno Día'],
                'position_id' => $this->catalogId($catalogs, 'POSITION', 'MILL_OPERATOR'),
                'work_area_id' => $this->catalogId($catalogs, 'WORK_AREA', 'PRODUCTION'),
                'employment_status_id' => $this->catalogId($catalogs, 'WORKER_STATUS', 'ACTIVE'),
                'base_salary' => 1800,
                'pension_system_id' => $this->catalogId($catalogs, 'PENSION_SYSTEM', 'AFP_HABITAT'),
                'status' => true,
            ],

            [
                'employee_code' => 'EMP0004',
                'document_type_id' => $this->catalogId($catalogs, 'DOCUMENT_TYPE', 'DNI'),
                'document_number' => '76325874',
                'first_name' => 'Luis Alberto',
                'last_name' => 'Mendoza Diaz',
                'birth_date' => '1987-11-04',
                'gender_id' => $this->catalogId($catalogs, 'GENDER', 'MALE'),
                'marital_status_id' => $this->catalogId($catalogs, 'MARITAL_STATUS', 'MARRIED'),
                'phone' => '984785236',
                'email' => 'luis.mendoza@molicente.com',
                'address' => 'Jr. Tarapacá 345, Amarilis',
                'hire_date' => '2025-02-01',
                'work_shift_id' => $workShiftIds['Turno Día'],
                'position_id' => $this->catalogId($catalogs, 'POSITION', 'SUPERVISOR'),
                'work_area_id' => $this->catalogId($catalogs, 'WORK_AREA', 'PRODUCTION'),
                'employment_status_id' => $this->catalogId($catalogs, 'WORKER_STATUS', 'ACTIVE'),
                'base_salary' => 1900,
                'pension_system_id' => $this->catalogId($catalogs, 'PENSION_SYSTEM', 'AFP_INTEGRA'),
                'status' => true,
            ],

            [
                'employee_code' => 'EMP0005',
                'document_type_id' => $this->catalogId($catalogs, 'DOCUMENT_TYPE', 'DNI'),
                'document_number' => '77412589',
                'first_name' => 'Jorge Antonio',
                'last_name' => 'Castillo Ruiz',
                'birth_date' => '1991-03-09',
                'gender_id' => $this->catalogId($catalogs, 'GENDER', 'MALE'),
                'marital_status_id' => $this->catalogId($catalogs, 'MARITAL_STATUS', 'SINGLE'),
                'phone' => '983654128',
                'email' => 'jorge.castillo@molicente.com',
                'address' => 'Jr. Abtao 152, Huánuco',
                'hire_date' => '2025-02-10',
                'work_shift_id' => $workShiftIds['Turno Día'],
                'position_id' => $this->catalogId($catalogs, 'POSITION', 'WAREHOUSE_ASSISTANT'),
                'work_area_id' => $this->catalogId($catalogs, 'WORK_AREA', 'WAREHOUSE'),
                'employment_status_id' => $this->catalogId($catalogs, 'WORKER_STATUS', 'ACTIVE'),
                'base_salary' => 1750,
                'pension_system_id' => $this->catalogId($catalogs, 'PENSION_SYSTEM', 'AFP_HABITAT'),
                'status' => true,
            ],

            [
                'employee_code' => 'EMP0006',
                'document_type_id' => $this->catalogId($catalogs, 'DOCUMENT_TYPE', 'DNI'),
                'document_number' => '78523691',
                'first_name' => 'Carlos Enrique',
                'last_name' => 'Vasquez Paredes',
                'birth_date' => '1989-09-30',
                'gender_id' => $this->catalogId($catalogs, 'GENDER', 'MALE'),
                'marital_status_id' => $this->catalogId($catalogs, 'MARITAL_STATUS', 'MARRIED'),
                'phone' => '982145369',
                'email' => 'carlos.vasquez@molicente.com',
                'address' => 'Av. Perú 723, Amarilis',
                'hire_date' => '2025-02-15',
                'work_shift_id' => $workShiftIds['Turno Día'],
                'position_id' => $this->catalogId($catalogs, 'POSITION', 'MILL_OPERATOR'),
                'work_area_id' => $this->catalogId($catalogs, 'WORK_AREA', 'PRODUCTION'),
                'employment_status_id' => $this->catalogId($catalogs, 'WORKER_STATUS', 'ACTIVE'),
                'base_salary' => 1850,
                'pension_system_id' => $this->catalogId($catalogs, 'PENSION_SYSTEM', 'AFP_INTEGRA'),
                'status' => true,
            ],

            [
                'employee_code' => 'EMP0007',
                'document_type_id' => $this->catalogId($catalogs, 'DOCUMENT_TYPE', 'DNI'),
                'document_number' => '79632584',
                'first_name' => 'Ricardo Jose',
                'last_name' => 'Herrera Silva',
                'birth_date' => '1993-07-11',
                'gender_id' => $this->catalogId($catalogs, 'GENDER', 'MALE'),
                'marital_status_id' => $this->catalogId($catalogs, 'MARITAL_STATUS', 'SINGLE'),
                'phone' => '981258741',
                'email' => 'ricardo.herrera@molicente.com',
                'address' => 'Jr. Huallayco 567, Huánuco',
                'hire_date' => '2025-03-01',
                'work_shift_id' => $workShiftIds['Turno Día'],
                'position_id' => $this->catalogId($catalogs, 'POSITION', 'MILL_OPERATOR'),
                'work_area_id' => $this->catalogId($catalogs, 'WORK_AREA', 'PRODUCTION'),
                'employment_status_id' => $this->catalogId($catalogs, 'WORKER_STATUS', 'ACTIVE'),
                'base_salary' => 1800,
                'pension_system_id' => $this->catalogId($catalogs, 'PENSION_SYSTEM', 'AFP_HABITAT'),
                'status' => true,
            ],

            [
                'employee_code' => 'EMP0008',
                'document_type_id' => $this->catalogId($catalogs, 'DOCUMENT_TYPE', 'DNI'),
                'document_number' => '70741258',
                'first_name' => 'Manuel Jesus',
                'last_name' => 'Salazar Torres',
                'birth_date' => '1986-12-19',
                'gender_id' => $this->catalogId($catalogs, 'GENDER', 'MALE'),
                'marital_status_id' => $this->catalogId($catalogs, 'MARITAL_STATUS', 'MARRIED'),
                'phone' => '980741258',
                'email' => 'manuel.salazar@molicente.com',
                'address' => 'Jr. San Martín 901, Amarilis',
                'hire_date' => '2025-03-05',
                'work_shift_id' => $workShiftIds['Turno Día'],
                'position_id' => $this->catalogId($catalogs, 'POSITION', 'MILL_OPERATOR'),
                'work_area_id' => $this->catalogId($catalogs, 'WORK_AREA', 'PRODUCTION'),
                'employment_status_id' => $this->catalogId($catalogs, 'WORKER_STATUS', 'ACTIVE'),
                'base_salary' => 1700,
                'pension_system_id' => $this->catalogId($catalogs, 'PENSION_SYSTEM', 'AFP_INTEGRA'),
                'status' => true,
            ],

            [
                'employee_code' => 'EMP0009',
                'document_type_id' => $this->catalogId($catalogs, 'DOCUMENT_TYPE', 'DNI'),
                'document_number' => '71852369',
                'first_name' => 'Fernando David',
                'last_name' => 'Lopez Ramos',
                'birth_date' => '1994-04-02',
                'gender_id' => $this->catalogId($catalogs, 'GENDER', 'MALE'),
                'marital_status_id' => $this->catalogId($catalogs, 'MARITAL_STATUS', 'SINGLE'),
                'phone' => '979852147',
                'email' => 'fernando.lopez@molicente.com',
                'address' => 'Jr. Independencia 421, Huánuco',
                'hire_date' => '2025-03-10',
                'work_shift_id' => $workShiftIds['Turno Día'],
                'position_id' => $this->catalogId($catalogs, 'POSITION', 'MILL_OPERATOR'),
                'work_area_id' => $this->catalogId($catalogs, 'WORK_AREA', 'PRODUCTION'),
                'employment_status_id' => $this->catalogId($catalogs, 'WORKER_STATUS', 'ACTIVE'),
                'base_salary' => 1800,
                'pension_system_id' => $this->catalogId($catalogs, 'PENSION_SYSTEM', 'AFP_HABITAT'),
                'status' => true,
            ],

            [
                'employee_code' => 'EMP0010',
                'document_type_id' => $this->catalogId($catalogs, 'DOCUMENT_TYPE', 'DNI'),
                'document_number' => '72987415',
                'first_name' => 'Roberto',
                'last_name' => 'Quispe Mendoza',
                'birth_date' => '1985-06-25',
                'gender_id' => $this->catalogId($catalogs, 'GENDER', 'MALE'),
                'marital_status_id' => $this->catalogId($catalogs, 'MARITAL_STATUS', 'MARRIED'),
                'phone' => '978963258',
                'email' => 'roberto.quispe@molicente.com',
                'address' => 'Jr. Leoncio Prado 210, Amarilis',
                'hire_date' => '2025-01-01',
                'work_shift_id' => $workShiftIds['Turno Tarde'],
                'position_id' => $this->catalogId($catalogs, 'POSITION', 'ACCOUNTANT'),
                'work_area_id' => $this->catalogId($catalogs, 'WORK_AREA', 'ACCOUNTING'),
                'employment_status_id' => $this->catalogId($catalogs, 'WORKER_STATUS', 'ACTIVE'),
                'base_salary' => 2200,
                'pension_system_id' => $this->catalogId($catalogs, 'PENSION_SYSTEM', 'AFP_HABITAT'),
                'status' => true,
            ],
        ];

        foreach ($employees as $employee) {
            Employee::updateOrCreate([
                'employee_code' => $employee['employee_code'],
            ], $employee);
        }
    }

    private function catalogId(array $catalogs, string $type, string $code): int
    {
        if (! isset($catalogs[$type][$code])) {
            throw new RuntimeException("El catálogo {$type} con código {$code} no está disponible. Ejecuta CatalogSeeder antes de EmployeeSeeder.");
        }

        return $catalogs[$type][$code];
    }
}
