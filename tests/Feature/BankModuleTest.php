<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class BankModuleTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_bank_can_be_created(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('banks.store'), [
            'name' => 'Banco Nacional',
            'code' => 'BN',
            'status' => true,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('organizational-structure.index', absolute: false));

        $this->assertDatabaseHas('banks', [
            'name' => 'Banco Nacional',
            'code' => 'BN',
            'status' => true,
        ]);
    }

    public function test_bank_requires_unique_name_and_code(): void
    {
        $user = User::factory()->create();
        $this->bank(['name' => 'Banco Repetido', 'code' => 'REP']);

        $response = $this->actingAs($user)->from(route('banks.create'))->post(route('banks.store'), [
            'name' => 'Banco Repetido',
            'code' => 'REP',
            'status' => true,
        ]);

        $response
            ->assertSessionHasErrors(['name', 'code'])
            ->assertRedirect(route('banks.create', absolute: false));
    }

    public function test_bank_can_be_updated_and_toggled(): void
    {
        $user = User::factory()->create();
        $bank = $this->bank();

        $update = $this->actingAs($user)->put(route('banks.update', $bank), [
            'name' => 'Banco Actualizado',
            'code' => 'ACT',
            'status' => true,
        ]);

        $update
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('organizational-structure.index', absolute: false));

        $toggle = $this->actingAs($user)->patch(route('banks.toggle-status', $bank));

        $toggle->assertSessionHasNoErrors();

        $this->assertDatabaseHas('banks', [
            'id' => $bank->id,
            'name' => 'Banco Actualizado',
            'code' => 'ACT',
            'status' => false,
        ]);
    }
}
