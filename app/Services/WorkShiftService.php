<?php

namespace App\Services;

use App\Models\WorkShift;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
        return WorkShift::create($data);
    }

    /**
     * Actualiza un turno laboral existente.
     */
    public function update(WorkShift $workShift, array $data): WorkShift
    {
        $workShift->update($data);

        return $workShift;
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
}
