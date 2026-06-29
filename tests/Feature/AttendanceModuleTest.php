<?php

namespace Tests\Feature;

use App\Models\AttendanceDay;
use App\Models\MonthlyAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class AttendanceModuleTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-15 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_monthly_attendance_can_be_created_with_calendar_days(): void
    {
        $user = User::factory()->create();
        $this->attendanceCatalogs();
        $employee = $this->employee([
            'document_type_id' => $this->catalog('DOCUMENT_TYPE', 'DNI')->id,
            'work_shift_id' => $this->workShift()->id,
        ]);

        $response = $this->actingAs($user)->post(route('attendance.store'), $this->currentPeriodPayload([
            'employee_id' => $employee->id,
        ]));

        $attendance = MonthlyAttendance::first();

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('attendance.index', absolute: false));

        $this->assertSame(30, $attendance->days()->count());
        $this->assertDatabaseHas('monthly_attendances', [
            'employee_id' => $employee->id,
            'month' => 6,
            'year' => 2026,
        ]);
    }

    public function test_monthly_attendance_rejects_duplicates_and_disallowed_periods(): void
    {
        $user = User::factory()->create();
        $this->attendanceCatalogs();
        $employee = $this->employee(['work_shift_id' => $this->workShift()->id]);

        $payload = $this->currentPeriodPayload(['employee_id' => $employee->id]);

        $this->actingAs($user)->post(route('attendance.store'), $payload)->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('attendance.store'), $payload)
            ->assertSessionHasErrors('employee_id');

        $this->actingAs($user)
            ->post(route('attendance.store'), [
                'employee_id' => $employee->id,
                'month' => 1,
                'year' => 2026,
            ])
            ->assertSessionHasErrors(['period', 'month']);
    }

    public function test_monthly_attendance_requires_active_employee_with_work_shift(): void
    {
        $user = User::factory()->create();
        $this->attendanceCatalogs();
        $documentType = $this->catalog('DOCUMENT_TYPE', 'DNI');
        $inactiveEmployee = $this->employee([
            'document_type_id' => $documentType->id,
            'document_number' => '22334455',
            'employee_code' => 'EMP-0002',
            'status' => false,
            'work_shift_id' => $this->workShift()->id,
        ]);
        $employeeWithoutShift = $this->employee([
            'document_type_id' => $documentType->id,
            'document_number' => '33445566',
            'employee_code' => 'EMP-0003',
            'work_shift_id' => null,
        ]);

        $this->actingAs($user)
            ->post(route('attendance.store'), $this->currentPeriodPayload([
                'employee_id' => $inactiveEmployee->id,
            ]))
            ->assertSessionHasErrors('employee_id');

        $this->actingAs($user)
            ->post(route('attendance.store'), $this->currentPeriodPayload([
                'employee_id' => $employeeWithoutShift->id,
            ]))
            ->assertSessionHasErrors('employee_id');

        $this->assertDatabaseMissing('monthly_attendances', [
            'employee_id' => $inactiveEmployee->id,
        ]);
        $this->assertDatabaseMissing('monthly_attendances', [
            'employee_id' => $employeeWithoutShift->id,
        ]);
    }

    public function test_attendance_day_requires_times_for_present_status(): void
    {
        $user = User::factory()->create();
        $catalogs = $this->attendanceCatalogs();
        $employee = $this->employee(['work_shift_id' => $this->workShift()->id]);

        $this->actingAs($user)->post(route('attendance.store'), $this->currentPeriodPayload([
            'employee_id' => $employee->id,
        ]));

        $day = AttendanceDay::whereDate('attendance_date', '2026-06-10')->first();

        $this->actingAs($user)
            ->patch(route('attendance.days.update', $day), [
                'status_id' => $catalogs['present']->id,
            ])
            ->assertSessionHasErrors(['entry_time', 'exit_time']);
    }

    public function test_attendance_day_can_be_marked_present_and_totals_are_recalculated(): void
    {
        $user = User::factory()->create();
        $catalogs = $this->attendanceCatalogs();
        $employee = $this->employee(['work_shift_id' => $this->workShift()->id]);

        $this->actingAs($user)->post(route('attendance.store'), $this->currentPeriodPayload([
            'employee_id' => $employee->id,
        ]));

        $attendance = MonthlyAttendance::first();
        $day = AttendanceDay::whereDate('attendance_date', '2026-06-10')->first();

        $this->actingAs($user)->patch(route('attendance.days.update', $day), [
            'status_id' => $catalogs['present']->id,
            'entry_time' => '08:00',
            'exit_time' => '18:00',
            'observation' => 'Hora extra',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('attendance_days', [
            'id' => $day->id,
            'worked_hours' => 9,
            'overtime_hours' => 1,
        ]);

        $this->assertSame(1, $attendance->refresh()->worked_days);
        $this->assertSame('1.00', $attendance->overtime_hours);
    }

    public function test_bulk_update_marks_multiple_days_and_close_requires_no_unmarked_days(): void
    {
        $user = User::factory()->create();
        $catalogs = $this->attendanceCatalogs();
        $employee = $this->employee(['work_shift_id' => $this->workShift()->id]);

        $this->actingAs($user)->post(route('attendance.store'), [
            'employee_id' => $employee->id,
            'month' => 5,
            'year' => 2026,
        ])->assertSessionHasNoErrors();

        $attendance = MonthlyAttendance::first();
        $days = $attendance->days()->pluck('id')->toArray();

        $this->actingAs($user)
            ->patch(route('attendance.close', $attendance))
            ->assertSessionHasErrors('attendance');

        $this->actingAs($user)->patch(route('attendance.days.bulk-update', $attendance), [
            'day_ids' => $days,
            'status_code' => 'REST',
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->patch(route('attendance.close', $attendance))
            ->assertSessionHasNoErrors();

        $attendance->refresh();

        $this->assertSame($catalogs['closed']->id, $attendance->status_id);
        $this->assertSame(31, $attendance->rest_days);
        $this->assertSame($user->id, $attendance->closed_by);
    }
}
