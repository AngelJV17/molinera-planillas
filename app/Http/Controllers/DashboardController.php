<?php
namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\MonthlyAttendance;
use App\Models\Payroll;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly PayrollService $payrollService
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $period = $this->resolvePeriod($request->input('period'));
        $currentMonth = $period['month'];
        $currentYear  = $period['year'];

        /*
        |--------------------------------------------------------------------------
        | Trabajadores activos
        |--------------------------------------------------------------------------
        */
        $activeWorkers = Employee::active()->count();

        /*
        |--------------------------------------------------------------------------
        | Asistencias del mes actual
        |--------------------------------------------------------------------------
        |
        | Total de asistencias creadas:
        | Representa cuántos trabajadores ya tienen su control mensual generado.
        |
        | Asistencias cerradas:
        | Representa cuántas asistencias ya fueron completadas y consolidadas.
        |
        */
        $totalMonthlyAttendances = MonthlyAttendance::query()
            ->where('month', $currentMonth)
            ->where('year', $currentYear)
            ->count();

        $closedMonthlyAttendances = MonthlyAttendance::query()
            ->where('month', $currentMonth)
            ->where('year', $currentYear)
            ->whereHas('status', function ($query) {
                $query->where('code', MonthlyAttendance::STATUS_CLOSED);
            })
            ->with(['status'])
            ->get();

        $closedAttendancesCount = $closedMonthlyAttendances->count();

        $pendingAttendances = max(
            $totalMonthlyAttendances - $closedAttendancesCount,
            0
        );

        $workersWithoutAttendance = max(
            $activeWorkers - $totalMonthlyAttendances,
            0
        );

        $closedAttendanceRate = $totalMonthlyAttendances > 0
            ? round(($closedAttendancesCount / $totalMonthlyAttendances) * 100)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Porcentaje de asistencia consolidada
        |--------------------------------------------------------------------------
        |
        | Este porcentaje solo se calcula con asistencias cerradas.
        | No se usan asistencias abiertas porque todavía no representan
        | información mensual final.
        |
        | Fórmula:
        | Asistencia = asistencias / (asistencias + faltas) * 100
        |
        | Los descansos no se consideran como falta ni como asistencia,
        | porque no son días laborables.
        |
        */
        $workedDays    = (int) $closedMonthlyAttendances->sum('worked_days');
        $absenceDays   = (int) $closedMonthlyAttendances->sum('absence_days');
        $restDays      = (int) $closedMonthlyAttendances->sum('rest_days');
        $exchangeDays  = (int) $closedMonthlyAttendances->sum('exchange_days');
        $overtimeHours = (float) $closedMonthlyAttendances->sum('payable_overtime_hours');

        $totalEvaluatedDays = $workedDays + $absenceDays;

        $attendanceRate = $totalEvaluatedDays > 0
            ? round(($workedDays / $totalEvaluatedDays) * 100)
            : 0;

        $absenceRate = $totalEvaluatedDays > 0
            ? round(($absenceDays / $totalEvaluatedDays) * 100)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Planillas recientes
        |--------------------------------------------------------------------------
        */
        $latestPayrolls = Payroll::query()
            ->with('status:id,code,name')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn(Payroll $payroll) => [
                'id'          => $payroll->id,
                'period'      => $this->payrollService->monthName($payroll->month) . ' ' . $payroll->year,
                'status'      => $payroll->status?->name ?? 'Sin estado',
                'status_code' => $payroll->status?->code,
                'total'       => $payroll->total_net,
                'date'        => $payroll->created_at?->format('d/m/Y'),
            ])
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Métricas del dashboard
        |--------------------------------------------------------------------------
        */
        return Inertia::render('Dashboard', [
            'metrics'          => [
                'active_workers'             => $activeWorkers,

                /*
                |--------------------------------------------------------------------------
                | Asistencia mensual
                |--------------------------------------------------------------------------
                */
                'attendance_rate'            => $attendanceRate,
                'absence_rate'               => $absenceRate,

                'monthly_attendances'        => $totalMonthlyAttendances,
                'closed_attendances'         => $closedAttendancesCount,
                'pending_attendances'        => $pendingAttendances,
                'closed_attendance_rate'     => $closedAttendanceRate,

                'workers_without_attendance' => $workersWithoutAttendance,

                'evaluated_attendance_days'  => $totalEvaluatedDays,
                'worked_days'                => $workedDays,
                'absence_days'               => $absenceDays,
                'rest_days'                  => $restDays,
                'exchange_days'              => $exchangeDays,
                'overtime_hours'             => $overtimeHours,

                /*
                |--------------------------------------------------------------------------
                | Planillas
                |--------------------------------------------------------------------------
                */
                'current_month_payrolls'     => Payroll::query()
                    ->where('month', $currentMonth)
                    ->where('year', $currentYear)
                    ->count(),

                'paid_payrolls'              => Payroll::query()
                    ->whereHas('status', function ($query) {
                        $query->where('code', Payroll::STATUS_PAID);
                    })
                    ->count(),
            ],

            'filters' => [
                'period' => $period['value'],
            ],

            'periodOptions' => $this->periodOptions($period),
            'currentPeriodLabel' => $this->payrollService->monthName($currentMonth) . ' ' . $currentYear,

            'latestPayrolls'   => $latestPayrolls,

            'attendanceStatus' => [
                [
                    'label' => 'Asistencias',
                    'value' => $attendanceRate,
                    'color' => 'bg-primary',
                ],
                [
                    'label' => 'Faltas',
                    'value' => $absenceRate,
                    'color' => 'bg-danger',
                ],
            ],

            'recentActivities' => $this->recentActivities(),
        ]);
    }

    private function resolvePeriod(?string $requestedPeriod): array
    {
        $requested = $this->payrollService->parsePeriod($requestedPeriod);

        if ($requested['month'] && $requested['year']) {
            return [
                'month' => $requested['month'],
                'year' => $requested['year'],
                'value' => sprintf('%04d-%02d', $requested['year'], $requested['month']),
            ];
        }

        $current = [
            'month' => (int) now()->month,
            'year' => (int) now()->year,
        ];

        $hasCurrentPeriodData = MonthlyAttendance::query()
            ->where('month', $current['month'])
            ->where('year', $current['year'])
            ->exists();

        if ($hasCurrentPeriodData) {
            return [
                ...$current,
                'value' => sprintf('%04d-%02d', $current['year'], $current['month']),
            ];
        }

        $latestAttendancePeriod = MonthlyAttendance::query()
            ->select(['month', 'year'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        if ($latestAttendancePeriod) {
            return [
                'month' => (int) $latestAttendancePeriod->month,
                'year' => (int) $latestAttendancePeriod->year,
                'value' => sprintf('%04d-%02d', $latestAttendancePeriod->year, $latestAttendancePeriod->month),
            ];
        }

        return [
            ...$current,
            'value' => sprintf('%04d-%02d', $current['year'], $current['month']),
        ];
    }

    private function periodOptions(array $selectedPeriod): array
    {
        $options = MonthlyAttendance::query()
            ->select(['month', 'year'])
            ->distinct()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->limit(12)
            ->get()
            ->map(fn(MonthlyAttendance $attendance) => [
                'value' => sprintf('%04d-%02d', $attendance->year, $attendance->month),
                'label' => $this->payrollService->monthName((int) $attendance->month) . ' ' . $attendance->year,
            ])
            ->values();

        $selectedValue = $selectedPeriod['value'];

        if (! $options->contains('value', $selectedValue)) {
            $options->prepend([
                'value' => $selectedValue,
                'label' => $this->payrollService->monthName($selectedPeriod['month']) . ' ' . $selectedPeriod['year'],
            ]);
        }

        return $options->values()->all();
    }

    private function recentActivities(): array
    {
        $payrolls = Payroll::query()
            ->latest()
            ->limit(3)
            ->get()
            ->toBase()
            ->map(function (Payroll $payroll) {
                return [
                    'date'    => $payroll->created_at,
                    'message' => "Planilla {$payroll->code} registrada para {$this->payrollService->monthName($payroll->month)} {$payroll->year}.",
                ];
            });

        $attendances = MonthlyAttendance::query()
            ->with([
                'employee:id,employee_code,first_name,last_name',
                'status:id,code,name',
            ])
            ->latest()
            ->limit(3)
            ->get()
            ->toBase()
            ->map(function (MonthlyAttendance $attendance) {
                $employeeName = trim(
                    "{$attendance->employee?->first_name} {$attendance->employee?->last_name}"
                );

                if ($employeeName === '') {
                    $employeeName = 'trabajador no disponible';
                }

                $period = $this->payrollService->monthName((int) $attendance->month) . ' ' . $attendance->year;

                $action = $attendance->status?->code === MonthlyAttendance::STATUS_CLOSED
                    ? 'cerrada'
                    : 'registrada';

                return [
                    'date'    => $attendance->updated_at ?? $attendance->created_at,
                    'message' => "Asistencia mensual {$action} para {$employeeName} correspondiente a {$period}.",
                ];
            });

        return $payrolls
            ->concat($attendances)
            ->sortByDesc('date')
            ->take(5)
            ->pluck('message')
            ->values()
            ->all();
    }
}
