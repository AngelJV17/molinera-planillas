<?php
namespace App\Http\Controllers;

use App\Http\Requests\Attendance\BulkUpdateAttendanceDaysRequest;
use App\Http\Requests\Attendance\StoreMonthlyAttendanceRequest;
use App\Http\Requests\Attendance\UpdateAttendanceDayRequest;
use App\Models\AttendanceDay;
use App\Models\MonthlyAttendance;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $service
    ) {
    }

    /**
     * Muestra el listado de asistencias mensuales.
     */
    public function index(Request $request): Response
    {
        $period      = $request->input('period', '');
        $periodParts = $this->service->parsePeriod($period);

        $filters = [
            'search'    => $request->input('search', ''),
            'status_id' => $request->input('status_id', ''),
            'period'    => $period,
            'month'     => $periodParts['month'],
            'year'      => $periodParts['year'],
            'per_page'  => $request->input('per_page', 10),
        ];

        $attendances = $this->service
            ->paginate($filters)
            ->through(function (MonthlyAttendance $attendance) {
                return [
                    'id'                         => $attendance->id,
                    'month'                      => $attendance->month,
                    'year'                       => $attendance->year,
                    'period'                     => $this->service->monthName($attendance->month) . ' ' . $attendance->year,

                    'employee'                   => [
                        'id'       => $attendance->employee?->id,
                        'name'     => $this->service->employeeDisplayName($attendance->employee),
                        'code'     => $this->service->employeeCode($attendance->employee),
                        'document' => $this->service->employeeDocument($attendance->employee),
                    ],

                    'status'                     => [
                        'id'   => $attendance->status?->id,
                        'code' => $attendance->status?->code,
                        'name' => $attendance->status?->name ?? 'Sin estado',
                    ],

                    'worked_days'                => $attendance->worked_days,
                    'absence_days'               => $attendance->absence_days,
                    'compensated_absence_days'   => $attendance->compensated_absence_days,
                    'uncompensated_absence_days' => $attendance->uncompensated_absence_days,
                    'exchange_days'              => $attendance->exchange_days,
                    'rest_days'                  => $attendance->rest_days,
                    'overtime_hours'             => $attendance->overtime_hours,
                    'payable_overtime_hours'     => $attendance->payable_overtime_hours,
                    'observations'               => $attendance->observations,
                    'is_editable'                => $attendance->isEditable(),
                    'is_closed'                  => $attendance->isClosed(),
                    'can_reopen'                 => $this->canReopenAttendance($attendance),
                ];
            });

        return Inertia::render('Attendance/Index', [
            'attendances'     => $attendances,
            'filters'         => $filters,
            'employees'       => $this->service->employeeOptions(),
            'monthlyStatuses' => $this->service->monthlyStatuses(),
            'monthOptions'    => $this->service->monthOptions(),
            'yearOptions'     => $this->service->yearOptions(),
            'allowedPeriods'  => $this->service->allowedPeriodOptions(),
            'defaultPeriod'   => now()->format('Y-m'),
            'defaultMonth'    => now()->month,
            'defaultYear'     => now()->year,
        ]);
    }

    /**
     * Registra una nueva cabecera mensual y genera sus días.
     */
    public function store(StoreMonthlyAttendanceRequest $request): RedirectResponse
    {
        $this->service->createMonthlyAttendance(
            $request->validated(),
            $request->user()?->id
        );

        return redirect()
            ->route('attendance.index')
            ->with('success', 'Asistencia mensual registrada correctamente.');
    }

    /**
     * Muestra la pantalla del calendario mensual.
     *
     * Esta vista la construiremos en el siguiente paso.
     */
    public function edit(MonthlyAttendance $monthlyAttendance): Response
    {
        $monthlyAttendance->load([
            'employee.workShift.rules',
            'employee.position',
            'employee.workArea',
            'employee.employmentStatus',
            'employee.pensionSystem',
            'status',
            'days.status',
            'days.workShift',
            'days.absenceExchange.status',
            'days.workedExchange.status',
        ]);

        return Inertia::render('Attendance/Edit', [
            'attendance'  => [
                'id'             => $monthlyAttendance->id,
                'period'         => $this->service->monthName((int) $monthlyAttendance->month) . ' ' . $monthlyAttendance->year,

                'employee'       => [
                    'id'         => $monthlyAttendance->employee?->id,
                    'name'       => $this->service->employeeDisplayName($monthlyAttendance->employee),
                    'code'       => $this->service->employeeCode($monthlyAttendance->employee),
                    'document'   => $this->service->employeeDocument($monthlyAttendance->employee),
                    'position'   => $monthlyAttendance->employee?->position?->name ?? 'Sin cargo',
                    'work_area'  => $monthlyAttendance->employee?->workArea?->name ?? 'Sin area',
                    'employment_status' => $monthlyAttendance->employee?->employmentStatus?->name ?? 'Sin estado laboral',
                    'pension_system' => $monthlyAttendance->employee?->pensionSystem?->name ?? 'Sin regimen',

                    'work_shift' => $monthlyAttendance->employee?->workShift
                        ? [
                        'id'          => $monthlyAttendance->employee->workShift->id,
                        'name'        => $monthlyAttendance->employee->workShift->name,
                        'start_time'  => optional($monthlyAttendance->employee->workShift->start_time)->format('H:i'),
                        'end_time'    => optional($monthlyAttendance->employee->workShift->end_time)->format('H:i'),
                        'daily_hours' => $monthlyAttendance->employee->workShift->daily_hours,
                        'rotation_enabled' => $monthlyAttendance->employee->workShift->rotation_enabled,
                        'rotation_work_days' => $monthlyAttendance->employee->workShift->rotation_work_days,
                        'rotation_rest_days' => $monthlyAttendance->employee->workShift->rotation_rest_days,
                    ]
                        : null,
                ],

                'status'         => [
                    'id'   => $monthlyAttendance->status?->id,
                    'code' => $monthlyAttendance->status?->code,
                    'name' => $monthlyAttendance->status?->name,
                ],

                'month'          => $monthlyAttendance->month,
                'year'           => $monthlyAttendance->year,
                'worked_days'    => $monthlyAttendance->worked_days,
                'absence_days'   => $monthlyAttendance->absence_days,
                'compensated_absence_days' => $monthlyAttendance->compensated_absence_days,
                'uncompensated_absence_days' => $monthlyAttendance->uncompensated_absence_days,
                'exchange_days'  => $monthlyAttendance->exchange_days,
                'rest_days'      => $monthlyAttendance->rest_days,
                'overtime_hours' => $monthlyAttendance->overtime_hours,
                'payable_overtime_hours' => $monthlyAttendance->payable_overtime_hours,
                'can_reopen'     => $this->canReopenAttendance($monthlyAttendance),

                'days'           => $monthlyAttendance->days
                    ->sortBy('attendance_date')
                    ->values()
                    ->map(function ($day) {
                        return [
                            'id'               => $day->id,
                            'attendance_date'  => $day->attendance_date?->toDateString(),
                            'day_number'       => $day->attendance_date?->format('d'),
                            'weekday'          => $day->attendance_date?->locale('es')->translatedFormat('D'),

                            'status'           => [
                                'id'   => $day->status?->id,
                                'code' => $day->status?->code,
                                'name' => $day->status?->name,
                            ],

                            'work_shift'       => $day->workShift
                                ? [
                                'id'   => $day->workShift->id,
                                'name' => $day->workShift->name,
                            ]
                                : null,

                            'entry_time'       => $day->entry_time,
                            'exit_time'        => $day->exit_time,
                            'worked_hours'     => $day->worked_hours,
                            'overtime_hours'   => $day->overtime_hours,
                            'payable_overtime_hours' => $day->payable_overtime_hours,
                            'observation'      => $day->observation,

                            'absence_exchange' => $day->absenceExchange,
                            'worked_exchange'  => $day->workedExchange,
                        ];
                    }),
            ],

            'dayStatuses' => $this->service->dayStatuses(),
        ]);
    }

    /**
     * Actualiza un día del calendario mensual.
     */
    public function updateDay(UpdateAttendanceDayRequest $request, AttendanceDay $attendanceDay): RedirectResponse
    {
        $this->service->updateDay(
            $attendanceDay,
            $request->validated()
        );

        return back()->with('success', 'Día de asistencia actualizado correctamente.');
    }

    /**
     * Actualiza varios días de una asistencia mensual con un mismo estado.
     */
    public function bulkUpdateDays(
        BulkUpdateAttendanceDaysRequest $request,
        MonthlyAttendance $monthlyAttendance
    ) {
        $this->service->bulkUpdateDays(
            attendance: $monthlyAttendance,
            dayIds: $request->input('day_ids'),
            statusCode: $request->input('status_code')
        );

        return back()->with('success', 'Los días seleccionados fueron actualizados correctamente.');
    }

    /**
     * Cierra la asistencia mensual.
     */
    public function close(MonthlyAttendance $monthlyAttendance, Request $request): RedirectResponse
    {
        $this->service->close(
            $monthlyAttendance,
            $request->user()?->id
        );

        return back()->with('success', 'Asistencia mensual cerrada correctamente.');
    }

    public function reopen(MonthlyAttendance $monthlyAttendance): RedirectResponse
    {
        $this->service->reopen($monthlyAttendance);

        return back()->with('success', 'Asistencia mensual reabierta correctamente. Realiza las correcciones y vuelve a cerrarla.');
    }

    private function canReopenAttendance(MonthlyAttendance $attendance): bool
    {
        $attendance->loadMissing(['status', 'payrollDetail.payroll.status']);

        if (! $attendance->isClosed()) {
            return false;
        }

        $payroll = $attendance->payrollDetail?->payroll;

        return ! $payroll || $payroll->isObserved() || $payroll->isRejected();
    }
}
