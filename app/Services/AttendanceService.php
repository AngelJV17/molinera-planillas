<?php
namespace App\Services;

use App\Models\AttendanceDay;
use App\Models\AttendanceExchange;
use App\Models\Catalog;
use App\Models\Employee;
use App\Models\MonthlyAttendance;
use Carbon\Carbon;
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
     * los días del calendario con estado "Sin marcar".
     */
    public function createMonthlyAttendance(array $data, ?int $userId = null): MonthlyAttendance
    {
        return DB::transaction(function () use ($data, $userId) {
            // Valida que solo se pueda registrar el mes actual o el mes anterior.
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
                    'employee_id' => 'Ya existe una asistencia mensual registrada para este trabajador en el periodo seleccionado.',
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
     * Esto se usará en la siguiente pantalla de calendario mensual.
     */
    public function updateDay(AttendanceDay $day, array $data): AttendanceDay
    {
        return DB::transaction(function () use ($day, $data) {
            $day->load('monthlyAttendance.status');

            // Valida que el día pueda modificarse.
            // Aquí se bloquean asistencias cerradas y días futuros.
            $this->ensureDayCanBeUpdated($day);

            $day->update([
                'status_id'      => $data['status_id'],
                'overtime_hours' => $data['overtime_hours'] ?? 0,
                'observation'    => $data['observation'] ?? null,
            ]);

            $this->recalculateTotals($day->monthlyAttendance);

            return $day;
        });
    }

    /**
     * Verifica si un día del calendario puede modificarse.
     *
     * Reglas:
     * - No se puede modificar una asistencia mensual cerrada.
     * - No se pueden marcar días futuros.
     *   Ejemplo: si hoy es 26, no se puede registrar 27, 28, 29, etc.
     */
    private function ensureDayCanBeUpdated(AttendanceDay $day): void
    {
        $day->loadMissing('monthlyAttendance.status');

        if (! $day->monthlyAttendance->isEditable()) {
            throw ValidationException::withMessages([
                'status_id' => 'No se puede modificar una asistencia mensual cerrada.',
            ]);
        }

        if ($day->attendance_date->startOfDay()->greaterThan(now()->startOfDay())) {
            throw ValidationException::withMessages([
                'attendance_date' => 'No puedes registrar asistencia de días futuros.',
                'status_id'       => 'No puedes registrar asistencia de días futuros.',
            ]);
        }
    }

    /**
     * Cierra una asistencia mensual.
     *
     * Una vez cerrada, ya no debería editarse desde el calendario.
     */
    public function close(MonthlyAttendance $attendance, ?int $userId = null): MonthlyAttendance
    {
        return DB::transaction(function () use ($attendance, $userId) {
            $attendance->load(['status', 'days.status']);

            // No se permite cerrar un periodo que todavía no ha terminado.
            // Ejemplo: si hoy es 26 de junio, no se puede cerrar junio porque aún faltan días.
            $periodEndDate = Carbon::create($attendance->year, $attendance->month, 1)
                ->endOfMonth()
                ->startOfDay();

            if ($periodEndDate->greaterThan(now()->startOfDay())) {
                throw ValidationException::withMessages([
                    'attendance' => 'No puedes cerrar esta asistencia mensual porque el periodo todavía no ha terminado.',
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
                    'attendance' => 'No puedes cerrar la asistencia mientras existan días sin marcar.',
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
     * Devuelve trabajadores para el selector.
     *
     * Se intenta mantener flexible porque la tabla employees puede variar
     * según los nombres de columnas definidos en tu proyecto.
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
     * Opciones de meses para filtros y formulario.
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
     * Opciones de años para filtros y formulario.
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
                'catalog' => "No se encontró el catálogo activo {$type} / {$code}. Ejecuta el seeder de catálogos.",
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
     *
     * Regla del negocio:
     * - Solo se permite registrar el mes actual.
     * - También se permite registrar como máximo el mes anterior.
     * - No se permiten meses futuros ni meses antiguos.
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
                'period' => 'Solo puedes registrar asistencia del mes actual o del mes anterior.',
                'month'  => 'Solo puedes registrar asistencia del mes actual o del mes anterior.',
                'year'   => 'Solo puedes registrar asistencia del mes actual o del mes anterior.',
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
