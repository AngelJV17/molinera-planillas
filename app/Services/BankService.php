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
    public function paginate(
        ?string $search,
        ?string $status,
        int $perPage
    ): LengthAwarePaginator {

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

            ->when(
                $status !== null &&
                $status !== '',
                fn($query) => $query->where(
                    'status',
                    $status
                )
            )

            ->latest()

            ->paginate($perPage)

            ->withQueryString();
    }

    /**
     * Cambiar estado.
     */
    public function toggleStatus(
        Bank $bank
    ): void {

        $bank->update([
            'status' => ! $bank->status,
        ]);
    }
}
