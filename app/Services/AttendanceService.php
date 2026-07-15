<?php

namespace App\Services;

use App\Models\AttendanceDay;
use App\Models\AttendanceExchange;
use App\Models\Catalog;
use App\Models\Employee;
use App\Models\MonthlyAttendance;
use App\Models\WorkShift;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AttendanceService
{
    /**
     * Lista las asistencias mensuales aplicando filtros.
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $search = trim($filters['search'] ?? '');
        $statusId = $filters['status_id'] ?? null;
        $month = $filters['month'] ?? null;
        $year = $filters['year'] ?? null;
        $perPage = (int) ($filters['per_page'] ?? 10);

        return MonthlyAttendance::query()
            ->with(['employee', 'status'])
            ->when($month, function (Builder $query) use ($month) {
                $query->where('month', (int) $month);
            })
            ->when($year, function (Builder $query) use ($year) {
                $query->where('year', (int) $year);
            })
            ->when($statusId, function (Builder $query) use ($statusId) {
                $query->where('status_id', (int) $statusId);
            })
            ->when($search !== '', function (Builder $query) use ($search) {
                $this->applyEmployeeSearch($query, $search);
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Crea una asistencia mensual para un trabajador.
     *
     * Al crear la cabecera mensual también se generan automáticamente
     * todos los días del calendario con estado "Sin marcar".
     */
    public function createMonthlyAttendance(array $data, ?int $userId = null): MonthlyAttendance
    {
        return DB::transaction(function () use ($data, $userId) {
            $this->ensureAllowedPeriod(
                (int) $data['month'],
                (int) $data['year']
            );

            $exists = MonthlyAttendance::query()
                ->where('employee_id', $data['employee_id'])
                ->where('month', $data['month'])
                ->where('year', $data['year'])
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'employee_id' => 'Este trabajador ya tiene asistencia registrada para el periodo seleccionado. Abre el registro existente para revisarlo o editarlo. [ATT-001]',
                ]);
            }

            $draftStatusId = $this->catalogId(
                MonthlyAttendance::CATALOG_TYPE_STATUS,
                MonthlyAttendance::STATUS_DRAFT
            );

            $attendance = MonthlyAttendance::create([
                'employee_id' => $data['employee_id'],
                'status_id' => $draftStatusId,
                'month' => $data['month'],
                'year' => $data['year'],
                'observations' => $data['observations'] ?? null,
                'created_by' => $userId,
            ]);

            $this->generateMonthDays($attendance);
            $this->recalculateTotals($attendance);

            return $attendance;
        });
    }

    /**
     * Actualiza un día del calendario.
     *
     * Si el día se marca como Asistió o Trabajó como canje,
     * se exige turno asignado al trabajador y se calculan
     * automáticamente las horas trabajadas y horas extras.
     */
    public function updateDay(AttendanceDay $day, array $data): AttendanceDay
    {
        return DB::transaction(function () use ($day, $data) {
            $day->load([
                'monthlyAttendance.status',
                'monthlyAttendance.employee.workShift',
                'absenceExchange',
                'workedExchange',
            ]);

            $this->ensureDayCanBeUpdated($day);

            $status = $this->attendanceDayStatus((int) $data['status_id']);
            $statusCode = $status->code;

            if ($statusCode !== AttendanceDay::STATUS_ABSENT) {
                $day->absenceExchange()->delete();
            }

            if ($this->isWorkedStatus($statusCode)) {
                $workShift = $this->resolveEmployeeWorkShift($day);

                $calculatedHours = $this->calculateWorkedAndOvertimeHours(
                    attendanceDate: $day->attendance_date,
                    entryTime: $data['entry_time'] ?? null,
                    exitTime: $data['exit_time'] ?? null,
                    workShift: $workShift
                );

                $day->update([
                    'status_id' => $status->id,
                    'work_shift_id' => $workShift->id,
                    'entry_time' => $data['entry_time'],
                    'exit_time' => $data['exit_time'],
                    'worked_hours' => $calculatedHours['worked_hours'],
                    'overtime_hours' => $calculatedHours['overtime_hours'],
                    'payable_overtime_hours' => $calculatedHours['payable_overtime_hours'],
                    'observation' => $data['observation'] ?? null,
                ]);

                if ($statusCode === AttendanceDay::STATUS_EXCHANGE_WORKED) {
                    $this->syncExchangeForWorkedDay(
                        workedDay: $day->refresh(),
                        absenceDayId: (int) ($data['absence_attendance_day_id'] ?? 0)
                    );
                } else {
                    $day->workedExchange()->delete();
                }
            } else {
                $day->workedExchange()->delete();

                $day->update([
                    'status_id' => $status->id,
                    'work_shift_id' => null,
                    'entry_time' => null,
                    'exit_time' => null,
                    'worked_hours' => 0,
                    'overtime_hours' => 0,
                    'payable_overtime_hours' => 0,
                    'observation' => $data['observation'] ?? null,
                ]);
            }

            $this->recalculateTotals($day->monthlyAttendance);

            return $day->refresh();
        });
    }

    private function syncExchangeForWorkedDay(AttendanceDay $workedDay, int $absenceDayId): void
    {
        $workedDay->loadMissing('monthlyAttendance');

        $absenceDay = AttendanceDay::query()
            ->with(['status', 'monthlyAttendance'])
            ->where('id', $absenceDayId)
            ->first();

        if (! $absenceDay) {
            throw ValidationException::withMessages([
                'absence_attendance_day_id' => 'Selecciona una falta valida para compensar con este dia trabajado. [ATT-015]',
            ]);
        }

        if ((int) $absenceDay->id === (int) $workedDay->id) {
            throw ValidationException::withMessages([
                'absence_attendance_day_id' => 'El dia trabajado como canje no puede compensarse a si mismo. Selecciona una falta diferente. [ATT-016]',
            ]);
        }

        if ((int) $absenceDay->monthly_attendance_id !== (int) $workedDay->monthly_attendance_id) {
            throw ValidationException::withMessages([
                'absence_attendance_day_id' => 'La falta seleccionada no pertenece a esta asistencia mensual. Selecciona una falta del mismo trabajador y periodo. [ATT-017]',
            ]);
        }

        if ($absenceDay->status?->code !== AttendanceDay::STATUS_ABSENT) {
            throw ValidationException::withMessages([
                'absence_attendance_day_id' => 'Solo puedes compensar dias marcados como Falto. Selecciona una falta valida. [ATT-018]',
            ]);
        }

        $existingAbsenceExchange = AttendanceExchange::query()
            ->where('absence_attendance_day_id', $absenceDay->id)
            ->where('exchange_attendance_day_id', '!=', $workedDay->id)
            ->exists();

        if ($existingAbsenceExchange) {
            throw ValidationException::withMessages([
                'absence_attendance_day_id' => 'Esta falta ya esta vinculada a otro canje. Selecciona una falta pendiente. [ATT-019]',
            ]);
        }

        $appliedStatusId = $this->catalogId(
            AttendanceExchange::CATALOG_TYPE_STATUS,
            AttendanceExchange::STATUS_APPLIED
        );

        AttendanceExchange::query()->updateOrCreate(
            [
                'exchange_attendance_day_id' => $workedDay->id,
            ],
            [
                'employee_id' => $workedDay->monthlyAttendance->employee_id,
                'status_id' => $appliedStatusId,
                'absence_attendance_day_id' => $absenceDay->id,
                'absence_date' => $absenceDay->attendance_date?->toDateString(),
                'exchange_date' => $workedDay->attendance_date?->toDateString(),
                'observation' => $workedDay->observation,
                'registered_by' => auth()->id(),
            ]
        );
    }

    /**
     * Actualiza varios días del calendario con un mismo estado.
     *
     * Esta función está pensada para carga rápida desde cuaderno:
     * - Seleccionar varios días.
     * - Marcar todos como Asistió, Faltó o Descanso.
     *
     * Cuando el estado es Asistió, se usa automáticamente el turno
     * asignado al trabajador para registrar ingreso, salida, horas
     * trabajadas y horas extras.
     */
    public function bulkUpdateDays(MonthlyAttendance $attendance, array $dayIds, string $statusCode): void
    {
        DB::transaction(function () use ($attendance, $dayIds, $statusCode) {
            $attendance->load([
                'status',
                'employee.workShift',
            ]);

            if (! $attendance->isEditable()) {
                throw ValidationException::withMessages([
                    'day_ids' => 'La asistencia mensual ya esta cerrada y no puede modificarse. Si necesitas corregirla, solicita la reapertura del periodo. [ATT-002]',
                ]);
            }

            $allowedStatusCodes = [
                AttendanceDay::STATUS_PRESENT,
                AttendanceDay::STATUS_ABSENT,
                AttendanceDay::STATUS_REST,
            ];

            if (! in_array($statusCode, $allowedStatusCodes, true)) {
                throw ValidationException::withMessages([
                    'status_code' => 'Para actualizar varios dias a la vez, elige Asistio, Falto o Descanso. Otros estados deben registrarse dia por dia. [ATT-003]',
                ]);
            }

            $uniqueDayIds = collect($dayIds)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $days = $attendance->days()
                ->whereIn('id', $uniqueDayIds)
                ->get();

            if ($days->count() !== $uniqueDayIds->count()) {
                throw ValidationException::withMessages([
                    'day_ids' => 'Algunos dias seleccionados no pertenecen a esta asistencia mensual. Recarga la pagina y vuelve a seleccionar los dias. [ATT-004]',
                ]);
            }

            $statusId = $this->catalogId(
                AttendanceDay::CATALOG_TYPE_STATUS,
                $statusCode
            );

            foreach ($days as $day) {
                $day->setRelation('monthlyAttendance', $attendance);

                $this->ensureDayCanBeUpdated($day);

                if ($statusCode === AttendanceDay::STATUS_PRESENT) {
                    $workShift = $this->resolveEmployeeWorkShift($day);
                    $schedule = $this->workScheduleForDate($workShift, $day->attendance_date->toDateString());

                    $entryTime = $schedule['start_time'];
                    $exitTime = $schedule['end_time'];

                    $calculatedHours = $this->calculateWorkedAndOvertimeHours(
                        attendanceDate: $day->attendance_date,
                        entryTime: $entryTime,
                        exitTime: $exitTime,
                        workShift: $workShift
                    );

                    $day->update([
                        'status_id' => $statusId,
                        'work_shift_id' => $workShift->id,
                        'entry_time' => $entryTime,
                        'exit_time' => $exitTime,
                        'worked_hours' => $calculatedHours['worked_hours'],
                        'overtime_hours' => $calculatedHours['overtime_hours'],
                        'payable_overtime_hours' => $calculatedHours['payable_overtime_hours'],
                        'observation' => $day->observation,
                    ]);

                    continue;
                }

                $day->update([
                    'status_id' => $statusId,
                    'work_shift_id' => null,
                    'entry_time' => null,
                    'exit_time' => null,
                    'worked_hours' => 0,
                    'overtime_hours' => 0,
                    'payable_overtime_hours' => 0,
                    'observation' => $day->observation,
                ]);
            }

            $this->recalculateTotals($attendance->refresh());
        });
    }

    public function importBulkFromSpreadsheet(array $data, string $path, ?int $userId = null): array
    {
        return DB::transaction(function () use ($data, $path, $userId) {
            $period = $this->parsePeriod($data['period']);
            $month = (int) $period['month'];
            $year = (int) $period['year'];
            $payrollGroupId = (int) $data['payroll_group_id'];

            $this->ensureAllowedPeriod($month, $year);

            $spreadsheet = IOFactory::load($path);
            $worksheet = $spreadsheet->getSheetByName('Asistencia') ?? $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray(null, true, true, true);

            if (count($rows) < 2) {
                throw ValidationException::withMessages([
                    'file' => 'El archivo no contiene filas de asistencia para importar. [ATT-022]',
                ]);
            }

            [$header, $rows] = $this->spreadsheetHeaderAndDataRows($rows);

            if (! array_key_exists('documento', $header)) {
                throw ValidationException::withMessages([
                    'file' => 'La hoja Asistencia debe incluir la columna documento. [ATT-023]',
                ]);
            }

            $dayColumns = $this->spreadsheetDayColumns($header, $month, $year);

            if ($dayColumns === []) {
                throw ValidationException::withMessages([
                    'file' => 'La hoja Asistencia debe incluir columnas de dias con encabezados 01, 02, 03... segun el mes. [ATT-033]',
                ]);
            }

            $employees = Employee::query()
                ->with(['workShift.rules', 'position', 'workArea', 'payrollGroup'])
                ->where('payroll_group_id', $payrollGroupId)
                ->where('status', true)
                ->get();

            $selectedPayrollGroupName = Catalog::query()
                ->where('id', $payrollGroupId)
                ->value('name') ?? 'el grupo de planilla seleccionado';

            $employeesByDocument = Employee::query()
                ->with(['workArea', 'payrollGroup'])
                ->get()
                ->filter(fn (Employee $employee) => filled($employee->document_number))
                ->keyBy(fn (Employee $employee) => $this->normalizeSpreadsheetKey($employee->document_number));

            $byDocument = $employees
                ->filter(fn (Employee $employee) => filled($employee->document_number))
                ->keyBy(fn (Employee $employee) => $this->normalizeSpreadsheetKey($employee->document_number));

            $rowsByKey = [];
            $seenDocuments = [];
            $errors = [];
            $skipped = 0;

            foreach ($rows as $rowNumber => $row) {
                $document = $this->normalizeSpreadsheetKey($this->spreadsheetCell($row, $header, 'documento'));

                if (! $document && $this->spreadsheetRowIsEmpty($row)) {
                    $skipped++;

                    continue;
                }

                if (! $document) {
                    $errors[] = "Fila {$rowNumber}: falta el DNI del trabajador.";

                    continue;
                }

                if (isset($seenDocuments[$document])) {
                    $skipped++;

                    continue;
                }
                $seenDocuments[$document] = true;

                $employee = $byDocument->get($document);

                if (! $employee) {
                    $errors[] = "Fila {$rowNumber}: ".$this->employeeImportLookupMessage($document, $employeesByDocument, $selectedPayrollGroupName);

                    continue;
                }

                if (! $employee->work_shift_id) {
                    $errors[] = "Fila {$rowNumber}: {$employee->full_name} no tiene un turno asignado.";

                    continue;
                }

                $observation = $this->spreadsheetCell($row, $header, 'observacion_general')
                    ?? $this->spreadsheetCell($row, $header, 'observacion');

                foreach ($dayColumns as $column => $day) {
                    $rawStatus = $this->spreadsheetCellByColumn($row, $column);

                    if ($rawStatus === null) {
                        continue;
                    }

                    try {
                        $statusCode = $this->normalizeMatrixStatusCode($rawStatus);
                    } catch (ValidationException $exception) {
                        $errors[] = "Fila {$rowNumber}, dia ".str_pad((string) $day, 2, '0', STR_PAD_LEFT).': '.$this->firstValidationMessage($exception);

                        continue;
                    }

                    $attendanceDate = Carbon::create($year, $month, $day)->toDateString();
                    $rowKey = "{$document}|{$attendanceDate}";
                    $rowsByKey[$rowKey] = [
                        'row' => $rowNumber,
                        'employee' => $employee,
                        'attendance_date' => $attendanceDate,
                        'status_code' => $statusCode,
                        'entry_time' => null,
                        'exit_time' => null,
                        'observation' => $observation,
                        'compensated_date' => null,
                    ];
                }
            }

            $this->mergeSpreadsheetOvertimeRows($spreadsheet, $rowsByKey, $byDocument, $employeesByDocument, $selectedPayrollGroupName, $month, $year, $errors, $skipped);
            $this->mergeSpreadsheetExchangeRows($spreadsheet, $rowsByKey, $byDocument, $employeesByDocument, $selectedPayrollGroupName, $month, $year, $errors, $skipped);

            foreach ($rowsByKey as $validatedRow) {
                if ($validatedRow['status_code'] === AttendanceDay::STATUS_EXCHANGE_WORKED && ! $validatedRow['compensated_date']) {
                    $errors[] = 'Fila '.$validatedRow['row'].': el codigo C requiere registrar el detalle en la hoja Canjes.';
                }
            }

            if ($errors !== []) {
                throw ValidationException::withMessages([
                    'file' => $this->spreadsheetErrorMessage($errors),
                ]);
            }

            if ($rowsByKey === []) {
                throw ValidationException::withMessages([
                    'file' => 'El archivo no contiene marcas de asistencia validas para importar. Revisa que tenga DNI y codigos A, F, D, C o S en los dias. [ATT-031]',
                ]);
            }

            $imported = 0;
            $attendances = [];

            foreach ($rowsByKey as $validatedRow) {
                /** @var Employee $employee */
                $employee = $validatedRow['employee'];

                if (! isset($attendances[$employee->id])) {
                    $existingAttendance = MonthlyAttendance::query()
                        ->with(['status'])
                        ->where('employee_id', $employee->id)
                        ->where('month', $month)
                        ->where('year', $year)
                        ->first();

                    if ($existingAttendance && ! $existingAttendance->isEditable()) {
                        throw ValidationException::withMessages([
                            'file' => "La asistencia de {$employee->full_name} ya esta cerrada. Reabre el periodo antes de importar cambios. [ATT-021]",
                        ]);
                    }
                }

                $attendance = $attendances[$employee->id] ??= $this->draftAttendanceForImport(
                    employee: $employee,
                    month: $month,
                    year: $year,
                    userId: $userId
                );

                $day = $attendance->days
                    ->first(fn (AttendanceDay $candidate) => $candidate->attendance_date->toDateString() === $validatedRow['attendance_date']);

                if (! $day) {
                    throw ValidationException::withMessages([
                        'file' => 'La fila '.$validatedRow['row'].' no pudo ubicarse en el calendario mensual del trabajador. [ATT-030]',
                    ]);
                }

                $data = [
                    'status_id' => $this->catalogId(AttendanceDay::CATALOG_TYPE_STATUS, $validatedRow['status_code']),
                    'entry_time' => $validatedRow['entry_time'],
                    'exit_time' => $validatedRow['exit_time'],
                    'observation' => $validatedRow['observation'],
                ];

                if ($this->isWorkedStatus($validatedRow['status_code']) && (! $data['entry_time'] || ! $data['exit_time'])) {
                    $schedule = $this->workScheduleForDate($employee->workShift, $validatedRow['attendance_date']);
                    $data['entry_time'] = $schedule['start_time'];
                    $data['exit_time'] = $schedule['end_time'];
                }

                if ($validatedRow['status_code'] === AttendanceDay::STATUS_EXCHANGE_WORKED) {
                    $absenceDay = $attendance->days
                        ->first(fn (AttendanceDay $candidate) => $candidate->attendance_date->toDateString() === $validatedRow['compensated_date']);

                    $data['absence_attendance_day_id'] = $absenceDay?->id;
                }

                $this->updateDay($day, $data);
                $attendance->load('days.status');
                $imported++;
            }

            return [
                'attendances' => count($attendances),
                'imported' => $imported,
                'skipped' => $skipped,
            ];
        });
    }

    public function attendanceImportTemplate(string $period, int $payrollGroupId): Spreadsheet
    {
        $periodParts = $this->parsePeriod($period);
        $year = (int) $periodParts['year'];
        $month = (int) $periodParts['month'];
        $periodDate = Carbon::create($year, $month, 1);
        $daysInMonth = $periodDate->daysInMonth;

        $payrollGroup = Catalog::query()
            ->where('id', $payrollGroupId)
            ->where('type', 'PAYROLL_GROUP')
            ->where('status', true)
            ->firstOrFail();

        $employees = Employee::query()
            ->with(['position', 'workArea', 'workShift'])
            ->where('payroll_group_id', $payrollGroupId)
            ->where('status', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Asistencia');

        $fixedHeaders = ['documento', 'apellidos_nombres', 'cargo', 'area', 'turno'];
        $lastColumnIndex = count($fixedHeaders) + $daysInMonth + 1;
        $lastColumn = Coordinate::stringFromColumnIndex($lastColumnIndex);

        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->setCellValue('A1', 'Formato de asistencia masiva');
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->setCellValue('A2', 'Periodo: '.$this->monthName($month)." {$year} | Grupo: {$payrollGroup->name}");
        $sheet->mergeCells("A3:{$lastColumn}3");
        $sheet->setCellValue('A3', 'Codigos permitidos: A=Asistio, F=Falto, D=Descanso, C=Canje, S=Sin marcar. En Horas extras y Canjes usa fechas DD-MM-YYYY.');

        $headers = $fixedHeaders;
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $headers[] = str_pad((string) $day, 2, '0', STR_PAD_LEFT);
        }
        $headers[] = 'observacion_general';

        $weekdayRow = array_fill(0, count($fixedHeaders), '');
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $weekdayRow[] = str($periodDate->copy()->day($day)->locale('es')->translatedFormat('D'))
                ->ascii()
                ->replace('.', '')
                ->ucfirst()
                ->toString();
        }
        $weekdayRow[] = '';

        $sheet->fromArray($weekdayRow, null, 'A4');
        $sheet->fromArray($headers, null, 'A5');

        $rowNumber = 6;
        foreach ($employees as $employee) {
            $row = [
                $employee->document_number,
                $employee->full_name,
                $employee->position?->name ?? '',
                $employee->workArea?->name ?? '',
                $employee->workShift?->name ?? '',
            ];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $row[] = '';
            }

            $row[] = '';
            $sheet->fromArray($row, null, "A{$rowNumber}");
            $rowNumber++;
        }

        $this->addTemplateAuxiliarySheets($spreadsheet);

        $lastRow = max($rowNumber - 1, 6);
        $headerRange = "A5:{$lastColumn}5";
        $weekdayRange = "A4:{$lastColumn}4";
        $tableRange = "A5:{$lastColumn}{$lastRow}";

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FF111827');
        $sheet->getStyle('A2:A3')->getFont()->setSize(10)->getColor()->setARGB('FF475569');
        $sheet->getStyle($weekdayRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FF334155']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2E8F0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0F766E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE5E7EB');
        $sheet->getStyle("A6:E{$lastRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
        $sheet->getStyle("F6:{$lastColumn}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('F6');
        $sheet->setAutoFilter($tableRange);

        for ($column = 1; $column <= $lastColumnIndex; $column++) {
            $letter = Coordinate::stringFromColumnIndex($column);
            $sheet->getColumnDimension($letter)->setAutoSize($column <= 5 || $column === $lastColumnIndex);
            if ($column > 5 && $column < $lastColumnIndex) {
                $sheet->getColumnDimension($letter)->setWidth(5);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Verifica si un día del calendario puede modificarse.
     */
    private function ensureDayCanBeUpdated(AttendanceDay $day): void
    {
        $day->loadMissing('monthlyAttendance.status');

        if (! $day->monthlyAttendance->isEditable()) {
            throw ValidationException::withMessages([
                'status_id' => 'La asistencia mensual ya esta cerrada y no puede modificarse. Si necesitas corregirla, solicita la reapertura del periodo. [ATT-002]',
            ]);
        }

        if ($day->attendance_date->startOfDay()->greaterThan(now()->startOfDay())) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Todavia no puedes registrar asistencia para una fecha futura. Selecciona una fecha de hoy o anterior. [ATT-005]',
                'status_id' => 'Todavia no puedes registrar asistencia para una fecha futura. Selecciona una fecha de hoy o anterior. [ATT-005]',
            ]);
        }
    }

    private function spreadsheetHeader(array $row): array
    {
        $header = [];

        foreach ($row as $column => $value) {
            $key = str((string) $value)
                ->ascii()
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->toString();

            if ($key !== '') {
                $header[$key] = $column;
            }
        }

        return $header;
    }

    private function spreadsheetHeaderAndDataRows(array $rows): array
    {
        foreach ($rows as $rowNumber => $row) {
            $header = $this->spreadsheetHeader($row);

            if (array_key_exists('documento', $header)) {
                return [
                    $header,
                    array_filter(
                        $rows,
                        fn (int|string $key) => (int) $key > (int) $rowNumber,
                        ARRAY_FILTER_USE_KEY
                    ),
                ];
            }
        }

        return [[], []];
    }

    private function addTemplateAuxiliarySheets(Spreadsheet $spreadsheet): void
    {
        $overtimeSheet = $spreadsheet->createSheet();
        $overtimeSheet->setTitle('Horas extras');
        $overtimeSheet->fromArray([
            ['documento', 'fecha', 'entrada', 'salida', 'observacion'],
        ], null, 'A1');
        $overtimeSheet->getStyle('B:B')->getNumberFormat()->setFormatCode('dd-mm-yyyy');

        $exchangeSheet = $spreadsheet->createSheet();
        $exchangeSheet->setTitle('Canjes');
        $exchangeSheet->fromArray([
            ['documento', 'fecha_canje', 'compensa_fecha', 'entrada', 'salida', 'observacion'],
        ], null, 'A1');
        $exchangeSheet->getStyle('B:C')->getNumberFormat()->setFormatCode('dd-mm-yyyy');

        foreach ([$overtimeSheet, $exchangeSheet] as $sheet) {
            $lastColumn = $sheet->getHighestColumn();
            $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0F766E']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet->getStyle("A1:{$lastColumn}1")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE5E7EB');

            foreach (range('A', $lastColumn) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
        }
    }

    private function spreadsheetCell(array $row, array $header, string $key): ?string
    {
        $column = $header[$key] ?? null;

        if (! $column) {
            return null;
        }

        $value = $row[$column] ?? null;

        return $value === null || $value === '' ? null : trim((string) $value);
    }

    private function spreadsheetCellByColumn(array $row, string $column): ?string
    {
        $value = $row[$column] ?? null;

        return $value === null || $value === '' ? null : trim((string) $value);
    }

    private function spreadsheetRowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function spreadsheetRowIsExamplePlaceholder(array $row): bool
    {
        $values = collect($row)
            ->map(fn ($value) => str((string) $value)->ascii()->lower()->trim()->toString())
            ->filter()
            ->values();

        if ($values->isEmpty()) {
            return false;
        }

        return $values->contains(fn (string $value) => str_contains($value, 'ej:'))
            || $values->contains(fn (string $value) => str_contains($value, 'yyyy-mm-dd'))
            || $values->contains(fn (string $value) => str_contains($value, 'dd-mm-yyyy'))
            || $values->contains(fn (string $value) => str_contains($value, 'dd/mm/yyyy'));
    }

    private function spreadsheetDayColumns(array $header, int $month, int $year): array
    {
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $columns = [];

        foreach ($header as $key => $column) {
            if (! preg_match('/^(0?[1-9]|[12][0-9]|3[01])$/', $key)) {
                continue;
            }

            $day = (int) $key;

            if ($day >= 1 && $day <= $daysInMonth) {
                $columns[$column] = $day;
            }
        }

        ksort($columns);

        return $columns;
    }

    private function mergeSpreadsheetOvertimeRows(
        Spreadsheet $spreadsheet,
        array &$rowsByKey,
        Collection $byDocument,
        Collection $employeesByDocument,
        string $selectedPayrollGroupName,
        int $month,
        int $year,
        array &$errors,
        int &$skipped
    ): void {
        $worksheet = $spreadsheet->getSheetByName('Horas extras');

        if (! $worksheet) {
            return;
        }

        $rows = $worksheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            return;
        }

        $header = $this->spreadsheetHeader(array_shift($rows));

        foreach (['documento', 'fecha', 'entrada', 'salida'] as $column) {
            if (! array_key_exists($column, $header)) {
                $errors[] = "Hoja Horas extras: falta la columna {$column}.";

                return;
            }
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $document = $this->normalizeSpreadsheetKey($this->spreadsheetCell($row, $header, 'documento'));
            $dateValue = $this->spreadsheetCell($row, $header, 'fecha');

            if (! $document && ! $dateValue && $this->spreadsheetRowIsEmpty($row)) {
                $skipped++;

                continue;
            }

            if ($this->spreadsheetRowIsExamplePlaceholder($row)) {
                $skipped++;

                continue;
            }

            try {
                $attendanceDate = $this->parseSpreadsheetDate($dateValue);
                $entryTime = $this->normalizeSpreadsheetTime($this->spreadsheetCell($row, $header, 'entrada'));
                $exitTime = $this->normalizeSpreadsheetTime($this->spreadsheetCell($row, $header, 'salida'));
            } catch (ValidationException $exception) {
                $errors[] = "Hoja Horas extras fila {$rowNumber}: ".$this->firstValidationMessage($exception);

                continue;
            }

            $employee = $document ? $byDocument->get($document) : null;
            if (! $attendanceDate || ! $entryTime || ! $exitTime) {
                $errors[] = "Hoja Horas extras fila {$rowNumber}: documento, fecha, entrada y salida son obligatorios.";

                continue;
            }

            $date = Carbon::parse($attendanceDate);

            if (! $employee) {
                $errors[] = "Hoja Horas extras fila {$rowNumber}: ".$this->employeeImportLookupMessage($document, $employeesByDocument, $selectedPayrollGroupName);

                continue;
            }

            if ($date->month !== $month || $date->year !== $year) {
                $errors[] = "Hoja Horas extras fila {$rowNumber}: la fecha no pertenece al periodo seleccionado.";

                continue;
            }

            $key = "{$document}|{$attendanceDate}";
            $rowsByKey[$key] = array_merge($rowsByKey[$key] ?? [
                'row' => $rowNumber,
                'employee' => $employee,
                'attendance_date' => $attendanceDate,
                'status_code' => AttendanceDay::STATUS_PRESENT,
                'entry_time' => null,
                'exit_time' => null,
                'observation' => null,
                'compensated_date' => null,
            ], [
                'status_code' => ($rowsByKey[$key]['status_code'] ?? AttendanceDay::STATUS_PRESENT) === AttendanceDay::STATUS_EXCHANGE_WORKED
                    ? AttendanceDay::STATUS_EXCHANGE_WORKED
                    : AttendanceDay::STATUS_PRESENT,
                'entry_time' => $entryTime,
                'exit_time' => $exitTime,
                'observation' => $this->spreadsheetCell($row, $header, 'observacion') ?? ($rowsByKey[$key]['observation'] ?? null),
            ]);
        }
    }

    private function mergeSpreadsheetExchangeRows(
        Spreadsheet $spreadsheet,
        array &$rowsByKey,
        Collection $byDocument,
        Collection $employeesByDocument,
        string $selectedPayrollGroupName,
        int $month,
        int $year,
        array &$errors,
        int &$skipped
    ): void {
        $worksheet = $spreadsheet->getSheetByName('Canjes');

        if (! $worksheet) {
            return;
        }

        $rows = $worksheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            return;
        }

        $header = $this->spreadsheetHeader(array_shift($rows));

        foreach (['documento', 'fecha_canje', 'compensa_fecha'] as $column) {
            if (! array_key_exists($column, $header)) {
                $errors[] = "Hoja Canjes: falta la columna {$column}.";

                return;
            }
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $document = $this->normalizeSpreadsheetKey($this->spreadsheetCell($row, $header, 'documento'));
            $exchangeDateValue = $this->spreadsheetCell($row, $header, 'fecha_canje');

            if (! $document && ! $exchangeDateValue && $this->spreadsheetRowIsEmpty($row)) {
                $skipped++;

                continue;
            }

            if ($this->spreadsheetRowIsExamplePlaceholder($row)) {
                $skipped++;

                continue;
            }

            try {
                $exchangeDate = $this->parseSpreadsheetDate($exchangeDateValue);
                $compensatedDate = $this->parseSpreadsheetDate($this->spreadsheetCell($row, $header, 'compensa_fecha'));
                $entryTime = $this->normalizeSpreadsheetTime($this->spreadsheetCell($row, $header, 'entrada'));
                $exitTime = $this->normalizeSpreadsheetTime($this->spreadsheetCell($row, $header, 'salida'));
            } catch (ValidationException $exception) {
                $errors[] = "Hoja Canjes fila {$rowNumber}: ".$this->firstValidationMessage($exception);

                continue;
            }

            $employee = $document ? $byDocument->get($document) : null;
            if (! $exchangeDate || ! $compensatedDate) {
                $errors[] = "Hoja Canjes fila {$rowNumber}: documento, fecha_canje y compensa_fecha son obligatorios.";

                continue;
            }

            $date = Carbon::parse($exchangeDate);
            $absenceDate = Carbon::parse($compensatedDate);

            if (! $employee) {
                $errors[] = "Hoja Canjes fila {$rowNumber}: ".$this->employeeImportLookupMessage($document, $employeesByDocument, $selectedPayrollGroupName);

                continue;
            }

            if ($date->month !== $month || $date->year !== $year || $absenceDate->month !== $month || $absenceDate->year !== $year) {
                $errors[] = "Hoja Canjes fila {$rowNumber}: fecha_canje y compensa_fecha deben pertenecer al periodo seleccionado.";

                continue;
            }

            $key = "{$document}|{$exchangeDate}";
            $rowsByKey[$key] = array_merge($rowsByKey[$key] ?? [
                'row' => $rowNumber,
                'employee' => $employee,
                'attendance_date' => $exchangeDate,
                'status_code' => AttendanceDay::STATUS_EXCHANGE_WORKED,
                'entry_time' => null,
                'exit_time' => null,
                'observation' => null,
                'compensated_date' => null,
            ], [
                'status_code' => AttendanceDay::STATUS_EXCHANGE_WORKED,
                'entry_time' => $entryTime,
                'exit_time' => $exitTime,
                'observation' => $this->spreadsheetCell($row, $header, 'observacion') ?? ($rowsByKey[$key]['observation'] ?? null),
                'compensated_date' => $compensatedDate,
            ]);

            $absenceKey = "{$document}|{$compensatedDate}";
            $rowsByKey[$absenceKey] = array_merge($rowsByKey[$absenceKey] ?? [
                'row' => $rowNumber,
                'employee' => $employee,
                'attendance_date' => $compensatedDate,
                'status_code' => AttendanceDay::STATUS_ABSENT,
                'entry_time' => null,
                'exit_time' => null,
                'observation' => 'Falta compensada por canje.',
                'compensated_date' => null,
            ], [
                'status_code' => AttendanceDay::STATUS_ABSENT,
            ]);
        }
    }

    private function draftAttendanceForImport(Employee $employee, int $month, int $year, ?int $userId): MonthlyAttendance
    {
        $attendance = MonthlyAttendance::query()
            ->with(['status', 'days.status'])
            ->where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if ($attendance) {
            if (! $attendance->isEditable()) {
                throw ValidationException::withMessages([
                    'file' => "La asistencia de {$employee->full_name} ya esta cerrada. Reabre el periodo antes de importar cambios. [ATT-021]",
                ]);
            }

            return $attendance;
        }

        $attendance = MonthlyAttendance::create([
            'employee_id' => $employee->id,
            'status_id' => $this->catalogId(MonthlyAttendance::CATALOG_TYPE_STATUS, MonthlyAttendance::STATUS_DRAFT),
            'month' => $month,
            'year' => $year,
            'observations' => 'Creada por importacion masiva de asistencia.',
            'created_by' => $userId,
        ]);

        $attendance->setRelation('employee', $employee);
        $this->generateMonthDays($attendance);
        $this->recalculateTotals($attendance);

        return $attendance->refresh()->load(['status', 'days.status']);
    }

    private function normalizeSpreadsheetKey(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $normalized = str($value)->ascii()->upper()->replaceMatches('/[^A-Z0-9]+/', '')->toString();

        return $normalized === '' ? null : $normalized;
    }

    private function employeeImportLookupMessage(
        string $document,
        Collection $employeesByDocument,
        string $selectedPayrollGroupName
    ): string {
        /** @var Employee|null $employee */
        $employee = $employeesByDocument->get($document);

        if (! $employee) {
            return "el DNI {$document} no esta registrado en trabajadores.";
        }

        if (! $employee->status) {
            return "el DNI {$document} pertenece a {$employee->full_name}, pero el trabajador esta inactivo. Activalo o retira esa fila del Excel.";
        }

        $employeeGroup = $employee->payrollGroup?->name ?? 'sin grupo de planilla asignado';
        $employeeArea = $employee->workArea?->name ?? 'sin area';

        return "el DNI {$document} pertenece a {$employee->full_name}, pero su grupo de planilla actual es {$employeeGroup} (area: {$employeeArea}). Grupo seleccionado para importar: {$selectedPayrollGroupName}. Selecciona el grupo correcto o corrige la ficha del trabajador.";
    }

    private function firstValidationMessage(ValidationException $exception): string
    {
        $messages = collect($exception->errors())->flatten();

        return (string) ($messages->first() ?? $exception->getMessage());
    }

    private function spreadsheetErrorMessage(array $errors): string
    {
        $visibleErrors = array_slice($errors, 0, 8);
        $message = 'Corrige el Excel y vuelve a subirlo. No se importo ninguna asistencia. '.implode(' ', $visibleErrors);

        if (count($errors) > count($visibleErrors)) {
            $message .= ' Hay '.(count($errors) - count($visibleErrors)).' error(es) adicional(es).';
        }

        return $message.' [ATT-032]';
    }

    private function normalizeStatusCode(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $normalized = str($value)->ascii()->upper()->replaceMatches('/[^A-Z0-9]+/', '_')->trim('_')->toString();

        return match ($normalized) {
            'PRESENT', 'ASISTIO', 'ASISTIO_' => AttendanceDay::STATUS_PRESENT,
            'ABSENT', 'FALTO', 'FALTA' => AttendanceDay::STATUS_ABSENT,
            'REST', 'DESCANSO' => AttendanceDay::STATUS_REST,
            'EXCHANGE_WORKED', 'CANJE', 'TRABAJO_COMO_CANJE', 'TRABAJADO_COMO_CANJE' => AttendanceDay::STATUS_EXCHANGE_WORKED,
            'UNMARKED', 'SIN_MARCAR' => AttendanceDay::STATUS_UNMARKED,
            default => throw ValidationException::withMessages([
                'file' => "El estado {$value} no es valido para importar asistencia. [ATT-025]",
            ]),
        };
    }

    private function normalizeMatrixStatusCode(?string $value): string
    {
        if (! $value) {
            throw ValidationException::withMessages([
                'file' => 'El estado esta vacio.',
            ]);
        }

        $normalized = str($value)->ascii()->upper()->replaceMatches('/[^A-Z0-9]+/', '_')->trim('_')->toString();

        return match ($normalized) {
            'A', 'ASISTIO', 'ASISTENCIA', 'PRESENT' => AttendanceDay::STATUS_PRESENT,
            'F', 'FALTO', 'FALTA', 'ABSENT' => AttendanceDay::STATUS_ABSENT,
            'D', 'DESCANSO', 'REST' => AttendanceDay::STATUS_REST,
            'C', 'CANJE', 'EXCHANGE_WORKED' => AttendanceDay::STATUS_EXCHANGE_WORKED,
            'S', 'SM', 'SIN_MARCAR', 'UNMARKED' => AttendanceDay::STATUS_UNMARKED,
            default => throw ValidationException::withMessages([
                'file' => "El codigo {$value} no es valido. Usa A, F, D, C o S.",
            ]),
        };
    }

    private function parseSpreadsheetDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $value = trim((string) $value);

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
            } catch (\Throwable) {
                // Continua con los formatos de texto para devolver el mensaje amigable.
            }
        }

        foreach (['d-m-Y', 'd/m/Y', 'j-n-Y', 'j/n/Y', 'Y-m-d', 'Y/m/d', 'Y-n-j', 'Y/n/j'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $value);
                $errors = Carbon::getLastErrors();

                if ($date && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                    return $date->toDateString();
                }
            } catch (\Throwable) {
                continue;
            }
        }

        throw ValidationException::withMessages([
            'file' => "La fecha {$value} no tiene un formato valido. Usa DD-MM-YYYY, por ejemplo 16-07-2026. Tambien aceptamos DD/MM/YYYY. [ATT-026]",
        ]);
    }

    private function normalizeSpreadsheetTime(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (preg_match('/^\d{1,2}:\d{2}/', $value)) {
            return substr(str_pad($value, 5, '0', STR_PAD_LEFT), 0, 5);
        }

        try {
            return Carbon::parse($value)->format('H:i');
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'file' => "La hora {$value} no tiene un formato valido. Usa HH:MM. [ATT-027]",
            ]);
        }
    }

    /**
     * Cierra una asistencia mensual.
     */
    public function close(MonthlyAttendance $attendance, ?int $userId = null): MonthlyAttendance
    {
        return DB::transaction(function () use ($attendance, $userId) {
            $attendance->load(['status', 'days.status']);

            $periodEndDate = Carbon::create($attendance->year, $attendance->month, 1)
                ->endOfMonth()
                ->startOfDay();

            if ($periodEndDate->greaterThan(now()->startOfDay())) {
                throw ValidationException::withMessages([
                    'attendance' => 'Aun no puedes cerrar esta asistencia mensual porque el periodo todavia no termina. Intentalo al finalizar el mes. [ATT-006]',
                ]);
            }

            if ($attendance->isClosed()) {
                return $attendance;
            }

            $unmarkedDays = $attendance->days
                ->filter(fn (AttendanceDay $day) => $day->status?->code === AttendanceDay::STATUS_UNMARKED)
                ->count();

            if ($unmarkedDays > 0) {
                throw ValidationException::withMessages([
                    'attendance' => "Faltan {$unmarkedDays} dias por marcar. Completa todos los dias antes de cerrar la asistencia mensual. [ATT-007]",
                ]);
            }

            $closedStatusId = $this->catalogId(
                MonthlyAttendance::CATALOG_TYPE_STATUS,
                MonthlyAttendance::STATUS_CLOSED
            );

            $this->recalculateTotals($attendance);

            $attendance->update([
                'status_id' => $closedStatusId,
                'closed_by' => $userId,
                'closed_at' => now(),
            ]);

            return $attendance->refresh();
        });
    }

    public function reopen(MonthlyAttendance $attendance): MonthlyAttendance
    {
        return DB::transaction(function () use ($attendance) {
            $attendance->loadMissing(['status', 'payrollDetail.payroll.status']);

            if (! $attendance->isClosed()) {
                return $attendance;
            }

            $payroll = $attendance->payrollDetail?->payroll;

            if ($payroll && ! $payroll->isObserved() && ! $payroll->isRejected()) {
                throw ValidationException::withMessages([
                    'attendance' => 'Esta asistencia ya pertenece a una planilla aprobada, pagada o en revision. Solo puede reabrirse si la planilla fue observada o rechazada. [ATT-020]',
                ]);
            }

            $draftStatusId = $this->catalogId(
                MonthlyAttendance::CATALOG_TYPE_STATUS,
                MonthlyAttendance::STATUS_DRAFT
            );

            $attendance->update([
                'status_id' => $draftStatusId,
                'closed_by' => null,
                'closed_at' => null,
            ]);

            return $attendance->refresh();
        });
    }

    /**
     * Devuelve los estados mensuales disponibles.
     */
    public function monthlyStatuses()
    {
        return Catalog::query()
            ->where('type', MonthlyAttendance::CATALOG_TYPE_STATUS)
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }

    /**
     * Devuelve los estados diarios disponibles para el calendario.
     */
    public function dayStatuses()
    {
        return Catalog::query()
            ->where('type', AttendanceDay::CATALOG_TYPE_STATUS)
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }

    /**
     * Devuelve trabajadores activos para el selector.
     */
    public function employeeOptions()
    {
        $query = Employee::query();

        if (Schema::hasColumn('employees', 'status')) {
            $query->where('status', true);
        }

        if (Schema::hasColumn('employees', 'name')) {
            $query->orderBy('name');
        } elseif (Schema::hasColumn('employees', 'full_name')) {
            $query->orderBy('full_name');
        } elseif (Schema::hasColumn('employees', 'first_name')) {
            $query->orderBy('first_name');
        } else {
            $query->orderBy('id');
        }

        return $query
            ->get()
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'name' => $this->employeeDisplayName($employee),
                'code' => $this->employeeCode($employee),
                'document' => $this->employeeDocument($employee),
            ])
            ->values();
    }

    /**
     * Opciones de meses.
     */
    public function monthOptions(): array
    {
        return [
            ['value' => 1, 'label' => 'Enero'],
            ['value' => 2, 'label' => 'Febrero'],
            ['value' => 3, 'label' => 'Marzo'],
            ['value' => 4, 'label' => 'Abril'],
            ['value' => 5, 'label' => 'Mayo'],
            ['value' => 6, 'label' => 'Junio'],
            ['value' => 7, 'label' => 'Julio'],
            ['value' => 8, 'label' => 'Agosto'],
            ['value' => 9, 'label' => 'Septiembre'],
            ['value' => 10, 'label' => 'Octubre'],
            ['value' => 11, 'label' => 'Noviembre'],
            ['value' => 12, 'label' => 'Diciembre'],
        ];
    }

    /**
     * Opciones de años.
     */
    public function yearOptions(): array
    {
        $currentYear = (int) date('Y');

        return collect(range($currentYear - 2, $currentYear + 1))
            ->map(fn (int $year) => [
                'value' => $year,
                'label' => (string) $year,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Devuelve el nombre del mes.
     */
    public function monthName(int $month): string
    {
        return collect($this->monthOptions())
            ->firstWhere('value', $month)['label'] ?? 'Mes desconocido';
    }

    /**
     * Genera todos los días del mes con estado "Sin marcar".
     */
    private function generateMonthDays(MonthlyAttendance $attendance): void
    {
        $unmarkedStatusId = $this->catalogId(
            AttendanceDay::CATALOG_TYPE_STATUS,
            AttendanceDay::STATUS_UNMARKED
        );
        $restStatusId = $this->catalogId(
            AttendanceDay::CATALOG_TYPE_STATUS,
            AttendanceDay::STATUS_REST
        );

        $attendance->loadMissing('employee.workShift.rules');
        $workShift = $attendance->employee?->workShift;

        $startDate = Carbon::create($attendance->year, $attendance->month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth();

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $statusId = $unmarkedStatusId;

            if ($workShift) {
                $schedule = $this->workScheduleForDate($workShift, $date->toDateString());
                $statusId = $schedule['is_working_day'] ? $unmarkedStatusId : $restStatusId;
            }

            $attendance->days()->firstOrCreate(
                [
                    'attendance_date' => $date->toDateString(),
                ],
                [
                    'status_id' => $statusId,
                    'work_shift_id' => null,
                    'entry_time' => null,
                    'exit_time' => null,
                    'worked_hours' => 0,
                    'overtime_hours' => 0,
                    'payable_overtime_hours' => 0,
                ]
            );
        }
    }

    /**
     * Recalcula los totales de asistencia mensual desde el detalle diario.
     */
    public function recalculateTotals(MonthlyAttendance $attendance): void
    {
        $attendance->loadMissing(['days.status']);

        $workedDays = 0;
        $absenceDays = 0;
        $exchangeDays = 0;
        $restDays = 0;
        $overtimeHours = 0;
        $payableOvertimeHours = 0;

        foreach ($attendance->days as $day) {
            $statusCode = $day->status?->code;

            if ($statusCode === AttendanceDay::STATUS_PRESENT) {
                $workedDays++;
            }

            if ($statusCode === AttendanceDay::STATUS_ABSENT) {
                $absenceDays++;
            }

            if ($statusCode === AttendanceDay::STATUS_EXCHANGE_WORKED) {
                $exchangeDays++;
            }

            if ($statusCode === AttendanceDay::STATUS_REST) {
                $restDays++;
            }

            $overtimeHours += (float) $day->overtime_hours;
            $payableOvertimeHours += (float) $day->payable_overtime_hours;
        }

        $appliedExchangeStatusId = $this->catalogId(
            AttendanceExchange::CATALOG_TYPE_STATUS,
            AttendanceExchange::STATUS_APPLIED
        );

        $startDate = Carbon::create($attendance->year, $attendance->month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $compensatedAbsenceDays = AttendanceExchange::query()
            ->where('employee_id', $attendance->employee_id)
            ->where('status_id', $appliedExchangeStatusId)
            ->whereBetween('absence_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->count();

        $attendance->update([
            'worked_days' => $workedDays,
            'absence_days' => $absenceDays,
            'compensated_absence_days' => $compensatedAbsenceDays,
            'uncompensated_absence_days' => max($absenceDays - $compensatedAbsenceDays, 0),
            'exchange_days' => $exchangeDays,
            'rest_days' => $restDays,
            'overtime_hours' => $overtimeHours,
            'payable_overtime_hours' => $payableOvertimeHours,
        ]);
    }

    /**
     * Obtiene el catálogo de estado diario seleccionado.
     */
    private function attendanceDayStatus(int $statusId): Catalog
    {
        $status = Catalog::query()
            ->where('id', $statusId)
            ->where('type', AttendanceDay::CATALOG_TYPE_STATUS)
            ->where('status', true)
            ->first();

        if (! $status) {
            throw ValidationException::withMessages([
                'status_id' => 'El estado seleccionado no esta disponible para asistencia diaria. Recarga la pagina y vuelve a intentarlo. [ATT-008]',
            ]);
        }

        return $status;
    }

    /**
     * Indica si el estado requiere registrar horas trabajadas.
     */
    private function isWorkedStatus(string $statusCode): bool
    {
        return in_array($statusCode, [
            AttendanceDay::STATUS_PRESENT,
            AttendanceDay::STATUS_EXCHANGE_WORKED,
        ], true);
    }

    /**
     * Obtiene el turno asignado al trabajador.
     *
     * Esta validación es estricta porque las horas extras dependen del turno.
     */
    private function resolveEmployeeWorkShift(AttendanceDay $day): WorkShift
    {
        $employee = $day->monthlyAttendance?->employee;

        if (! $employee) {
            throw ValidationException::withMessages([
                'status_id' => 'No se encontro el trabajador de esta asistencia. Solicita a soporte revisar el registro. [ATT-009]',
            ]);
        }

        $workShift = $employee->workShift;

        if (! $workShift) {
            throw ValidationException::withMessages([
                'status_id' => 'Este trabajador no tiene un turno asignado. Asigna un turno en su ficha antes de registrar asistencia. [ATT-010]',
            ]);
        }

        if (! $workShift->status) {
            throw ValidationException::withMessages([
                'status_id' => 'El turno asignado al trabajador esta inactivo. Activa el turno o asigna uno vigente antes de registrar asistencia. [ATT-011]',
            ]);
        }

        return $workShift;
    }

    /**
     * Calcula horas trabajadas y horas extras usando el turno asignado.
     */
    private function calculateWorkedAndOvertimeHours(
        CarbonInterface|string $attendanceDate,
        ?string $entryTime,
        ?string $exitTime,
        WorkShift $workShift
    ): array {
        if (! $entryTime || ! $exitTime) {
            throw ValidationException::withMessages([
                'entry_time' => 'Ingresa la hora de entrada y salida para calcular la asistencia del dia. [ATT-012]',
                'exit_time' => 'Ingresa la hora de entrada y salida para calcular la asistencia del dia. [ATT-012]',
            ]);
        }

        $date = $attendanceDate instanceof CarbonInterface
            ? $attendanceDate->toDateString()
            : Carbon::parse($attendanceDate)->toDateString();

        $schedule = $this->workScheduleForDate($workShift, $date);
        $entryAt = Carbon::parse("{$date} {$entryTime}");
        $exitAt = Carbon::parse("{$date} {$exitTime}");

        if ($exitAt->lessThanOrEqualTo($entryAt)) {
            if (! $schedule['crosses_midnight']) {
                throw ValidationException::withMessages([
                    'exit_time' => 'La hora de salida debe ser posterior a la hora de entrada. Si el turno cruza medianoche, marca el turno como nocturno. [ATT-013]',
                ]);
            }

            $exitAt->addDay();
        }

        $workedMinutes = $entryAt->diffInMinutes($exitAt);

        $breakMinutes = $this->calculateBreakMinutes(
            date: $date,
            entryAt: $entryAt,
            exitAt: $exitAt,
            schedule: $schedule
        );

        $workedMinutes = max($workedMinutes - $breakMinutes, 0);

        $workedHours = round($workedMinutes / 60, 2);
        $expectedHours = (float) $schedule['expected_hours'];
        $overtimeAfterHours = $schedule['overtime_after_hours'] !== null
            ? (float) $schedule['overtime_after_hours']
            : $expectedHours;
        $overtimeHours = round(max($workedHours - $expectedHours, 0), 2);
        $payableOvertimeHours = $schedule['overtime_pay_enabled']
            ? round(max($workedHours - $overtimeAfterHours, 0), 2)
            : 0.0;

        return [
            'worked_hours' => $workedHours,
            'overtime_hours' => $overtimeHours,
            'payable_overtime_hours' => $payableOvertimeHours,
        ];
    }

    private function workScheduleForDate(WorkShift $workShift, string $date): array
    {
        $workShift->loadMissing('rules');

        $dayOfWeek = Carbon::parse($date)->isoWeekday();
        $rule = $workShift->uses_daily_rules || $workShift->rotation_enabled
            ? $workShift->rules->firstWhere('day_of_week', $dayOfWeek)
            : null;
        $hasDailyRule = (bool) $rule;

        $schedule = [
            'is_working_day' => $rule?->is_working_day ?? true,
            'start_time' => $this->formatTimeValue($rule?->start_time ?? $workShift->start_time),
            'break_start_time' => $hasDailyRule
                ? ($rule->break_start_time ? $this->formatTimeValue($rule->break_start_time) : null)
                : ($workShift->break_start_time ? $this->formatTimeValue($workShift->break_start_time) : null),
            'break_end_time' => $hasDailyRule
                ? ($rule->break_end_time ? $this->formatTimeValue($rule->break_end_time) : null)
                : ($workShift->break_end_time ? $this->formatTimeValue($workShift->break_end_time) : null),
            'end_time' => $this->formatTimeValue($rule?->end_time ?? $workShift->end_time),
            'expected_hours' => (float) ($rule?->expected_hours ?? $workShift->daily_hours),
            'crosses_midnight' => (bool) ($rule?->crosses_midnight ?? $workShift->crosses_midnight),
            'overtime_pay_enabled' => (bool) ($rule?->overtime_pay_enabled ?? true),
            'overtime_after_hours' => $rule?->overtime_after_hours,
        ];

        if ($this->isRotationRestDay($workShift, $date)) {
            $schedule['is_working_day'] = false;
            $schedule['expected_hours'] = 0.0;
        }

        return $schedule;
    }

    private function isRotationRestDay(WorkShift $workShift, string $date): bool
    {
        if (! $workShift->rotation_enabled || ! $workShift->rotation_start_date) {
            return false;
        }

        $workDays = (int) $workShift->rotation_work_days;
        $restDays = (int) $workShift->rotation_rest_days;
        $cycleDays = $workDays + $restDays;

        if ($workDays <= 0 || $restDays <= 0 || $cycleDays <= 0) {
            return false;
        }

        $startDate = $workShift->rotation_start_date->copy()->startOfDay();
        $attendanceDate = Carbon::parse($date)->startOfDay();

        if ($attendanceDate->lessThan($startDate)) {
            return false;
        }

        $cyclePosition = $startDate->diffInDays($attendanceDate) % $cycleDays;

        return $cyclePosition >= $workDays;
    }

    /**
     * Calcula los minutos de refrigerio que deben descontarse.
     *
     * Solo descuenta refrigerio si el turno tiene hora de inicio y fin de descanso
     * y si ese rango cruza realmente con el rango trabajado.
     */
    private function calculateBreakMinutes(
        string $date,
        CarbonInterface $entryAt,
        CarbonInterface $exitAt,
        array $schedule
    ): int {
        if (! $schedule['break_start_time'] || ! $schedule['break_end_time']) {
            return 0;
        }

        $breakStartTime = $schedule['break_start_time'];
        $breakEndTime = $schedule['break_end_time'];
        $shiftStartTime = $schedule['start_time'];

        $breakStartAt = Carbon::parse("{$date} {$breakStartTime}");
        $breakEndAt = Carbon::parse("{$date} {$breakEndTime}");

        if ($schedule['crosses_midnight'] && $breakStartTime < $shiftStartTime) {
            $breakStartAt->addDay();
        }

        if ($breakEndAt->lessThanOrEqualTo($breakStartAt)) {
            $breakEndAt->addDay();
        }

        $overlapStart = $entryAt->greaterThan($breakStartAt)
            ? $entryAt->copy()
            : $breakStartAt->copy();

        $overlapEnd = $exitAt->lessThan($breakEndAt)
            ? $exitAt->copy()
            : $breakEndAt->copy();

        if ($overlapEnd->lessThanOrEqualTo($overlapStart)) {
            return 0;
        }

        return $overlapStart->diffInMinutes($overlapEnd);
    }

    /**
     * Normaliza un valor de hora.
     *
     * WorkShift castea las horas como datetime:H:i, por eso este método
     * acepta Carbon o string.
     */
    private function formatTimeValue(mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->format('H:i');
        }

        return substr((string) $value, 0, 5);
    }

    /**
     * Obtiene el ID de un catálogo por tipo y código.
     */
    private function catalogId(string $type, string $code): int
    {
        $catalog = Catalog::query()
            ->where('type', $type)
            ->where('code', $code)
            ->where('status', true)
            ->first();

        if (! $catalog) {
            throw ValidationException::withMessages([
                'catalog' => "No se pudo completar la accion porque falta una configuracion interna. Solicita a soporte revisar el catalogo {$type}/{$code}. [CFG-001]",
            ]);
        }

        return $catalog->id;
    }

    /**
     * Aplica búsqueda flexible por datos del trabajador.
     */
    private function applyEmployeeSearch(Builder $query, string $search): void
    {
        $query->whereHas('employee', function (Builder $employeeQuery) use ($search) {
            $columns = [
                'code',
                'employee_code',
                'document_number',
                'dni',
                'name',
                'full_name',
                'first_name',
                'last_name',
                'email',
            ];

            $employeeQuery->where(function (Builder $innerQuery) use ($columns, $search) {
                foreach ($columns as $column) {
                    if (Schema::hasColumn('employees', $column)) {
                        $innerQuery->orWhere($column, 'like', "%{$search}%");
                    }
                }
            });
        });
    }

    /**
     * Obtiene un nombre entendible del trabajador.
     */
    public function employeeDisplayName(?Employee $employee): string
    {
        if (! $employee) {
            return 'Trabajador no disponible';
        }

        $fullName = $employee->getAttribute('full_name')
            ?: $employee->getAttribute('name');

        if ($fullName) {
            return $fullName;
        }

        $firstName = $employee->getAttribute('first_name') ?? '';
        $lastName = $employee->getAttribute('last_name') ?? '';

        return trim("{$firstName} {$lastName}") ?: "Trabajador #{$employee->id}";
    }

    /**
     * Obtiene el código del trabajador si existe.
     */
    public function employeeCode(?Employee $employee): string
    {
        if (! $employee) {
            return 'Sin código';
        }

        return $employee->getAttribute('code')
            ?: $employee->getAttribute('employee_code')
            ?: "ID {$employee->id}";
    }

    /**
     * Obtiene el documento del trabajador si existe.
     */
    public function employeeDocument(?Employee $employee): string
    {
        if (! $employee) {
            return 'Sin documento';
        }

        return $employee->getAttribute('document_number')
            ?: $employee->getAttribute('dni')
            ?: 'Sin documento';
    }

    /**
     * Devuelve los periodos permitidos para registrar asistencia.
     */
    public function allowedPeriodOptions(): array
    {
        $currentPeriod = now()->startOfMonth();
        $previousPeriod = now()->subMonthNoOverflow()->startOfMonth();

        return collect([$previousPeriod, $currentPeriod])
            ->map(fn ($date) => [
                'value' => $date->format('Y-m'),
                'month' => (int) $date->month,
                'year' => (int) $date->year,
                'label' => $this->monthName((int) $date->month).' '.$date->year,
                'is_current' => $date->isSameMonth($currentPeriod),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Valida que el periodo seleccionado esté permitido para registrar asistencia.
     */
    public function ensureAllowedPeriod(int $month, int $year): void
    {
        $selectedPeriod = now()
            ->setDate($year, $month, 1)
            ->startOfMonth();

        $allowedPeriods = collect($this->allowedPeriodOptions())
            ->pluck('value')
            ->toArray();

        if (! in_array($selectedPeriod->format('Y-m'), $allowedPeriods, true)) {
            throw ValidationException::withMessages([
                'period' => 'Solo puedes registrar asistencia del mes actual o del mes anterior. Selecciona uno de los periodos disponibles. [ATT-014]',
                'month' => 'Solo puedes registrar asistencia del mes actual o del mes anterior. Selecciona uno de los periodos disponibles. [ATT-014]',
                'year' => 'Solo puedes registrar asistencia del mes actual o del mes anterior. Selecciona uno de los periodos disponibles. [ATT-014]',
            ]);
        }
    }

    /**
     * Convierte un periodo mensual en mes y año.
     */
    public function parsePeriod(?string $period): array
    {
        if (! $period) {
            return [
                'month' => null,
                'year' => null,
            ];
        }

        $period = trim(str_replace('/', '-', $period));

        if (preg_match('/^\d{4}-\d{2}$/', $period)) {
            [$year, $month] = explode('-', $period);

            return [
                'month' => (int) $month,
                'year' => (int) $year,
            ];
        }

        if (preg_match('/^\d{2}-\d{4}$/', $period)) {
            [$month, $year] = explode('-', $period);

            return [
                'month' => (int) $month,
                'year' => (int) $year,
            ];
        }

        return [
            'month' => null,
            'year' => null,
        ];
    }
}
