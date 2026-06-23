<?php

namespace App\Services;

use App\Models\Bank;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * ==========================================================================
 * SERVICIO DE BANCOS
 * ==========================================================================
 */
class BankService
{
    /**
     * Listado paginado.
     */
    public function paginate(?string $search, ?string $status, int $perPage): LengthAwarePaginator
    {

        return Bank::query()

            ->when($search, function ($query) use ($search) {

                $query->where(function ($query) use ($search) {

                    $query->where(
                        'name',
                        'like',
                        "%{$search}%"
                    )
                        ->orWhere(
                            'code',
                            'like',
                            "%{$search}%"
                        );
                });
            })

            ->when($status !== null && $status !== '', fn ($query) => $query->where('status', (bool) $status))

            ->latest()

            ->paginate(min($perPage, 100))

            ->withQueryString();
    }

    /**
     * Registra una entidad bancaria.
     */
    public function create(array $data): Bank
    {
        return Bank::create($data);
    }

    /**
     * Actualiza una entidad bancaria existente.
     */
    public function update(Bank $bank, array $data): Bank
    {
        $bank->update($data);

        return $bank;
    }

    /**
     * Cambiar estado.
     */
    public function toggleStatus(Bank $bank): Bank
    {

        $bank->update([
            'status' => ! $bank->status,
        ]);

        return $bank;
    }
}
