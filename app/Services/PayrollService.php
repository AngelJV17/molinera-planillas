<?php

namespace App\Services;

use App\Models\Catalog;
use App\Models\MonthlyAttendance;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\PayrollDetailConcept;
use App\Models\PayrollParameter;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    /**
     * Lista planillas con filtros simples para la pantalla principal.
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $periodParts = $this->parsePeriod($filters['period'] ?? null);

        return Payroll::query()
            ->with(['status:id,code,name'])
            ->withCount('details')
            ->when($periodParts['month'], fn(Builder $query) => $query->where('month', $periodParts['month']))
            ->when($periodParts['year'], fn(Builder $query) => $query->where('year', $periodParts['year']))
            ->when($filters['status_id'] ?? null, fn(Builder $query, $statusId) => $query->where('status_id', $statusId))
            ->latest()
            ->paginate(min((int) ($filters['per_page'] ?? 10), 100))
            ->withQueryString();
    }

    /**
     * Genera una planilla real desde asistencias mensuales cerradas.
     */
    public function generate(array $data, ?int $userId = null): Payroll
    {
        return DB::transaction(function () use ($data, $userId) {
            $month = (int) $data['month'];
            $year = (int) $data['year'];

            if (Payroll::period($month, $year)->exists()) {
                throw ValidationException::withMessages([
                    'period' => 'Ya hay una planilla generada para este periodo. Revisa el listado antes de crear otra. [PAY-001]',
                    'month' => 'Ya hay una planilla generada para este periodo. Revisa el listado antes de crear otra. [PAY-001]',
                ]);
            }

            $attendances = $this->closedAttendances($month, $year);

            if ($attendances->isEmpty()) {
                throw ValidationException::withMessages([
                    'period' => 'No hay asistencias cerradas listas para este periodo. Cierra las asistencias mensuales antes de generar la planilla. [PAY-002]',
                    'month' => 'No hay asistencias cerradas listas para este periodo. Cierra las asistencias mensuales antes de generar la planilla. [PAY-002]',
                ]);
            }

            $parameters = $this->activeParameters();
            $this->ensureRequiredParameters($parameters, $attendances);

            $payroll = Payroll::create([
                'code' => $this->generateCode($month, $year),
                'status_id' => $this->catalogId(Payroll::CATALOG_TYPE_STATUS, Payroll::STATUS_IN_REVIEW),
                'month' => $month,
                'year' => $year,
                'payment_date' => $data['payment_date'] ?? null,
                'observations' => $data['observations'] ?? null,
                'generated_by' => $userId,
            ]);

            foreach ($attendances as $attendance) {
                $this->createPayrollDetail($payroll, $attendance, $parameters);
            }

            $this->recalculateTotals($payroll);

            return $payroll->refresh()->load(['status', 'details.concepts.conceptType']);
        });
    }

    /**
     * Aprueba una planilla que se encuentra en revision.
     */
    public function approve(Payroll $payroll, ?int $userId = null): Payroll
    {
        return $this->transitionFromReview(
            payroll: $payroll,
            statusCode: Payroll::STATUS_APPROVED,
            userId: $userId,
            extra: ['rejection_reason' => null]
        );
    }

    /**
     * Rechaza una planilla en revision y guarda el motivo si existe.
     */
    public function reject(Payroll $payroll, ?int $userId = null, ?string $reason = null): Payroll
    {
        if (! $reason) {
            throw ValidationException::withMessages([
                'reason' => 'Ingresa el motivo del rechazo para dejar trazabilidad de la decision. [PAY-007]',
            ]);
        }

        return $this->transitionFromReview(
            payroll: $payroll,
            statusCode: Payroll::STATUS_REJECTED,
            userId: $userId,
            extra: ['rejection_reason' => $reason]
        );
    }

    public function observe(Payroll $payroll, ?int $userId = null, ?string $reason = null): Payroll
    {
        if (! $reason) {
            throw ValidationException::withMessages([
                'reason' => 'Ingresa la observacion que debe corregirse antes de aprobar la planilla. [PAY-008]',
            ]);
        }

        return $this->transitionFromReview(
            payroll: $payroll,
            statusCode: Payroll::STATUS_OBSERVED,
            userId: $userId,
            extra: ['rejection_reason' => $reason]
        );
    }

    public function recalculate(Payroll $payroll, ?int $userId = null): Payroll
    {
        return DB::transaction(function () use ($payroll, $userId) {
            $payroll->loadMissing('status');

            if (! $payroll->isObserved() && ! $payroll->isRejected()) {
                throw ValidationException::withMessages([
                    'payroll' => 'Solo puedes recalcular planillas observadas o rechazadas. [PAY-009]',
                ]);
            }

            $attendances = MonthlyAttendance::query()
                ->with(['employee.pensionSystem', 'status'])
                ->where('month', $payroll->month)
                ->where('year', $payroll->year)
                ->whereHas('status', fn(Builder $query) => $query->where('code', MonthlyAttendance::STATUS_CLOSED))
                ->orderBy('employee_id')
                ->get();

            if ($attendances->isEmpty()) {
                throw ValidationException::withMessages([
                    'payroll' => 'No hay asistencias cerradas para recalcular esta planilla. Cierra las asistencias corregidas y vuelve a intentarlo. [PAY-010]',
                ]);
            }

            $parameters = $this->activeParameters();
            $this->ensureRequiredParameters($parameters, $attendances);

            $payroll->details()->each(function (PayrollDetail $detail) {
                $detail->concepts()->delete();
                $detail->delete();
            });

            $payroll->update([
                'status_id' => $this->catalogId(Payroll::CATALOG_TYPE_STATUS, Payroll::STATUS_IN_REVIEW),
                'rejection_reason' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'paid_by' => null,
                'paid_at' => null,
                'generated_by' => $userId ?? $payroll->generated_by,
            ]);

            foreach ($attendances as $attendance) {
                $this->createPayrollDetail($payroll, $attendance, $parameters);
            }

            $this->recalculateTotals($payroll);

            return $payroll->refresh()->load(['status', 'details.concepts.conceptType']);
        });
    }

    /**
     * Marca como pagada solo una planilla previamente aprobada.
     */
    public function markAsPaid(Payroll $payroll, ?int $userId = null): Payroll
    {
        $payroll->loadMissing('status');

        if (! $payroll->isApproved()) {
            throw ValidationException::withMessages([
                'payroll' => 'Primero aprueba la planilla. Solo las planillas aprobadas pueden marcarse como pagadas. [PAY-003]',
            ]);
        }

        $payroll->update([
            'status_id' => $this->catalogId(Payroll::CATALOG_TYPE_STATUS, Payroll::STATUS_PAID),
            'paid_by' => $userId,
            'paid_at' => now(),
        ]);

        return $payroll->refresh();
    }

    public function statuses()
    {
        return Catalog::query()
            ->where('type', Payroll::CATALOG_TYPE_STATUS)
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }

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

    public function monthName(int $month): string
    {
        return collect($this->monthOptions())->firstWhere('value', $month)['label'] ?? 'Mes desconocido';
    }

    public function parsePeriod(?string $period): array
    {
        if (! $period || ! preg_match('/^\d{4}-\d{2}$/', $period)) {
            return ['month' => null, 'year' => null];
        }

        [$year, $month] = explode('-', $period);

        return ['month' => (int) $month, 'year' => (int) $year];
    }

    private function createPayrollDetail(Payroll $payroll, MonthlyAttendance $attendance, array $parameters): void
    {
        $employee = $attendance->employee;
        $baseSalary = round((float) $employee->base_salary, 2);

        if ($baseSalary <= 0) {
            throw ValidationException::withMessages([
                'payroll' => "No se puede calcular la planilla porque {$employee->full_name} no tiene un sueldo basico valido. Actualiza su ficha de trabajador. [PAY-005]",
            ]);
        }

        $dailyRate = round($baseSalary / 30, 2);
        $hourlyRate = round($dailyRate / 8, 2);
        $payableOvertimeHours = (float) ($attendance->payable_overtime_hours ?? $attendance->overtime_hours);
        $overtimeIncome = round($hourlyRate * $payableOvertimeHours * ($parameters['OVERTIME_RATE'] ?? 0), 2);
        $absenceDiscount = round($dailyRate * (int) $attendance->uncompensated_absence_days, 2);
        $pensionRate = $this->pensionRate($employee->pensionSystem?->code, $parameters);
        $pensionDiscount = round(($baseSalary + $overtimeIncome) * $pensionRate, 2);
        $employerContribution = round($baseSalary * $parameters['ESSALUD_RATE'], 2);
        $totalIncome = round($baseSalary + $overtimeIncome, 2);
        $totalDiscount = round($absenceDiscount + $pensionDiscount, 2);
        $netPay = round(max($totalIncome - $totalDiscount, 0), 2);

        $detail = PayrollDetail::create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employee->id,
            'monthly_attendance_id' => $attendance->id,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->full_name,
            'document_number' => $employee->document_number,
            'pension_system_code' => $employee->pensionSystem?->code,
            'pension_system_name' => $employee->pensionSystem?->name,
            'base_salary' => $baseSalary,
            'worked_days' => $attendance->worked_days,
            'absence_days' => $attendance->absence_days,
            'uncompensated_absence_days' => $attendance->uncompensated_absence_days,
            'rest_days' => $attendance->rest_days,
            'overtime_hours' => $payableOvertimeHours,
            'daily_rate' => $dailyRate,
            'hourly_rate' => $hourlyRate,
            'total_income' => $totalIncome,
            'total_discount' => $totalDiscount,
            'total_employer_contribution' => $employerContribution,
            'net_pay' => $netPay,
        ]);

        $this->createConcepts(
            detail: $detail,
            baseSalary: $baseSalary,
            overtimeIncome: $overtimeIncome,
            absenceDiscount: $absenceDiscount,
            pensionDiscount: $pensionDiscount,
            employerContribution: $employerContribution,
            pensionRate: $pensionRate,
            overtimeHours: $payableOvertimeHours,
            uncompensatedAbsences: (int) $attendance->uncompensated_absence_days
        );
    }

    private function createConcepts(
        PayrollDetail $detail,
        float $baseSalary,
        float $overtimeIncome,
        float $absenceDiscount,
        float $pensionDiscount,
        float $employerContribution,
        float $pensionRate,
        float $overtimeHours,
        int $uncompensatedAbsences
    ): void {
        $incomeType = $this->catalogId(PayrollDetailConcept::CATALOG_TYPE, PayrollDetailConcept::TYPE_INCOME);
        $discountType = $this->catalogId(PayrollDetailConcept::CATALOG_TYPE, PayrollDetailConcept::TYPE_DISCOUNT);
        $contributionType = $this->catalogId(PayrollDetailConcept::CATALOG_TYPE, PayrollDetailConcept::TYPE_EMPLOYER_CONTRIBUTION);

        $concepts = [
            [$incomeType, 'BASE_SALARY', 'Sueldo basico', 1, 0, $baseSalary, true, 10],
            [$incomeType, 'OVERTIME', 'Horas extra', $overtimeHours, 0, $overtimeIncome, true, 20],
            [$discountType, 'ABSENCE_DISCOUNT', 'Descuento por faltas', $uncompensatedAbsences, 0, $absenceDiscount, false, 30],
            [$discountType, 'PENSION_DISCOUNT', 'Descuento pensionario', 1, $pensionRate, $pensionDiscount, false, 40],
            [$contributionType, 'ESSALUD', 'Aporte EsSalud empleador', 1, 0, $employerContribution, false, 50],
        ];

        foreach ($concepts as [$typeId, $code, $name, $quantity, $rate, $amount, $taxable, $sortOrder]) {
            if ((float) $amount <= 0 && $code !== 'BASE_SALARY') {
                continue;
            }

            PayrollDetailConcept::create([
                'payroll_detail_id' => $detail->id,
                'concept_type_id' => $typeId,
                'code' => $code,
                'name' => $name,
                'quantity' => $quantity,
                'rate' => $rate,
                'amount' => $amount,
                'taxable' => $taxable,
                'sort_order' => $sortOrder,
            ]);
        }
    }

    private function closedAttendances(int $month, int $year)
    {
        return MonthlyAttendance::query()
            ->with(['employee.pensionSystem', 'status'])
            ->where('month', $month)
            ->where('year', $year)
            ->whereHas('status', fn(Builder $query) => $query->where('code', MonthlyAttendance::STATUS_CLOSED))
            ->whereDoesntHave('payrollDetail')
            ->orderBy('employee_id')
            ->get();
    }

    private function recalculateTotals(Payroll $payroll): void
    {
        $totals = $payroll->details()
            ->selectRaw('COUNT(*) as employee_count')
            ->selectRaw('COALESCE(SUM(base_salary), 0) as total_base_salary')
            ->selectRaw('COALESCE(SUM(total_income), 0) as total_income')
            ->selectRaw('COALESCE(SUM(total_discount), 0) as total_discount')
            ->selectRaw('COALESCE(SUM(total_employer_contribution), 0) as total_employer_contribution')
            ->selectRaw('COALESCE(SUM(net_pay), 0) as total_net')
            ->first();

        $payroll->update([
            'employee_count' => (int) $totals->employee_count,
            'total_base_salary' => $totals->total_base_salary,
            'total_income' => $totals->total_income,
            'total_discount' => $totals->total_discount,
            'total_employer_contribution' => $totals->total_employer_contribution,
            'total_net' => $totals->total_net,
        ]);
    }

    private function transitionFromReview(Payroll $payroll, string $statusCode, ?int $userId, array $extra = []): Payroll
    {
        $payroll->loadMissing('status');

        if (! $payroll->isInReview()) {
            throw ValidationException::withMessages([
                'payroll' => 'Esta accion solo esta disponible para planillas en revision. Actualiza la planilla desde su estado actual. [PAY-006]',
            ]);
        }

        $payroll->update(array_merge([
            'status_id' => $this->catalogId(Payroll::CATALOG_TYPE_STATUS, $statusCode),
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
        ], $extra));

        return $payroll->refresh();
    }

    private function activeParameters(): array
    {
        return PayrollParameter::active()
            ->pluck('value', 'code')
            ->map(fn($value) => (float) $value)
            ->toArray();
    }

    private function ensureRequiredParameters(array $parameters, $attendances): void
    {
        $required = collect(['ESSALUD_RATE']);

        if ($attendances->contains(fn(MonthlyAttendance $attendance) => $attendance->employee->pensionSystem?->code === 'ONP')) {
            $required->push('ONP_RATE');
        }

        if ($attendances->contains(fn(MonthlyAttendance $attendance) => str_starts_with($attendance->employee->pensionSystem?->code ?? '', 'AFP'))) {
            $required->push('AFP_RATE');
        }

        if ($attendances->contains(fn(MonthlyAttendance $attendance) => (float) ($attendance->payable_overtime_hours ?? $attendance->overtime_hours) > 0)) {
            $required->push('OVERTIME_RATE');
        }

        $missing = $required
            ->unique()
            ->reject(fn(string $code) => array_key_exists($code, $parameters))
            ->values();

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'payroll' => $this->missingPayrollParametersMessage($missing->all()),
            ]);
        }
    }

    private function pensionRate(?string $pensionCode, array $parameters): float
    {
        if ($pensionCode === 'ONP') {
            return $parameters['ONP_RATE'];
        }

        if ($pensionCode && str_starts_with($pensionCode, 'AFP')) {
            return $parameters['AFP_RATE'];
        }

        return 0.0;
    }

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

    private function generateCode(int $month, int $year): string
    {
        return 'PLA-' . $year . '-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT);
    }

    private function missingPayrollParametersMessage(array $missingCodes): string
    {
        $labels = [
            'ONP_RATE' => 'tasa ONP',
            'AFP_RATE' => 'tasa AFP',
            'ESSALUD_RATE' => 'aporte EsSalud',
            'OVERTIME_RATE' => 'factor de horas extra',
        ];

        $friendlyNames = collect($missingCodes)
            ->map(fn(string $code) => $labels[$code] ?? $code)
            ->implode(', ');

        return 'Falta configurar ' . $friendlyNames
            . ' para generar esta planilla. Solicita a soporte activar los parametros: '
            . implode(', ', $missingCodes)
            . '. [PAY-004]';
    }
}
