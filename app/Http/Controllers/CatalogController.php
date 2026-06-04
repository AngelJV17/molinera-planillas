<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreCatalogRequest;
use App\Http\Requests\UpdateCatalogRequest;
use App\Models\Catalog;
use App\Services\CatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalogService
    ) {
    }

    /**
     * Muestra el listado de catálogos con filtros.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only([
            'search',
            'status',
            'per_page',
        ]);

        return Inertia::render('Catalogs/Index', [
            'catalogs' => $this->catalogService->paginate($filters),
            'filters'  => $filters,
        ]);
    }

    /**
     * Muestra el formulario de creación.
     */
    public function create(): Response
    {
        return Inertia::render('Catalogs/Create');
    }

    /**
     * Guarda un nuevo catálogo.
     */
    public function store(StoreCatalogRequest $request): RedirectResponse
    {
        $this->catalogService->create($request->validated());

        return redirect()
            ->route('catalogs.index')
            ->with('success', 'Catálogo registrado correctamente.');
    }

    /**
     * Muestra el formulario de edición.
     */
    public function edit(Catalog $catalog): Response
    {
        return Inertia::render('Catalogs/Edit', [
            'catalog' => $catalog,
        ]);
    }

    /**
     * Actualiza un catálogo existente.
     */
    public function update(UpdateCatalogRequest $request, Catalog $catalog): RedirectResponse
    {
        $this->catalogService->update($catalog, $request->validated());

        return redirect()
            ->route('catalogs.index')
            ->with('success', 'Catálogo actualizado correctamente.');
    }

    /**
     * Activa o desactiva un catálogo.
     *
     * Evitamos eliminar registros para no afectar datos históricos.
     */
    public function toggleStatus(Catalog $catalog): RedirectResponse
    {
        $this->catalogService->toggleStatus($catalog);

        return back()->with('success', 'Estado del catálogo actualizado.');
    }
}
