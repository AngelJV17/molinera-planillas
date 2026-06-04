<?php
namespace App\Services;

use App\Models\Catalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CatalogService
{
    /**
     * Lista los catálogos con filtros básicos.
     *
     * Esta lógica se separa del controlador para mantenerlo limpio
     * y facilitar futuras mejoras como filtros avanzados.
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Catalog::query()
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('type', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(isset($filters['status']) && $filters['status'] !== '', function ($query) use ($filters) {
                $query->where('status', (bool) $filters['status']);
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();
    }

    /**
     * Registra una nueva opción de catálogo.
     */
    public function create(array $data): Catalog
    {
        return Catalog::create($data);
    }

    /**
     * Actualiza una opción de catálogo existente.
     */
    public function update(Catalog $catalog, array $data): Catalog
    {
        $catalog->update($data);

        return $catalog;
    }

    /**
     * Activa o desactiva un catálogo sin eliminarlo físicamente.
     *
     * Esto permite conservar el historial y evitar problemas con relaciones.
     */
    public function toggleStatus(Catalog $catalog): Catalog
    {
        $catalog->update([
            'status' => ! $catalog->status,
        ]);

        return $catalog;
    }
}
