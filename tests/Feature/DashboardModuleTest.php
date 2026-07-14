<?php

namespace Tests\Feature;

use App\Models\MonthlyAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class DashboardModuleTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_uses_latest_attendance_period_when_current_period_has_no_data(): void
    {
        Carbon::setTestNow('2026-07-01 00:05:00');

        $user = User::factory()->create();
        $catalogs = $this->attendanceCatalogs();
        $employee = $this->employee(['work_shift_id' => $this->workShift()->id]);

        MonthlyAttendance::create([
            'employee_id' => $employee->id,
            'status_id' => $catalogs['closed']->id,
            'month' => 6,
            'year' => 2026,
            'worked_days' => 24,
            'absence_days' => 1,
            'rest_days' => 5,
            'closed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('Dashboard')
                ->where('filters.period', '2026-06')
                ->where('currentPeriodLabel', 'Junio 2026')
                ->where('metrics.monthly_attendances', 1)
                ->where('metrics.closed_attendances', 1)
                ->where('metrics.attendance_rate', 96)
            );
    }
}
