<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class UserRoleModuleTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_user_can_be_created_with_roles_and_temporary_password(): void
    {
        $admin = User::factory()->create();
        $role = $this->role('Planillas');

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Operador Sistema',
            'username' => 'operador',
            'email' => 'operador@example.com',
            'status' => true,
            'roles' => [$role->name],
        ]);

        $created = User::firstWhere('username', 'operador');

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('temporary_password')
            ->assertRedirect(route('users.index', absolute: false));

        $this->assertTrue($created->must_change_password);
        $this->assertTrue(Hash::check(session('temporary_password'), $created->password));
        $this->assertTrue($created->hasRole($role->name));
    }

    public function test_user_validates_unique_username_email_and_required_roles(): void
    {
        $admin = User::factory()->create();
        User::factory()->create([
            'username' => 'duplicado',
            'email' => 'duplicado@example.com',
        ]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Usuario Duplicado',
            'username' => 'duplicado',
            'email' => 'duplicado@example.com',
            'status' => true,
            'roles' => [],
        ]);

        $response->assertSessionHasErrors(['username', 'email', 'roles']);
    }

    public function test_user_can_be_updated_toggled_and_password_reset(): void
    {
        $admin = User::factory()->create();
        $role = $this->role('RRHH');
        $user = User::factory()->create([
            'username' => 'rrhh',
            'must_change_password' => false,
        ]);

        $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => 'RRHH Actualizado',
            'username' => 'rrhh_actualizado',
            'email' => 'rrhh@example.com',
            'status' => true,
            'roles' => [$role->name],
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->patch(route('users.toggle-status', $user))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->patch(route('users.reset-password', $user))
            ->assertSessionHas('temporary_password');

        $user->refresh();

        $this->assertSame('rrhh_actualizado', $user->username);
        $this->assertFalse($user->status);
        $this->assertTrue($user->must_change_password);
    }

    public function test_authenticated_user_role_is_shared_with_the_layout(): void
    {
        $role = $this->role('Contador');
        $user = User::factory()->create();
        $user->assignRole($role);

        $this
            ->actingAs($user)
            ->get(route('profile.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.roles.0', 'Contador')
                ->where('auth.user.display_label', 'Contador')
            );
    }

    public function test_role_can_be_created_and_updated_with_permissions(): void
    {
        $admin = User::factory()->create();
        $view = $this->permission('workers.view');
        $edit = $this->permission('workers.edit');

        $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'Supervisor',
            'permissions' => [$view->name],
        ])->assertSessionHasNoErrors();

        $role = Role::firstWhere('name', 'Supervisor');

        $this->actingAs($admin)->put(route('roles.update', $role), [
            'name' => 'Supervisor Planta',
            'permissions' => [$view->name, $edit->name],
        ])->assertSessionHasNoErrors();

        $role->refresh();

        $this->assertSame('Supervisor Planta', $role->name);
        $this->assertTrue($role->hasPermissionTo($view->name));
        $this->assertTrue($role->hasPermissionTo($edit->name));
    }

    public function test_role_validates_unique_name_and_existing_permissions(): void
    {
        $admin = User::factory()->create();
        $this->role('Administrador');

        $response = $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'Administrador',
            'permissions' => ['missing.permission'],
        ]);

        $response->assertSessionHasErrors(['name', 'permissions.0']);
    }

    public function test_base_actor_roles_match_use_case_diagram(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertEqualsCanonicalizing([
            'Super Admin',
            'Administrador',
            'RRHH',
            'Contabilidad',
            'Gerente',
        ], Role::query()->pluck('name')->all());
    }

    public function test_actor_permission_matrix_matches_expected_restrictions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $superAdmin = Role::findByName('Super Admin');
        $administrator = Role::findByName('Administrador');
        $humanResources = Role::findByName('RRHH');
        $accounting = Role::findByName('Contabilidad');
        $manager = Role::findByName('Gerente');

        $this->assertTrue($superAdmin->hasPermissionTo('users.create'));
        $this->assertTrue($superAdmin->hasPermissionTo('roles.assign-permissions'));

        $this->assertTrue($administrator->hasPermissionTo('users.create'));
        $this->assertTrue($administrator->hasPermissionTo('catalogs.edit'));
        $this->assertFalse($administrator->hasPermissionTo('payrolls.approve'));
        $this->assertFalse($administrator->hasPermissionTo('attendance.edit'));

        $this->assertTrue($humanResources->hasPermissionTo('workers.create'));
        $this->assertTrue($humanResources->hasPermissionTo('attendance.close'));
        $this->assertFalse($humanResources->hasPermissionTo('payrolls.create'));
        $this->assertFalse($humanResources->hasPermissionTo('users.create'));

        $this->assertTrue($accounting->hasPermissionTo('payrolls.create'));
        $this->assertTrue($accounting->hasPermissionTo('payrolls.pay'));
        $this->assertTrue($accounting->hasPermissionTo('payment-slips.download'));
        $this->assertFalse($accounting->hasPermissionTo('payrolls.approve'));

        $this->assertTrue($manager->hasPermissionTo('payrolls.approve'));
        $this->assertTrue($manager->hasPermissionTo('payrolls.observe'));
        $this->assertTrue($manager->hasPermissionTo('payrolls.reject'));
        $this->assertFalse($manager->hasPermissionTo('payrolls.create'));
        $this->assertFalse($manager->hasPermissionTo('payrolls.pay'));
    }
}
