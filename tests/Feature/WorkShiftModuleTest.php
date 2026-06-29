<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class WorkShiftModuleTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_work_shift_can_be_created(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('work-shifts.store'), [
            'name' => 'Turno Manana',
            'description' => 'Primer turno',
            'start_time' => '07:00',
            'break_start_time' => '12:00',
            'break_end_time' => '13:00',
            'end_time' => '16:00',
            'tolerance_minutes' => 15,
            'daily_hours' => 8,
            'crosses_midnight' => false,
            'status' => true,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('organizational-structure.index', absolute: false));

        $this->assertDatabaseHas('work_shifts', [
            'name' => 'Turno Manana',
            'tolerance_minutes' => 15,
            'status' => true,
        ]);
    }

    public function test_work_shift_validates_required_hours_and_limits(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('work-shifts.store'), [
            'name' => '',
            'start_time' => '7am',
            'end_time' => null,
            'tolerance_minutes' => 241,
            'daily_hours' => 0,
            'crosses_midnight' => false,
            'status' => true,
        ]);

        $response->assertSessionHasErrors([
            'name',
            'start_time',
            'end_time',
            'tolerance_minutes',
            'daily_hours',
        ]);
    }

    public function test_work_shift_can_be_updated_and_toggled(): void
    {
        $user = User::factory()->create();
        $workShift = $this->workShift();

        $update = $this->actingAs($user)->put(route('work-shifts.update', $workShift), [
            'name' => 'Turno Noche',
            'description' => 'Cruza medianoche',
            'start_time' => '20:00',
            'break_start_time' => null,
            'break_end_time' => null,
            'end_time' => '04:00',
            'tolerance_minutes' => 5,
            'daily_hours' => 8,
            'crosses_midnight' => true,
            'status' => true,
        ]);

        $update
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('organizational-structure.index', absolute: false));

        $this->actingAs($user)
            ->patch(route('work-shifts.toggle-status', $workShift))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('work_shifts', [
            'id' => $workShift->id,
            'name' => 'Turno Noche',
            'crosses_midnight' => true,
            'status' => false,
        ]);
    }
}
