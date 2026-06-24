<?php
namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = [
            [
                'employee_code'        => 'EMP0001',
                'document_type_id'     => 1,
                'document_number'      => '72145896',
                'first_name'           => 'Juan Carlos',
                'last_name'            => 'Ramirez Flores',
                'birth_date'           => '1990-05-15',
                'phone'                => '987654321',
                'email'                => 'juan.ramirez@molicente.com',
                'address'              => 'Jr. Los Laureles 245, Amarilis',
                'hire_date'            => '2025-01-10',
                'work_shift_id'        => 1,
                'employment_status_id' => 3,
                'base_salary'          => 1800,
                'pension_system_id'    => 5,
                'status'               => true,
            ], [
                'employee_code'        => 'EMP0002',
                'document_type_id'     => 1,
                'document_number'      => '74125896',
                'first_name'           => 'Pedro Luis',
                'last_name'            => 'Torres Vega',
                'birth_date'           => '1988-08-22',
                'phone'                => '986321547',
                'email'                => 'pedro.torres@molicente.com',
                'address'              => 'Av. Universitaria 512, Huánuco',
                'hire_date'            => '2025-01-12',
                'work_shift_id'        => 1,
                'employment_status_id' => 3,
                'base_salary'          => 1700,
                'pension_system_id'    => 4,
                'status'               => true,
            ],

            [
                'employee_code'        => 'EMP0003',
                'document_type_id'     => 1,
                'document_number'      => '75236984',
                'first_name'           => 'Miguel Angel',
                'last_name'            => 'Soto Rojas',
                'birth_date'           => '1992-01-18',
                'phone'                => '985214785',
                'email'                => 'miguel.soto@molicente.com',
                'address'              => 'Jr. Dos de Mayo 879, Huánuco',
                'hire_date'            => '2025-01-15',
                'work_shift_id'        => 1,
                'employment_status_id' => 3,
                'base_salary'          => 1800,
                'pension_system_id'    => 5,
                'status'               => true,
            ],

            [
                'employee_code'        => 'EMP0004',
                'document_type_id'     => 1,
                'document_number'      => '76325874',
                'first_name'           => 'Luis Alberto',
                'last_name'            => 'Mendoza Diaz',
                'birth_date'           => '1987-11-04',
                'phone'                => '984785236',
                'email'                => 'luis.mendoza@molicente.com',
                'address'              => 'Jr. Tarapacá 345, Amarilis',
                'hire_date'            => '2025-02-01',
                'work_shift_id'        => 1,
                'employment_status_id' => 3,
                'base_salary'          => 1900,
                'pension_system_id'    => 4,
                'status'               => true,
            ],

            [
                'employee_code'        => 'EMP0005',
                'document_type_id'     => 1,
                'document_number'      => '77412589',
                'first_name'           => 'Jorge Antonio',
                'last_name'            => 'Castillo Ruiz',
                'birth_date'           => '1991-03-09',
                'phone'                => '983654128',
                'email'                => 'jorge.castillo@molicente.com',
                'address'              => 'Jr. Abtao 152, Huánuco',
                'hire_date'            => '2025-02-10',
                'work_shift_id'        => 1,
                'employment_status_id' => 3,
                'base_salary'          => 1750,
                'pension_system_id'    => 5,
                'status'               => true,
            ],

            [
                'employee_code'        => 'EMP0006',
                'document_type_id'     => 1,
                'document_number'      => '78523691',
                'first_name'           => 'Carlos Enrique',
                'last_name'            => 'Vasquez Paredes',
                'birth_date'           => '1989-09-30',
                'phone'                => '982145369',
                'email'                => 'carlos.vasquez@molicente.com',
                'address'              => 'Av. Perú 723, Amarilis',
                'hire_date'            => '2025-02-15',
                'work_shift_id'        => 1,
                'employment_status_id' => 3,
                'base_salary'          => 1850,
                'pension_system_id'    => 4,
                'status'               => true,
            ],

            [
                'employee_code'        => 'EMP0007',
                'document_type_id'     => 1,
                'document_number'      => '79632584',
                'first_name'           => 'Ricardo Jose',
                'last_name'            => 'Herrera Silva',
                'birth_date'           => '1993-07-11',
                'phone'                => '981258741',
                'email'                => 'ricardo.herrera@molicente.com',
                'address'              => 'Jr. Huallayco 567, Huánuco',
                'hire_date'            => '2025-03-01',
                'work_shift_id'        => 1,
                'employment_status_id' => 3,
                'base_salary'          => 1800,
                'pension_system_id'    => 5,
                'status'               => true,
            ],

            [
                'employee_code'        => 'EMP0008',
                'document_type_id'     => 1,
                'document_number'      => '70741258',
                'first_name'           => 'Manuel Jesus',
                'last_name'            => 'Salazar Torres',
                'birth_date'           => '1986-12-19',
                'phone'                => '980741258',
                'email'                => 'manuel.salazar@molicente.com',
                'address'              => 'Jr. San Martín 901, Amarilis',
                'hire_date'            => '2025-03-05',
                'work_shift_id'        => 1,
                'employment_status_id' => 3,
                'base_salary'          => 1700,
                'pension_system_id'    => 4,
                'status'               => true,
            ],

            [
                'employee_code'        => 'EMP0009',
                'document_type_id'     => 1,
                'document_number'      => '71852369',
                'first_name'           => 'Fernando David',
                'last_name'            => 'Lopez Ramos',
                'birth_date'           => '1994-04-02',
                'phone'                => '979852147',
                'email'                => 'fernando.lopez@molicente.com',
                'address'              => 'Jr. Independencia 421, Huánuco',
                'hire_date'            => '2025-03-10',
                'work_shift_id'        => 1,
                'employment_status_id' => 3,
                'base_salary'          => 1800,
                'pension_system_id'    => 5,
                'status'               => true,
            ],

            [
                'employee_code'        => 'EMP0010',
                'document_type_id'     => 1,
                'document_number'      => '72987415',
                'first_name'           => 'Roberto',
                'last_name'            => 'Quispe Mendoza',
                'birth_date'           => '1985-06-25',
                'phone'                => '978963258',
                'email'                => 'roberto.quispe@molicente.com',
                'address'              => 'Jr. Leoncio Prado 210, Amarilis',
                'hire_date'            => '2025-01-01',
                'work_shift_id'        => 2,
                'employment_status_id' => 3,
                'base_salary'          => 2200,
                'pension_system_id'    => 5,
                'status'               => true,
            ],

        ];

        foreach ($employees as $employee) {
            Employee::create($employee);
        }
    }
}
