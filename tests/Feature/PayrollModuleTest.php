<?php

namespace Tests\Feature;

use App\Models\MonthlyAttendance;
use App\Models\Payroll;
use App\Models\PayrollParameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class PayrollModuleTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_payroll_can_be_generated_from_closed_attendance(): void
    {
        $user = User::factory()->create();
        $catalogs = array_merge($this->attendanceCatalogs(), $this->payrollCatalogs());
        $this->payrollParameters();
        $documentType = $this->catalog('DOCUMENT_TYPE', 'DNI');
        $pensionSystem = $this->catalog('PENSION_SYSTEM', 'ONP', ['name' => 'ONP']);
        $employee = $this->employee([
            'document_type_id' => $documentType->id,
            'pension_system_id' => $pensionSystem->id,
            'base_salary' => 3000,
        ]);

        MonthlyAttendance::create([
            'employee_id' => $employee->id,
            'status_id' => $catalogs['closed']->id,
            'month' => 5,
            'year' => 2026,
            'worked_days' => 24,
            'absence_days' => 2,
            'uncompensated_absence_days' => 2,
            'rest_days' => 4,
            'overtime_hours' => 4,
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('payrolls.store'), [
            'month' => 5,
            'year' => 2026,
            'payment_date' => '2026-05-31',
            'observations' => 'Planilla de mayo',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('payrolls.index', ['period' => '2026-05'], false));

        $payroll = Payroll::with('details.concepts')->first();
        $detail = $payroll->details->first();

        $this->assertSame('PLA-2026-05', $payroll->code);
        $this->assertSame($catalogs['in_review']->id, $payroll->status_id);
        $this->assertSame(1, $payroll->employee_count);
        $this->assertSame('2464.37', $detail->net_pay);
        $this->assertSame(5, $detail->concepts->count());
    }

    public function test_payroll_generation_requires_closed_attendances(): void
    {
        $user = User::factory()->create();
        $this->payrollCatalogs();
        $this->payrollParameters();

        $response = $this->actingAs($user)->post(route('payrolls.store'), [
            'month' => 6,
            'year' => 2026,
        ]);

        $response->assertSessionHasErrors(['period', 'month']);
        $this->assertDatabaseCount('payrolls', 0);
    }

    public function test_payroll_generation_only_requires_parameters_used_by_attended_workers(): void
    {
        $user = User::factory()->create();
        $catalogs = array_merge($this->attendanceCatalogs(), $this->payrollCatalogs());
        $documentType = $this->catalog('DOCUMENT_TYPE', 'DNI');
        $pensionSystem = $this->catalog('PENSION_SYSTEM', 'ONP', ['name' => 'ONP']);
        $employee = $this->employee([
            'document_type_id' => $documentType->id,
            'pension_system_id' => $pensionSystem->id,
            'base_salary' => 2000,
        ]);

        PayrollParameter::create([
            'code' => 'ONP_RATE',
            'name' => 'ONP_RATE',
            'value' => 0.13,
            'status' => true,
        ]);

        PayrollParameter::create([
            'code' => 'ESSALUD_RATE',
            'name' => 'ESSALUD_RATE',
            'value' => 0.09,
            'status' => true,
        ]);

        MonthlyAttendance::create([
            'employee_id' => $employee->id,
            'status_id' => $catalogs['closed']->id,
            'month' => 7,
            'year' => 2026,
            'worked_days' => 26,
            'rest_days' => 4,
            'overtime_hours' => 0,
            'closed_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('payrolls.store'), [
                'month' => 7,
                'year' => 2026,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('payrolls', 1);
    }

    public function test_payroll_generation_requires_afp_parameter_when_attended_workers_use_afp(): void
    {
        $user = User::factory()->create();
        $catalogs = array_merge($this->attendanceCatalogs(), $this->payrollCatalogs());
        $documentType = $this->catalog('DOCUMENT_TYPE', 'DNI');
        $onp = $this->catalog('PENSION_SYSTEM', 'ONP', ['name' => 'ONP']);
        $afp = $this->catalog('PENSION_SYSTEM', 'AFP_INTEGRA', ['name' => 'AFP Integra']);
        $onpEmployee = $this->employee([
            'document_type_id' => $documentType->id,
            'document_number' => '12345678',
            'employee_code' => 'EMP-ONP',
            'pension_system_id' => $onp->id,
        ]);
        $afpEmployee = $this->employee([
            'document_type_id' => $documentType->id,
            'document_number' => '87654321',
            'employee_code' => 'EMP-AFP',
            'email' => 'afp@example.com',
            'pension_system_id' => $afp->id,
        ]);

        PayrollParameter::create([
            'code' => 'ONP_RATE',
            'name' => 'ONP_RATE',
            'value' => 0.13,
            'status' => true,
        ]);

        PayrollParameter::create([
            'code' => 'ESSALUD_RATE',
            'name' => 'ESSALUD_RATE',
            'value' => 0.09,
            'status' => true,
        ]);

        foreach ([$onpEmployee, $afpEmployee] as $employee) {
            MonthlyAttendance::create([
                'employee_id' => $employee->id,
                'status_id' => $catalogs['closed']->id,
                'month' => 8,
                'year' => 2026,
                'worked_days' => 26,
                'rest_days' => 4,
                'overtime_hours' => 0,
                'closed_at' => now(),
            ]);
        }

        $response = $this->actingAs($user)->post(route('payrolls.store'), [
            'month' => 8,
            'year' => 2026,
        ]);

        $response->assertSessionHasErrors(['payroll']);
        $this->assertStringContainsString('AFP_RATE', session('errors')->first('payroll'));
        $this->assertDatabaseCount('payrolls', 0);
    }

    public function test_payroll_generation_rejects_duplicate_period(): void
    {
        $user = User::factory()->create();
        $catalogs = array_merge($this->attendanceCatalogs(), $this->payrollCatalogs());
        $this->payrollParameters();
        $employee = $this->employee();

        MonthlyAttendance::create([
            'employee_id' => $employee->id,
            'status_id' => $catalogs['closed']->id,
            'month' => 5,
            'year' => 2026,
            'worked_days' => 25,
            'rest_days' => 5,
            'closed_at' => now(),
        ]);

        $payload = ['month' => 5, 'year' => 2026];

        $this->actingAs($user)->post(route('payrolls.store'), $payload)->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('payrolls.store'), $payload)
            ->assertSessionHasErrors(['period', 'month']);
    }

    public function test_payroll_can_be_approved_and_paid(): void
    {
        $user = User::factory()->create();
        $payroll = $this->generatedPayrollForPeriod($user, 5, 2026);

        $this->actingAs($user)
            ->patch(route('payrolls.approve', $payroll))
            ->assertSessionHasNoErrors();

        $payroll->refresh();

        $this->assertSame('APPROVED', $payroll->status->code);
        $this->assertSame($user->id, $payroll->reviewed_by);

        $this->actingAs($user)
            ->patch(route('payrolls.pay', $payroll))
            ->assertSessionHasNoErrors();

        $payroll->refresh();

        $this->assertSame('PAID', $payroll->status->code);
        $this->assertSame($user->id, $payroll->paid_by);
    }

    public function test_payroll_can_not_be_paid_before_approval(): void
    {
        $user = User::factory()->create();
        $payroll = $this->generatedPayrollForPeriod($user, 5, 2026);

        $this->actingAs($user)
            ->patch(route('payrolls.pay', $payroll))
            ->assertSessionHasErrors('payroll');
    }

    private function generatedPayrollForPeriod(User $user, int $month, int $year): Payroll
    {
        $catalogs = array_merge($this->attendanceCatalogs(), $this->payrollCatalogs());
        $this->payrollParameters();
        $employee = $this->employee([
            'document_number' => (string) random_int(10000000, 99999999),
            'employee_code' => 'EMP-' . random_int(1000, 9999),
        ]);

        MonthlyAttendance::create([
            'employee_id' => $employee->id,
            'status_id' => $catalogs['closed']->id,
            'month' => $month,
            'year' => $year,
            'worked_days' => 25,
            'rest_days' => 5,
            'closed_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('payrolls.store'), [
                'month' => $month,
                'year' => $year,
            ])
            ->assertSessionHasNoErrors();

        return Payroll::firstOrFail();
    }
}
