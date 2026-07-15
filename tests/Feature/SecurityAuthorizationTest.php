<?php

namespace Tests\Feature;

use App\Models\MonthlyAttendance;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class SecurityAuthorizationTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    protected bool $grantAllPermissions = false;

    public function test_report_exports_require_explicit_permission(): void
    {
        $this->permission('reports.export');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.export', [
                'type' => 'payroll_summary',
                'format' => 'xlsx',
                'period' => '2026-05',
            ]))
            ->assertForbidden();
    }

    public function test_payment_slip_downloads_require_explicit_permission(): void
    {
        $this->permission('payment-slips.download');
        $user = User::factory()->create();
        $payroll = $this->generatedPayrollForPeriod($user, 5, 2026);
        $detail = $payroll->details()->firstOrFail();

        $this->actingAs($user)
            ->get(route('payment-slips.excel', $detail))
            ->assertForbidden();
    }

    public function test_payroll_approval_requires_explicit_permission(): void
    {
        $this->permission('payrolls.approve');
        $user = User::factory()->create();
        $payroll = $this->generatedPayrollForPeriod($user, 5, 2026);

        $this->actingAs($user)
            ->patch(route('payrolls.approve', $payroll))
            ->assertForbidden();
    }

    public function test_unauthenticated_users_are_redirected_before_accessing_protected_exports(): void
    {
        $this->get(route('reports.export', [
            'type' => 'payroll_summary',
            'format' => 'xlsx',
            'period' => '2026-05',
        ]))->assertRedirect(route('login', absolute: false));
    }

    private function generatedPayrollForPeriod(User $user, int $month, int $year)
    {
        $this->grantAllPermissions = true;

        $catalogs = array_merge($this->attendanceCatalogs(), $this->payrollCatalogs());
        $this->payrollParameters();
        $payrollGroup = $this->catalog('PAYROLL_GROUP', 'AREA-'.random_int(1000, 9999), ['name' => 'Area prueba']);
        $employee = $this->employee([
            'document_number' => (string) random_int(10000000, 99999999),
            'employee_code' => 'EMP-'.random_int(1000, 9999),
            'payroll_group_id' => $payrollGroup->id,
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
                'payroll_group_id' => $payrollGroup->id,
            ])
            ->assertSessionHasNoErrors();

        $this->grantAllPermissions = false;

        return Payroll::firstOrFail();
    }
}
