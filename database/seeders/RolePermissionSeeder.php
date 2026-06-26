<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Dashboard
            'dashboard.view',

            // Configuración general / Catálogos
            'catalogs.view',
            'catalogs.create',
            'catalogs.edit',
            'catalogs.toggle-status',

            // Bancos
            'banks.view',
            'banks.create',
            'banks.edit',
            'banks.toggle-status',

            // Turnos
            'work-shifts.view',
            'work-shifts.create',
            'work-shifts.edit',
            'work-shifts.toggle-status',

            // Trabajadores
            'workers.view',
            'workers.create',
            'workers.edit',
            'workers.toggle-status',

            // Asistencia
            'attendance.view',
            'attendance.create',
            'attendance.edit',
            'attendance.close',

            // Planillas
            'payrolls.view',
            'payrolls.create',
            'payrolls.review',
            'payrolls.approve',
            'payrolls.reject',
            'payrolls.pay',

            // Boletas
            'payment-slips.view',
            'payment-slips.generate',
            'payment-slips.download',

            // Reportes
            'reports.view',
            'reports.export',

            // Usuarios
            'users.view',
            'users.create',
            'users.edit',
            'users.toggle-status',

            // Roles y permisos
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.assign-permissions',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::firstOrCreate([
            'name'       => 'Super Admin',
            'guard_name' => 'web',
        ]);

        $administrator = Role::firstOrCreate([
            'name'       => 'Administrador',
            'guard_name' => 'web',
        ]);

        $manager = Role::firstOrCreate([
            'name'       => 'Gerente',
            'guard_name' => 'web',
        ]);

        $accountant = Role::firstOrCreate([
            'name'       => 'Contador',
            'guard_name' => 'web',
        ]);

        $humanResources = Role::firstOrCreate([
            'name'       => 'RRHH',
            'guard_name' => 'web',
        ]);

        $worker = Role::firstOrCreate([
            'name'       => 'Trabajador',
            'guard_name' => 'web',
        ]);

        $superAdmin->syncPermissions($permissions);

        $administrator->syncPermissions([
            'dashboard.view',

            'catalogs.view',
            'catalogs.create',
            'catalogs.edit',
            'catalogs.toggle-status',

            'banks.view',
            'banks.create',
            'banks.edit',
            'banks.toggle-status',

            'work-shifts.view',
            'work-shifts.create',
            'work-shifts.edit',
            'work-shifts.toggle-status',

            'workers.view',
            'workers.create',
            'workers.edit',
            'workers.toggle-status',

            'attendance.view',
            'attendance.create',
            'attendance.edit',
            'attendance.close',

            'payrolls.view',
            'payrolls.create',
            'payrolls.review',

            'payment-slips.view',
            'payment-slips.generate',
            'payment-slips.download',

            'reports.view',

            'users.view',
            'users.create',
            'users.edit',
            'users.toggle-status',

            'roles.view',
        ]);

        $manager->syncPermissions([
            'dashboard.view',

            'workers.view',
            'attendance.view',

            'payrolls.view',
            'payrolls.review',
            'payrolls.approve',
            'payrolls.reject',

            'payment-slips.view',

            'reports.view',
            'reports.export',
        ]);

        $accountant->syncPermissions([
            'dashboard.view',

            'workers.view',
            'attendance.view',

            'payrolls.view',
            'payrolls.review',
            'payrolls.pay',

            'payment-slips.view',
            'payment-slips.generate',
            'payment-slips.download',

            'reports.view',
            'reports.export',
        ]);

        $humanResources->syncPermissions([
            'dashboard.view',

            'workers.view',
            'workers.create',
            'workers.edit',
            'workers.toggle-status',

            'attendance.view',
            'attendance.create',
            'attendance.edit',

            'work-shifts.view',

            'catalogs.view',
        ]);

        $worker->syncPermissions([
            'dashboard.view',

            'payment-slips.view',
            'payment-slips.download',
        ]);

        /*
         * Asignar Super Admin al primer usuario existente.
         * Esto evita que el sistema quede sin un usuario con control total.
         */
        $firstUser = User::query()->orderBy('id')->first();

        if ($firstUser && ! $firstUser->hasRole('Super Admin')) {
            $firstUser->assignRole('Super Admin');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
