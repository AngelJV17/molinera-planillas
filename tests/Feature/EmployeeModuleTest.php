<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class EmployeeModuleTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_employee_can_be_created_without_system_access(): void
    {
        $user = User::factory()->create();
        $documentType = $this->catalog('DOCUMENT_TYPE', 'DNI');

        $response = $this->actingAs($user)->post(route('workers.store'), [
            'document_type_id' => $documentType->id,
            'document_number' => '87654321',
            'first_name' => 'Maria',
            'last_name' => 'Lopez',
            'birth_date' => '1992-05-01',
            'email' => 'maria.lopez@example.com',
            'hire_date' => '2025-01-10',
            'base_salary' => 2200,
            'status' => true,
            'has_system_access' => false,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('workers.index', absolute: false));

        $this->assertDatabaseHas('employees', [
            'employee_code' => 'EMP-0001',
            'document_number' => '87654321',
            'user_id' => null,
        ]);
    }

    public function test_employee_with_system_access_creates_linked_user(): void
    {
        $user = User::factory()->create();
        $documentType = $this->catalog('DOCUMENT_TYPE', 'DNI');

        $this->actingAs($user)->post(route('workers.store'), [
            'document_type_id' => $documentType->id,
            'document_number' => '11223344',
            'first_name' => 'Luis',
            'last_name' => 'Ramos',
            'email' => 'luis.ramos@example.com',
            'hire_date' => '2025-02-01',
            'base_salary' => 2500,
            'status' => true,
            'has_system_access' => true,
        ])->assertSessionHasNoErrors();

        $createdUser = User::firstWhere('username', '11223344');

        $this->assertNotNull($createdUser);
        $this->assertTrue(Hash::check('11223344', $createdUser->password));
        $this->assertTrue($createdUser->must_change_password);
        $this->assertDatabaseHas('employees', [
            'document_number' => '11223344',
            'user_id' => $createdUser->id,
        ]);
    }

    public function test_employee_with_system_access_rejects_existing_user_credentials(): void
    {
        $user = User::factory()->create();
        $documentType = $this->catalog('DOCUMENT_TYPE', 'DNI');
        User::factory()->create([
            'username' => '44556677',
            'email' => 'ocupado@example.com',
        ]);

        $this->actingAs($user)->post(route('workers.store'), [
            'document_type_id' => $documentType->id,
            'document_number' => '44556677',
            'first_name' => 'Carlos',
            'last_name' => 'Rios',
            'email' => 'ocupado@example.com',
            'hire_date' => '2025-02-01',
            'base_salary' => 2500,
            'status' => true,
            'has_system_access' => true,
        ])->assertSessionHasErrors(['document_number', 'email']);
    }

    public function test_employee_validates_required_fields_and_unique_document(): void
    {
        $user = User::factory()->create();
        $documentType = $this->catalog('DOCUMENT_TYPE', 'DNI');
        $this->employee([
            'document_type_id' => $documentType->id,
            'document_number' => '99999999',
        ]);

        $response = $this->actingAs($user)->post(route('workers.store'), [
            'document_type_id' => $documentType->id,
            'document_number' => '99999999',
            'first_name' => '',
            'last_name' => '',
            'birth_date' => now()->addDay()->toDateString(),
            'hire_date' => '',
            'base_salary' => -1,
            'status' => true,
            'has_system_access' => false,
        ]);

        $response->assertSessionHasErrors([
            'document_number',
            'first_name',
            'last_name',
            'birth_date',
            'hire_date',
            'base_salary',
        ]);
    }

    public function test_employee_can_be_updated_and_disabled(): void
    {
        $user = User::factory()->create();
        $documentType = $this->catalog('DOCUMENT_TYPE', 'DNI');
        $employee = $this->employee(['document_type_id' => $documentType->id]);

        $this->actingAs($user)->put(route('workers.update', $employee), [
            'employee_code' => $employee->employee_code,
            'document_type_id' => $documentType->id,
            'document_number' => '12345678',
            'first_name' => 'Juan Carlos',
            'last_name' => 'Perez',
            'birth_date' => '1990-01-01',
            'email' => 'juan.carlos@example.com',
            'hire_date' => '2024-01-15',
            'termination_date' => null,
            'base_salary' => 2000,
            'status' => true,
            'has_system_access' => false,
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->patch(route('workers.toggle-status', $employee))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'first_name' => 'Juan Carlos',
            'status' => false,
        ]);
    }
}
