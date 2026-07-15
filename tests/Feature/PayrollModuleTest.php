<?php

namespace Tests\Feature;

use App\Models\MonthlyAttendance;
use App\Models\Payroll;
use App\Models\PayrollParameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
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
        $payrollGroup = $this->catalog('PAYROLL_GROUP', 'PRODUCCION', ['name' => 'Producción']);
        $pensionSystem = $this->catalog('PENSION_SYSTEM', 'ONP', ['name' => 'ONP']);
        $employee = $this->employee([
            'document_type_id' => $documentType->id,
            'payroll_group_id' => $payrollGroup->id,
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
            'payable_overtime_hours' => 4,
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('payrolls.store'), [
            'month' => 5,
            'year' => 2026,
            'payroll_group_id' => $payrollGroup->id,
            'payment_date' => '2026-05-31',
            'observations' => 'Planilla de mayo',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('payrolls.index', ['period' => '2026-05', 'payroll_group_id' => $payrollGroup->id], false));

        $payroll = Payroll::with('details.concepts')->first();
        $detail = $payroll->details->first();

        $this->assertSame('PLA-2026-05-PRODUCCION', $payroll->code);
        $this->assertSame($payrollGroup->id, $payroll->payroll_group_id);
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
        $payrollGroup = $this->catalog('PAYROLL_GROUP', 'PRODUCCION', ['name' => 'Producción']);

        $response = $this->actingAs($user)->post(route('payrolls.store'), [
            'month' => 6,
            'year' => 2026,
            'payroll_group_id' => $payrollGroup->id,
        ]);

        $response->assertSessionHasErrors(['period', 'month', 'payroll_group_id']);
        $this->assertDatabaseCount('payrolls', 0);
    }

    public function test_payroll_generation_only_requires_parameters_used_by_attended_workers(): void
    {
        $user = User::factory()->create();
        $catalogs = array_merge($this->attendanceCatalogs(), $this->payrollCatalogs());
        $documentType = $this->catalog('DOCUMENT_TYPE', 'DNI');
        $payrollGroup = $this->catalog('PAYROLL_GROUP', 'ADMINISTRATIVA', ['name' => 'Administrativa']);
        $pensionSystem = $this->catalog('PENSION_SYSTEM', 'ONP', ['name' => 'ONP']);
        $employee = $this->employee([
            'document_type_id' => $documentType->id,
            'payroll_group_id' => $payrollGroup->id,
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
                'payroll_group_id' => $payrollGroup->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('payrolls', 1);
    }

    public function test_payroll_generation_requires_afp_parameter_when_attended_workers_use_afp(): void
    {
        $user = User::factory()->create();
        $catalogs = array_merge($this->attendanceCatalogs(), $this->payrollCatalogs());
        $documentType = $this->catalog('DOCUMENT_TYPE', 'DNI');
        $payrollGroup = $this->catalog('PAYROLL_GROUP', 'PRODUCCION', ['name' => 'Producción']);
        $onp = $this->catalog('PENSION_SYSTEM', 'ONP', ['name' => 'ONP']);
        $afp = $this->catalog('PENSION_SYSTEM', 'AFP_INTEGRA', ['name' => 'AFP Integra']);
        $onpEmployee = $this->employee([
            'document_type_id' => $documentType->id,
            'payroll_group_id' => $payrollGroup->id,
            'document_number' => '12345678',
            'employee_code' => 'EMP-ONP',
            'pension_system_id' => $onp->id,
        ]);
        $afpEmployee = $this->employee([
            'document_type_id' => $documentType->id,
            'payroll_group_id' => $payrollGroup->id,
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
            'payroll_group_id' => $payrollGroup->id,
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
        $payrollGroup = $this->catalog('PAYROLL_GROUP', 'PRODUCCION', ['name' => 'Producción']);
        $employee = $this->employee(['payroll_group_id' => $payrollGroup->id]);

        MonthlyAttendance::create([
            'employee_id' => $employee->id,
            'status_id' => $catalogs['closed']->id,
            'month' => 5,
            'year' => 2026,
            'worked_days' => 25,
            'rest_days' => 5,
            'closed_at' => now(),
        ]);

        $payload = ['month' => 5, 'year' => 2026, 'payroll_group_id' => $payrollGroup->id];

        $this->actingAs($user)->post(route('payrolls.store'), $payload)->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('payrolls.store'), $payload)
            ->assertSessionHasErrors(['period', 'month']);
    }

    public function test_payrolls_can_be_generated_separately_by_payroll_group(): void
    {
        $user = User::factory()->create();
        $catalogs = array_merge($this->attendanceCatalogs(), $this->payrollCatalogs());
        $this->payrollParameters();
        $production = $this->catalog('PAYROLL_GROUP', 'PRODUCCION', ['name' => 'Producción']);
        $administration = $this->catalog('PAYROLL_GROUP', 'ADMIN', ['name' => 'Administrativa']);
        $documentType = $this->catalog('DOCUMENT_TYPE', 'DNI');
        $productionEmployee = $this->employee([
            'document_type_id' => $documentType->id,
            'document_number' => '11111111',
            'employee_code' => 'EMP-PRO',
            'payroll_group_id' => $production->id,
        ]);
        $adminEmployee = $this->employee([
            'document_type_id' => $documentType->id,
            'document_number' => '22222222',
            'employee_code' => 'EMP-ADM',
            'email' => 'admin.area@example.com',
            'payroll_group_id' => $administration->id,
        ]);

        foreach ([[$productionEmployee, 24], [$adminEmployee, 26]] as [$employee, $workedDays]) {
            MonthlyAttendance::create([
                'employee_id' => $employee->id,
                'status_id' => $catalogs['closed']->id,
                'month' => 6,
                'year' => 2026,
                'worked_days' => $workedDays,
                'rest_days' => 4,
                'closed_at' => now(),
            ]);
        }

        $this->actingAs($user)->post(route('payrolls.store'), [
            'month' => 6,
            'year' => 2026,
            'payroll_group_id' => $production->id,
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('payrolls.store'), [
            'month' => 6,
            'year' => 2026,
            'payroll_group_id' => $administration->id,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payrolls', [
            'month' => 6,
            'year' => 2026,
            'payroll_group_id' => $production->id,
            'employee_count' => 1,
        ]);
        $this->assertDatabaseHas('payrolls', [
            'month' => 6,
            'year' => 2026,
            'payroll_group_id' => $administration->id,
            'employee_count' => 1,
        ]);
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

    public function test_reports_and_payment_slips_can_be_exported_as_excel_and_pdf(): void
    {
        $user = User::factory()->create();
        $payroll = $this->generatedPayrollForPeriod($user, 5, 2026);

        $this->actingAs($user)
            ->patch(route('payrolls.approve', $payroll))
            ->assertSessionHasNoErrors();

        $detail = $payroll->details()->firstOrFail();

        $reportExcel = $this->actingAs($user)->get(route('reports.export', [
            'type' => 'payroll_summary',
            'format' => 'xlsx',
            'period' => '2026-05',
        ]));

        $reportExcel
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $reportExcel->streamedContent());

        $reportPdf = $this->actingAs($user)->get(route('reports.export', [
            'type' => 'payroll_summary',
            'format' => 'pdf',
            'period' => '2026-05',
        ]));

        $reportPdf->assertOk();
        $this->assertStringStartsWith('%PDF', $reportPdf->getContent());

        $slipExcel = $this->actingAs($user)->get(route('payment-slips.excel', $detail));

        $slipExcel
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $slipExcel->streamedContent());

        $slipPdf = $this->actingAs($user)->get(route('payment-slips.pdf', $detail));

        $slipPdf->assertOk();
        $this->assertStringStartsWith('%PDF', $slipPdf->getContent());
    }

    public function test_report_excel_contains_expected_headers_and_rows(): void
    {
        $user = User::factory()->create();
        $payroll = $this->generatedPayrollForPeriod($user, 5, 2026);

        $response = $this->actingAs($user)->get(route('reports.export', [
            'type' => 'attendance_summary',
            'format' => 'xlsx',
            'period' => '2026-05',
        ]));

        $response->assertOk();

        $sheet = $this->sheetFromStreamedExcel($response->streamedContent());

        $this->assertSame('Resumen de asistencias', $sheet->getCell('A1')->getValue());
        $this->assertSame('Trabajador', $sheet->getCell('A4')->getValue());
        $this->assertSame('Horas extra registradas', $sheet->getCell('J4')->getValue());
        $this->assertSame('Horas extra remunerables', $sheet->getCell('K4')->getValue());
        $this->assertSame($payroll->details()->firstOrFail()->employee_name, $sheet->getCell('A5')->getValue());
    }

    public function test_payment_slip_excel_contains_worker_concepts_and_totals(): void
    {
        $user = User::factory()->create();
        $payroll = $this->generatedPayrollForPeriod($user, 5, 2026);

        $this->actingAs($user)
            ->patch(route('payrolls.approve', $payroll))
            ->assertSessionHasNoErrors();

        $detail = $payroll->details()->firstOrFail();

        $response = $this->actingAs($user)->get(route('payment-slips.excel', $detail));

        $response->assertOk();

        $sheet = $this->sheetFromStreamedExcel($response->streamedContent());

        $this->assertSame('Boleta de pago', $sheet->getCell('A1')->getValue());
        $this->assertSame('Trabajador', $sheet->getCell('A4')->getValue());
        $this->assertSame($detail->employee_name, $sheet->getCell('B4')->getValue());
        $this->assertSame('Concepto', $sheet->getCell('A9')->getValue());
        $this->assertSame('Sueldo basico', $sheet->getCell('A10')->getValue());
        $netRow = $this->findRowByCellValue($sheet, 'D', 'Neto a pagar');

        $this->assertNotNull($netRow);
        $this->assertEquals((float) $detail->net_pay, (float) $sheet->getCell("E{$netRow}")->getValue());
    }

    public function test_unapproved_payment_slips_cannot_be_downloaded(): void
    {
        $user = User::factory()->create();
        $payroll = $this->generatedPayrollForPeriod($user, 5, 2026);
        $detail = $payroll->details()->firstOrFail();

        $this->actingAs($user)
            ->get(route('payment-slips.pdf', $detail))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('payment-slips.excel', $detail))
            ->assertNotFound();
    }

    public function test_report_export_validates_type_format_and_period(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.export', [
                'type' => 'invalid',
                'format' => 'docx',
                'period' => '2026/05',
            ]))
            ->assertSessionHasErrors(['type', 'format', 'period']);
    }

    public function test_report_export_accepts_local_period_format(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.export', [
                'type' => 'payroll_summary',
                'format' => 'xlsx',
                'period' => '05-2026',
            ]))
            ->assertOk();
    }

    public function test_payroll_can_not_be_paid_before_approval(): void
    {
        $user = User::factory()->create();
        $payroll = $this->generatedPayrollForPeriod($user, 5, 2026);

        $this->actingAs($user)
            ->patch(route('payrolls.pay', $payroll))
            ->assertSessionHasErrors('payroll');
    }

    public function test_payroll_can_be_observed_reopened_and_recalculated(): void
    {
        $user = User::factory()->create();
        $payroll = $this->generatedPayrollForPeriod($user, 5, 2026);
        $attendance = MonthlyAttendance::firstOrFail();

        $this->actingAs($user)
            ->patch(route('attendance.reopen', $attendance))
            ->assertSessionHasErrors('attendance');

        $this->actingAs($user)
            ->patch(route('payrolls.observe', $payroll), [
                'reason' => 'Revisar faltas antes de aprobar.',
            ])
            ->assertSessionHasNoErrors();

        $payroll->refresh();
        $this->assertSame('OBSERVED', $payroll->status->code);
        $this->assertSame('Revisar faltas antes de aprobar.', $payroll->rejection_reason);

        $this->actingAs($user)
            ->patch(route('attendance.reopen', $attendance))
            ->assertSessionHasNoErrors();

        $this->assertTrue($attendance->refresh()->isEditable());

        $this->actingAs($user)
            ->patch(route('attendance.close', $attendance))
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->patch(route('payrolls.recalculate', $payroll))
            ->assertSessionHasNoErrors();

        $payroll->refresh();
        $this->assertSame('IN_REVIEW', $payroll->status->code);
        $this->assertNull($payroll->rejection_reason);
    }

    private function generatedPayrollForPeriod(User $user, int $month, int $year): Payroll
    {
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

        return Payroll::firstOrFail();
    }

    private function sheetFromStreamedExcel(string $content): Worksheet
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx-test-');
        file_put_contents($path, $content);

        $spreadsheet = IOFactory::load($path);
        @unlink($path);

        return $spreadsheet->getActiveSheet();
    }

    private function findRowByCellValue(Worksheet $sheet, string $column, string $value): ?int
    {
        for ($row = 1; $row <= 100; $row++) {
            if ($sheet->getCell("{$column}{$row}")->getValue() === $value) {
                return $row;
            }
        }

        return null;
    }
}
