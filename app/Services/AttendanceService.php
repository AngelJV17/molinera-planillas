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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    /**
     * Lista las asistencias mensuales aplicando filtros.
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $search   = trim($filters['search'] ?? '');
        $statusId = $filters['status_id'] ?? null;
        $month    = $filters['month'] ?? null;
        $year     = $filters['year'] ?? null;
        $perPage  = (int) ($filters['per_page'] ?? 10);

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
                'employee_id'  => $data['employee_id'],
                'status_id'    => $draftStatusId,
                'month'        => $data['month'],
                'year'         => $data['year'],
                'observations' => $data['observations'] ?? null,
                'created_by'   => $userId,
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
            ]);

            $this->ensureDayCanBeUpdated($day);

            $status     = $this->attendanceDayStatus((int) $data['status_id']);
            $statusCode = $status->code;

            if ($this->isWorkedStatus($statusCode)) {
                $workShift = $this->resolveEmployeeWorkShift($day);

                $calculatedHours = $this->calculateWorkedAndOvertimeHours(
                    attendanceDate: $day->attendance_date,
                    entryTime: $data['entry_time'] ?? null,
                    exitTime: $data['exit_time'] ?? null,
                    workShift: $workShift
                );

                $day->update([
                    'status_id'      => $status->id,
                    'work_shift_id'  => $workShift->id,
                    'entry_time'     => $data['entry_time'],
                    'exit_time'      => $data['exit_time'],
                    'worked_hours'   => $calculatedHours['worked_hours'],
                    'overtime_hours' => $calculatedHours['overtime_hours'],
                    'observation'    => $data['observation'] ?? null,
                ]);
            } else {
                $day->update([
                    'status_id'      => $status->id,
                    'work_shift_id'  => null,
                    'entry_time'     => null,
                    'exit_time'      => null,
                    'worked_hours'   => 0,
                    'overtime_hours' => 0,
                    'observation'    => $data['observation'] ?? null,
                ]);
            }

            $this->recalculateTotals($day->monthlyAttendance);

            return $day->refresh();
        });
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

                    $entryTime = $this->formatTimeValue($workShift->start_time);
                    $exitTime = $this->formatTimeValue($workShift->end_time);

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
                    'observation' => $day->observation,
                ]);
            }

            $this->recalculateTotals($attendance->refresh());
        });
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
                'status_id'       => 'Todavia no puedes registrar asistencia para una fecha futura. Selecciona una fecha de hoy o anterior. [ATT-005]',
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
                ->filter(fn(AttendanceDay $day) => $day->status?->code === AttendanceDay::STATUS_UNMARKED)
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
            ->map(fn(Employee $employee) => [
                'id'       => $employee->id,
                'name'     => $this->employeeDisplayName($employee),
                'code'     => $this->employeeCode($employee),
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
            ->map(fn(int $year) => [
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

        $startDate = Carbon::create($attendance->year, $attendance->month, 1)->startOfDay();
        $endDate   = $startDate->copy()->endOfMonth();

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $attendance->days()->firstOrCreate(
                [
                    'attendance_date' => $date->toDateString(),
                ],
                [
                    'status_id'      => $unmarkedStatusId,
                    'work_shift_id'  => null,
                    'entry_time'     => null,
                    'exit_time'      => null,
                    'worked_hours'   => 0,
                    'overtime_hours' => 0,
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

        $workedDays    = 0;
        $absenceDays   = 0;
        $exchangeDays  = 0;
        $restDays      = 0;
        $overtimeHours = 0;

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
        }

        $appliedExchangeStatusId = $this->catalogId(
            AttendanceExchange::CATALOG_TYPE_STATUS,
            AttendanceExchange::STATUS_APPLIED
        );

        $startDate = Carbon::create($attendance->year, $attendance->month, 1)->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();

        $compensatedAbsenceDays = AttendanceExchange::query()
            ->where('employee_id', $attendance->employee_id)
            ->where('status_id', $appliedExchangeStatusId)
            ->whereBetween('absence_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->count();

        $attendance->update([
            'worked_days'                => $workedDays,
            'absence_days'               => $absenceDays,
            'compensated_absence_days'   => $compensatedAbsenceDays,
            'uncompensated_absence_days' => max($absenceDays - $compensatedAbsenceDays, 0),
            'exchange_days'              => $exchangeDays,
            'rest_days'                  => $restDays,
            'overtime_hours'             => $overtimeHours,
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
        CarbonInterface | string $attendanceDate,
        ?string $entryTime,
        ?string $exitTime,
        WorkShift $workShift
    ): array {
        if (! $entryTime || ! $exitTime) {
            throw ValidationException::withMessages([
                'entry_time' => 'Ingresa la hora de entrada y salida para calcular la asistencia del dia. [ATT-012]',
                'exit_time'  => 'Ingresa la hora de entrada y salida para calcular la asistencia del dia. [ATT-012]',
            ]);
        }

        $date = $attendanceDate instanceof CarbonInterface
            ? $attendanceDate->toDateString()
            : Carbon::parse($attendanceDate)->toDateString();

        $entryAt = Carbon::parse("{$date} {$entryTime}");
        $exitAt  = Carbon::parse("{$date} {$exitTime}");

        if ($exitAt->lessThanOrEqualTo($entryAt)) {
            if (! $workShift->crosses_midnight) {
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
            workShift: $workShift
        );

        $workedMinutes = max($workedMinutes - $breakMinutes, 0);

        $workedHours   = round($workedMinutes / 60, 2);
        $expectedHours = (float) $workShift->daily_hours;
        $overtimeHours = round(max($workedHours - $expectedHours, 0), 2);

        return [
            'worked_hours'   => $workedHours,
            'overtime_hours' => $overtimeHours,
        ];
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
        WorkShift $workShift
    ): int {
        if (! $workShift->break_start_time || ! $workShift->break_end_time) {
            return 0;
        }

        $breakStartTime = $this->formatTimeValue($workShift->break_start_time);
        $breakEndTime   = $this->formatTimeValue($workShift->break_end_time);
        $shiftStartTime = $this->formatTimeValue($workShift->start_time);

        $breakStartAt = Carbon::parse("{$date} {$breakStartTime}");
        $breakEndAt   = Carbon::parse("{$date} {$breakEndTime}");

        if ($workShift->crosses_midnight && $breakStartTime < $shiftStartTime) {
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
        $lastName  = $employee->getAttribute('last_name') ?? '';

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
        $currentPeriod  = now()->startOfMonth();
        $previousPeriod = now()->subMonthNoOverflow()->startOfMonth();

        return collect([$previousPeriod, $currentPeriod])
            ->map(fn($date) => [
                'value'      => $date->format('Y-m'),
                'month'      => (int) $date->month,
                'year'       => (int) $date->year,
                'label'      => $this->monthName((int) $date->month) . ' ' . $date->year,
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
                'month'  => 'Solo puedes registrar asistencia del mes actual o del mes anterior. Selecciona uno de los periodos disponibles. [ATT-014]',
                'year'   => 'Solo puedes registrar asistencia del mes actual o del mes anterior. Selecciona uno de los periodos disponibles. [ATT-014]',
            ]);
        }
    }

    /**
     * Convierte un periodo con formato YYYY-MM en mes y año.
     */
    public function parsePeriod(?string $period): array
    {
        if (! $period || ! preg_match('/^\d{4}-\d{2}$/', $period)) {
            return [
                'month' => null,
                'year'  => null,
            ];
        }

        [$year, $month] = explode('-', $period);

        return [
            'month' => (int) $month,
            'year'  => (int) $year,
        ];
    }
}
