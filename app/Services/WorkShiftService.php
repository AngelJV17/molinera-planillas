<?php

namespace App\Services;

use App\Models\WorkShift;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Gestiona las operaciones de negocio para los turnos laborales.
 */
class WorkShiftService
{
    /**
     * Lista turnos con filtros básicos y conteo de trabajadores asignados.
     */
    public function paginate(?string $search, ?string $status, int $perPage): LengthAwarePaginator
    {
        return WorkShift::query()
            ->with('rules')
            ->withCount('employees')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status !== null && $status !== '', fn ($query) => $query->where('status', (bool) $status))
            ->latest()
            ->paginate(min($perPage, 100))
            ->withQueryString();
    }

    /**
     * Registra un nuevo turno laboral.
     */
    public function create(array $data): WorkShift
    {
        return DB::transaction(function () use ($data) {
            $rules = $data['daily_rules'] ?? [];

            $workShift = WorkShift::create($this->shiftPayload($data));

            $this->syncRules($workShift, $rules);

            return $workShift->refresh()->load('rules');
        });
    }

    /**
     * Actualiza un turno laboral existente.
     */
    public function update(WorkShift $workShift, array $data): WorkShift
    {
        return DB::transaction(function () use ($workShift, $data) {
            $rules = $data['daily_rules'] ?? [];

            $workShift->update($this->shiftPayload($data));
            $this->syncRules($workShift, $rules);

            return $workShift->refresh()->load('rules');
        });
    }

    /**
     * Activa o desactiva un turno sin eliminarlo físicamente.
     */
    public function toggleStatus(WorkShift $workShift): WorkShift
    {
        $workShift->update([
            'status' => ! $workShift->status,
        ]);

        return $workShift;
    }

    private function shiftPayload(array $data): array
    {
        return Arr::except($data, ['daily_rules']);
    }

    private function syncRules(WorkShift $workShift, array $rules): void
    {
        if (! ($workShift->uses_daily_rules || $workShift->rotation_enabled)) {
            $workShift->rules()->delete();

            return;
        }

        $validDays = collect($rules)
            ->filter(fn(array $rule) => isset($rule['day_of_week']))
            ->keyBy(fn(array $rule) => (int) $rule['day_of_week']);

        foreach (range(1, 7) as $dayOfWeek) {
            $rule = $validDays->get($dayOfWeek, []);

            $workShift->rules()->updateOrCreate(
                ['day_of_week' => $dayOfWeek],
                [
                    'is_working_day' => (bool) ($rule['is_working_day'] ?? true),
                    'start_time' => $rule['start_time'] ?? $workShift->start_time?->format('H:i'),
                    'break_start_time' => $rule['break_start_time'] ?? $workShift->break_start_time?->format('H:i'),
                    'break_end_time' => $rule['break_end_time'] ?? $workShift->break_end_time?->format('H:i'),
                    'end_time' => $rule['end_time'] ?? $workShift->end_time?->format('H:i'),
                    'tolerance_minutes' => (int) ($rule['tolerance_minutes'] ?? $workShift->tolerance_minutes),
                    'expected_hours' => (float) ($rule['expected_hours'] ?? $workShift->daily_hours),
                    'crosses_midnight' => (bool) ($rule['crosses_midnight'] ?? $workShift->crosses_midnight),
                    'counts_as_full_day' => (bool) ($rule['counts_as_full_day'] ?? true),
                    'overtime_pay_enabled' => (bool) ($rule['overtime_pay_enabled'] ?? true),
                    'overtime_after_hours' => $rule['overtime_after_hours'] ?? null,
                ]
            );
        }
    }
}
