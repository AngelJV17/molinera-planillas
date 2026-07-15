<?php

namespace Tests\Feature;

use App\Models\AttendanceDay;
use App\Models\MonthlyAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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

    public function test_saturday_overtime_can_be_registered_without_being_payable(): void
    {
        $user = User::factory()->create();
        $catalogs = $this->attendanceCatalogs();
        $workShift = $this->workShift([
            'uses_daily_rules' => true,
        ]);
        $workShift->rules()->createMany(collect(range(1, 7))->map(fn (int $day) => [
            'day_of_week' => $day,
            'is_working_day' => $day !== 7,
            'start_time' => $day === 6 ? '08:00' : '08:00',
            'end_time' => $day === 6 ? '12:00' : '17:00',
            'expected_hours' => $day === 6 ? 4 : 8,
            'tolerance_minutes' => 10,
            'crosses_midnight' => false,
            'counts_as_full_day' => $day !== 7,
            'overtime_pay_enabled' => $day !== 6,
        ])->all());
        $employee = $this->employee(['work_shift_id' => $workShift->id]);

        $this->actingAs($user)->post(route('attendance.store'), $this->currentPeriodPayload([
            'employee_id' => $employee->id,
        ]));

        $attendance = MonthlyAttendance::first();
        $day = AttendanceDay::whereDate('attendance_date', '2026-06-13')->first();

        $this->actingAs($user)->patch(route('attendance.days.update', $day), [
            'status_id' => $catalogs['present']->id,
            'entry_time' => '08:00',
            'exit_time' => '14:00',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('attendance_days', [
            'id' => $day->id,
            'worked_hours' => 6,
            'overtime_hours' => 2,
            'payable_overtime_hours' => 0,
        ]);

        $attendance->refresh();
        $this->assertSame('2.00', $attendance->overtime_hours);
        $this->assertSame('0.00', $attendance->payable_overtime_hours);
    }

    public function test_rotating_night_shift_marks_cycle_rest_days(): void
    {
        $user = User::factory()->create();
        $this->attendanceCatalogs();
        $workShift = $this->workShift([
            'name' => 'Vigilancia 6x1',
            'start_time' => '18:00',
            'break_start_time' => null,
            'break_end_time' => null,
            'end_time' => '06:00',
            'daily_hours' => 12,
            'crosses_midnight' => true,
            'rotation_enabled' => true,
            'rotation_work_days' => 6,
            'rotation_rest_days' => 1,
            'rotation_start_date' => '2026-06-01',
        ]);
        $employee = $this->employee(['work_shift_id' => $workShift->id]);

        $this->actingAs($user)->post(route('attendance.store'), $this->currentPeriodPayload([
            'employee_id' => $employee->id,
        ]))->assertSessionHasNoErrors();

        $attendance = MonthlyAttendance::first();
        $restDay = AttendanceDay::whereDate('attendance_date', '2026-06-07')->first();
        $workDay = AttendanceDay::whereDate('attendance_date', '2026-06-06')->first();

        $this->assertSame(AttendanceDay::STATUS_REST, $restDay->status->code);
        $this->assertSame(AttendanceDay::STATUS_UNMARKED, $workDay->status->code);
        $this->assertSame(4, $attendance->refresh()->rest_days);
    }

    public function test_exchange_worked_day_requires_and_creates_formal_absence_exchange(): void
    {
        $user = User::factory()->create();
        $catalogs = $this->attendanceCatalogs();
        $employee = $this->employee(['work_shift_id' => $this->workShift()->id]);

        $this->actingAs($user)->post(route('attendance.store'), $this->currentPeriodPayload([
            'employee_id' => $employee->id,
        ]));

        $attendance = MonthlyAttendance::first();
        $absenceDay = AttendanceDay::whereDate('attendance_date', '2026-06-09')->first();
        $exchangeDay = AttendanceDay::whereDate('attendance_date', '2026-06-10')->first();

        $this->actingAs($user)->patch(route('attendance.days.update', $absenceDay), [
            'status_id' => $catalogs['absent']->id,
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->patch(route('attendance.days.update', $exchangeDay), [
            'status_id' => $catalogs['exchange_worked']->id,
            'entry_time' => '08:00',
            'exit_time' => '17:00',
        ])->assertSessionHasErrors('absence_attendance_day_id');

        $this->actingAs($user)->patch(route('attendance.days.update', $exchangeDay), [
            'status_id' => $catalogs['exchange_worked']->id,
            'entry_time' => '08:00',
            'exit_time' => '17:00',
            'absence_attendance_day_id' => $absenceDay->id,
            'observation' => 'Compensa falta del dia anterior',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('attendance_exchanges', [
            'employee_id' => $employee->id,
            'status_id' => $catalogs['exchange_applied']->id,
            'absence_attendance_day_id' => $absenceDay->id,
            'exchange_attendance_day_id' => $exchangeDay->id,
            'absence_date' => '2026-06-09 00:00:00',
            'exchange_date' => '2026-06-10 00:00:00',
            'registered_by' => $user->id,
        ]);

        $attendance->refresh();

        $this->assertSame(1, $attendance->absence_days);
        $this->assertSame(1, $attendance->exchange_days);
        $this->assertSame(1, $attendance->compensated_absence_days);
        $this->assertSame(0, $attendance->uncompensated_absence_days);
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

    public function test_attendance_days_can_be_imported_in_bulk_from_excel_by_payroll_group(): void
    {
        $user = User::factory()->create();
        $this->attendanceCatalogs();
        $workArea = $this->catalog('WORK_AREA', 'PRODUCTION', ['name' => 'Produccion']);
        $warehouseArea = $this->catalog('WORK_AREA', 'WAREHOUSE', ['name' => 'Almacen']);
        $otherPayrollGroup = $this->catalog('PAYROLL_GROUP', 'ADMIN', ['name' => 'Planilla administrativa']);
        $payrollGroup = $this->catalog('PAYROLL_GROUP', 'PRODUCTION', ['name' => 'Planilla personal de produccion']);
        $documentType = $this->catalog('DOCUMENT_TYPE', 'DNI');
        $workShift = $this->workShift();

        $employee = $this->employee([
            'document_type_id' => $documentType->id,
            'employee_code' => 'EMP-0001',
            'document_number' => '12345678',
            'work_area_id' => $workArea->id,
            'payroll_group_id' => $payrollGroup->id,
            'work_shift_id' => $workShift->id,
        ]);
        $secondEmployee = $this->employee([
            'document_type_id' => $documentType->id,
            'employee_code' => 'EMP-0002',
            'document_number' => '87654321',
            'email' => 'ana.perez@example.com',
            'first_name' => 'Ana',
            'work_area_id' => $warehouseArea->id,
            'payroll_group_id' => $payrollGroup->id,
            'work_shift_id' => $workShift->id,
        ]);
        $this->employee([
            'document_type_id' => $documentType->id,
            'employee_code' => 'EMP-0003',
            'document_number' => '11111111',
            'email' => 'otro@example.com',
            'work_area_id' => $warehouseArea->id,
            'payroll_group_id' => $otherPayrollGroup->id,
            'work_shift_id' => $workShift->id,
        ]);

        $file = $this->attendanceImportFile([
            [
                'documento' => '12345678',
                'nombre' => 'Juan Perez',
                'dias' => [10 => 'A', 11 => 'F'],
            ],
            [
                'documento' => '87654321',
                'nombre' => 'Ana Perez',
                'dias' => [10 => 'D'],
            ],
            [
                'documento' => '87654321',
                'nombre' => 'Ana Perez duplicada',
                'dias' => [10 => 'F'],
            ],
        ]);

        $this->actingAs($user)
            ->post(route('attendance.import-excel'), [
                'period' => '2026-06',
                'payroll_group_id' => $payrollGroup->id,
                'file' => $file,
            ])
            ->assertSessionHasNoErrors();

        $attendance = MonthlyAttendance::query()
            ->where('employee_id', $employee->id)
            ->firstOrFail();
        $secondAttendance = MonthlyAttendance::query()
            ->where('employee_id', $secondEmployee->id)
            ->firstOrFail();

        $this->assertDatabaseHas('attendance_days', [
            'monthly_attendance_id' => $attendance->id,
            'attendance_date' => '2026-06-10 00:00:00',
            'worked_hours' => 8,
            'overtime_hours' => 0,
        ]);

        $attendance->refresh();
        $this->assertSame(1, $attendance->worked_days);
        $this->assertSame(1, $attendance->absence_days);
        $this->assertSame(1, $secondAttendance->rest_days);
        $this->assertSame(2, MonthlyAttendance::count());
    }

    public function test_bulk_attendance_import_rejects_invalid_rows_without_creating_attendance(): void
    {
        $user = User::factory()->create();
        $this->attendanceCatalogs();
        $workArea = $this->catalog('WORK_AREA', 'PRODUCTION', ['name' => 'Produccion']);
        $otherArea = $this->catalog('WORK_AREA', 'ADMIN', ['name' => 'Administrativa']);
        $payrollGroup = $this->catalog('PAYROLL_GROUP', 'PRODUCTION', ['name' => 'Planilla personal de produccion']);
        $otherPayrollGroup = $this->catalog('PAYROLL_GROUP', 'ADMIN', ['name' => 'Planilla administrativa']);
        $documentType = $this->catalog('DOCUMENT_TYPE', 'DNI');
        $workShift = $this->workShift();

        $this->employee([
            'document_type_id' => $documentType->id,
            'employee_code' => 'EMP-INACTIVO',
            'document_number' => '78523691',
            'email' => 'inactivo@example.com',
            'work_area_id' => $workArea->id,
            'payroll_group_id' => $payrollGroup->id,
            'work_shift_id' => $workShift->id,
            'status' => false,
        ]);

        $this->employee([
            'document_type_id' => $documentType->id,
            'employee_code' => 'EMP-OTRA-AREA',
            'document_number' => '79632584',
            'email' => 'otra.area@example.com',
            'work_area_id' => $otherArea->id,
            'payroll_group_id' => $otherPayrollGroup->id,
            'work_shift_id' => $workShift->id,
        ]);

        $file = $this->attendanceImportFile([
            [
                'documento' => '99999999',
                'nombre' => 'Trabajador no registrado',
                'dias' => [10 => 'A'],
            ],
            [
                'documento' => '78523691',
                'nombre' => 'Trabajador inactivo',
                'dias' => [10 => 'A'],
            ],
            [
                'documento' => '79632584',
                'nombre' => 'Trabajador otra area',
                'dias' => [10 => 'A'],
            ],
        ]);

        $response = $this->actingAs($user)
            ->post(route('attendance.import-excel'), [
                'period' => '2026-06',
                'payroll_group_id' => $payrollGroup->id,
                'file' => $file,
            ])
            ->assertSessionHasErrors(['file']);

        $message = session('errors')->first('file');
        $this->assertStringContainsString('el DNI 99999999 no esta registrado en trabajadores', $message);
        $this->assertStringContainsString('el DNI 78523691 pertenece a Juan Perez, pero el trabajador esta inactivo', $message);
        $this->assertStringContainsString('el DNI 79632584 pertenece a Juan Perez, pero su grupo de planilla actual es Planilla administrativa', $message);

        $this->assertSame(0, MonthlyAttendance::count());
        $this->assertDatabaseMissing('attendance_days', [
            'attendance_date' => '2026-06-10 00:00:00',
        ]);
    }

    public function test_bulk_attendance_template_is_generated_for_selected_period_and_payroll_group(): void
    {
        $user = User::factory()->create();
        $productionArea = $this->catalog('WORK_AREA', 'PRODUCTION', ['name' => 'Produccion']);
        $warehouseArea = $this->catalog('WORK_AREA', 'WAREHOUSE', ['name' => 'Almacen']);
        $payrollGroup = $this->catalog('PAYROLL_GROUP', 'PRODUCTION', ['name' => 'Planilla personal de produccion']);
        $otherPayrollGroup = $this->catalog('PAYROLL_GROUP', 'ADMIN', ['name' => 'Planilla administrativa']);
        $documentType = $this->catalog('DOCUMENT_TYPE', 'DNI');
        $workShift = $this->workShift(['name' => 'Turno Manana']);

        $this->employee([
            'document_type_id' => $documentType->id,
            'employee_code' => 'EMP-0001',
            'document_number' => '78523691',
            'first_name' => 'Carlos Enrique',
            'last_name' => 'Vasquez Paredes',
            'work_area_id' => $productionArea->id,
            'payroll_group_id' => $payrollGroup->id,
            'work_shift_id' => $workShift->id,
        ]);

        $this->employee([
            'document_type_id' => $documentType->id,
            'employee_code' => 'EMP-0002',
            'document_number' => '79632584',
            'email' => 'almacen@example.com',
            'first_name' => 'Ricardo Jose',
            'last_name' => 'Herrera Silva',
            'work_area_id' => $warehouseArea->id,
            'payroll_group_id' => $payrollGroup->id,
            'work_shift_id' => $workShift->id,
        ]);

        $this->employee([
            'document_type_id' => $documentType->id,
            'employee_code' => 'EMP-0003',
            'document_number' => '11111111',
            'email' => 'admin.template@example.com',
            'work_area_id' => $productionArea->id,
            'payroll_group_id' => $otherPayrollGroup->id,
            'work_shift_id' => $workShift->id,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.import-template', [
            'period' => '2026-07',
            'payroll_group_id' => $payrollGroup->id,
        ]));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $sheet = $this->sheetFromStreamedExcel($response->streamedContent(), 'Asistencia');

        $this->assertSame('Formato de asistencia masiva', $sheet->getCell('A1')->getValue());
        $this->assertStringContainsString('DD-MM-YYYY', $sheet->getCell('A3')->getValue());
        $this->assertSame('documento', $sheet->getCell('A5')->getValue());
        $this->assertSame('Mie', $sheet->getCell('F4')->getValue());
        $this->assertSame('01', $sheet->getCell('F5')->getValue());
        $this->assertSame('31', (string) $sheet->getCell('AJ5')->getValue());
        $this->assertSame('79632584', (string) $sheet->getCell('A6')->getValue());
        $this->assertSame('Almacen', $sheet->getCell('D6')->getValue());
        $this->assertSame('78523691', (string) $sheet->getCell('A7')->getValue());
        $this->assertNull($sheet->getCell('A8')->getValue());
    }

    public function test_bulk_attendance_import_ignores_auxiliary_placeholder_rows(): void
    {
        $user = User::factory()->create();
        $this->attendanceCatalogs();
        $workArea = $this->catalog('WORK_AREA', 'PRODUCTION', ['name' => 'Produccion']);
        $payrollGroup = $this->catalog('PAYROLL_GROUP', 'PRODUCTION', ['name' => 'Planilla personal de produccion']);
        $documentType = $this->catalog('DOCUMENT_TYPE', 'DNI');
        $workShift = $this->workShift();

        $employee = $this->employee([
            'document_type_id' => $documentType->id,
            'document_number' => '12345678',
            'work_area_id' => $workArea->id,
            'payroll_group_id' => $payrollGroup->id,
            'work_shift_id' => $workShift->id,
        ]);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Asistencia');
        $sheet->fromArray(['documento', 'apellidos_nombres', 'cargo', 'area', 'turno', '01'], null, 'A1');
        $sheet->fromArray(['12345678', 'Juan Perez', '', 'Produccion', 'Turno Dia', 'A'], null, 'A2');

        $overtimeSheet = $spreadsheet->createSheet();
        $overtimeSheet->setTitle('Horas extras');
        $overtimeSheet->fromArray([
            ['documento', 'fecha', 'entrada', 'salida', 'observacion'],
            ['Ej: 78523691', 'DD-MM-YYYY', '18:00', '20:00', 'Opcional'],
        ], null, 'A1');

        $exchangeSheet = $spreadsheet->createSheet();
        $exchangeSheet->setTitle('Canjes');
        $exchangeSheet->fromArray([
            ['documento', 'fecha_canje', 'compensa_fecha', 'entrada', 'salida', 'observacion'],
            ['Ej: 78523691', 'DD-MM-YYYY', 'DD-MM-YYYY', '08:00', '17:00', 'Opcional'],
        ], null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'attendance-placeholder-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        $file = new UploadedFile(
            $path,
            'asistencia-placeholder.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->actingAs($user)
            ->post(route('attendance.import-excel'), [
                'period' => '2026-06',
                'payroll_group_id' => $payrollGroup->id,
                'file' => $file,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('monthly_attendances', [
            'employee_id' => $employee->id,
            'month' => 6,
            'year' => 2026,
        ]);
    }

    public function test_bulk_attendance_import_accepts_peruvian_date_format_in_auxiliary_sheets(): void
    {
        $user = User::factory()->create();
        $this->attendanceCatalogs();
        $workArea = $this->catalog('WORK_AREA', 'PRODUCTION', ['name' => 'Produccion']);
        $payrollGroup = $this->catalog('PAYROLL_GROUP', 'PRODUCTION', ['name' => 'Planilla personal de produccion']);
        $documentType = $this->catalog('DOCUMENT_TYPE', 'DNI');
        $workShift = $this->workShift();

        $employee = $this->employee([
            'document_type_id' => $documentType->id,
            'document_number' => '12345678',
            'work_area_id' => $workArea->id,
            'payroll_group_id' => $payrollGroup->id,
            'work_shift_id' => $workShift->id,
        ]);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Asistencia');
        $sheet->fromArray(['documento', 'apellidos_nombres', 'cargo', 'area', 'turno', '01'], null, 'A1');
        $sheet->fromArray(['12345678', 'Juan Perez', '', 'Produccion', 'Turno Dia', 'A'], null, 'A2');

        $overtimeSheet = $spreadsheet->createSheet();
        $overtimeSheet->setTitle('Horas extras');
        $overtimeSheet->fromArray([
            ['documento', 'fecha', 'entrada', 'salida', 'observacion'],
            ['12345678', '01-06-2026', '18:00', '20:00', 'Prueba formato peruano'],
        ], null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'attendance-date-format-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        $file = new UploadedFile(
            $path,
            'asistencia-fecha-peruana.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->actingAs($user)
            ->post(route('attendance.import-excel'), [
                'period' => '2026-06',
                'payroll_group_id' => $payrollGroup->id,
                'file' => $file,
            ])
            ->assertSessionHasNoErrors();

        $attendance = MonthlyAttendance::query()
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        $this->assertDatabaseHas('attendance_days', [
            'monthly_attendance_id' => $attendance->id,
            'attendance_date' => '2026-06-01 00:00:00',
            'entry_time' => '18:00',
            'exit_time' => '20:00',
        ]);
    }

    private function attendanceImportFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Asistencia');

        $headers = ['documento', 'apellidos_nombres', 'cargo', 'area', 'turno'];
        for ($day = 1; $day <= 31; $day++) {
            $headers[] = str_pad((string) $day, 2, '0', STR_PAD_LEFT);
        }
        $headers[] = 'observacion_general';
        $sheet->fromArray($headers, null, 'A1');

        $matrixRows = [];
        foreach ($rows as $row) {
            $matrixRow = [
                $row['documento'],
                $row['nombre'] ?? '',
                $row['cargo'] ?? '',
                $row['area'] ?? '',
                $row['turno'] ?? '',
            ];

            for ($day = 1; $day <= 31; $day++) {
                $matrixRow[] = $row['dias'][$day] ?? '';
            }

            $matrixRow[] = $row['observacion'] ?? '';
            $matrixRows[] = $matrixRow;
        }

        $sheet->fromArray($matrixRows, null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'attendance-import-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile(
            $path,
            'asistencia.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    private function sheetFromStreamedExcel(string $content, string $sheetName): Worksheet
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx-test-');
        file_put_contents($path, $content);

        $spreadsheet = IOFactory::load($path);
        @unlink($path);

        return $spreadsheet->getSheetByName($sheetName) ?? $spreadsheet->getActiveSheet();
    }
}
