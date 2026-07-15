<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
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
            'attendance.reopen',

            'attendance-exchanges.view',

            'payrolls.view',
            'payrolls.create',
            'payrolls.review',
            'payrolls.approve',
            'payrolls.observe',
            'payrolls.reject',
            'payrolls.recalculate',
            'payrolls.pay',

            'payroll-parameters.view',
            'payroll-parameters.create',
            'payroll-parameters.edit',
            'payroll-parameters.toggle-status',

            'payment-slips.view',
            'payment-slips.generate',
            'payment-slips.download',

            'reports.view',
            'reports.export',

            'users.view',
            'users.create',
            'users.edit',
            'users.toggle-status',

            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.assign-permissions',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $administrator = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $humanResources = Role::firstOrCreate(['name' => 'RRHH', 'guard_name' => 'web']);
        $accounting = Role::firstOrCreate(['name' => 'Contabilidad', 'guard_name' => 'web']);
        $manager = Role::firstOrCreate(['name' => 'Gerente', 'guard_name' => 'web']);

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

            'payroll-parameters.view',
            'payroll-parameters.create',
            'payroll-parameters.edit',
            'payroll-parameters.toggle-status',

            'users.view',
            'users.create',
            'users.edit',
            'users.toggle-status',

            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.assign-permissions',

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
            'attendance.close',
            'attendance.reopen',

            'attendance-exchanges.view',

            'work-shifts.view',

            'reports.view',
            'reports.export',
        ]);

        $accounting->syncPermissions([
            'dashboard.view',

            'workers.view',
            'attendance.view',
            'attendance-exchanges.view',

            'payrolls.view',
            'payrolls.create',
            'payrolls.review',
            'payrolls.observe',
            'payrolls.recalculate',
            'payrolls.pay',

            'payroll-parameters.view',
            'payroll-parameters.edit',

            'payment-slips.view',
            'payment-slips.generate',
            'payment-slips.download',

            'reports.view',
            'reports.export',
        ]);

        $manager->syncPermissions([
            'dashboard.view',

            'workers.view',
            'attendance.view',
            'attendance-exchanges.view',

            'payrolls.view',
            'payrolls.review',
            'payrolls.approve',
            'payrolls.observe',
            'payrolls.reject',

            'payment-slips.view',

            'reports.view',
            'reports.export',
        ]);

        Role::query()
            ->whereIn('name', ['Contador', 'Trabajador'])
            ->each(fn (Role $role) => $role->delete());

        $superAdminUser = User::updateOrCreate(
            ['email' => 'admin@molicente.com'],
            [
                'name' => 'Super Administrador',
                'username' => 'admin',
                'email' => 'admin@molicente.com',
                'password' => Hash::make('Admin123456*'),
                'must_change_password' => true,
                'email_verified_at' => now(),
            ]
        );

        $superAdminUser->syncRoles([$superAdmin]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
