<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_role_can_be_created_and_updated_with_permissions(): void
    {
        $admin = User::factory()->create();
        $view = $this->permission('workers.view');
        $edit = $this->permission('workers.edit');

        $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'Supervisor',
            'permissions' => [$view->name],
        ])->assertSessionHasNoErrors();

        $role = \Spatie\Permission\Models\Role::firstWhere('name', 'Supervisor');

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
}
