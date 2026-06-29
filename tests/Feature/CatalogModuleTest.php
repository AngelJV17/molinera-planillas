<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class CatalogModuleTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_catalog_can_be_created_and_updated(): void
    {
        $user = User::factory()->create();

        $create = $this->actingAs($user)->post(route('catalogs.store'), [
            'type' => 'WORK_AREA',
            'code' => 'PROD',
            'name' => 'Produccion',
            'description' => 'Area operativa',
            'status' => true,
        ]);

        $catalog = \App\Models\Catalog::firstWhere('code', 'PROD');

        $create
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('catalogs.index', ['type' => 'WORK_AREA'], false));

        $update = $this->actingAs($user)->put(route('catalogs.update', $catalog), [
            'type' => 'WORK_AREA',
            'code' => 'PROD',
            'name' => 'Produccion Planta',
            'description' => null,
            'status' => true,
        ]);

        $update->assertSessionHasNoErrors();

        $this->assertDatabaseHas('catalogs', [
            'id' => $catalog->id,
            'name' => 'Produccion Planta',
        ]);
    }

    public function test_catalog_code_must_be_unique_inside_same_type(): void
    {
        $user = User::factory()->create();
        $this->catalog('DOCUMENT_TYPE', 'DNI');

        $response = $this->actingAs($user)->post(route('catalogs.store'), [
            'type' => 'DOCUMENT_TYPE',
            'code' => 'DNI',
            'name' => 'Documento duplicado',
            'status' => true,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_catalog_status_can_be_toggled(): void
    {
        $user = User::factory()->create();
        $catalog = $this->catalog('POSITION', 'OPERADOR');

        $this->actingAs($user)
            ->patch(route('catalogs.toggle-status', $catalog))
            ->assertSessionHasNoErrors();

        $this->assertFalse($catalog->refresh()->status);
    }
}
