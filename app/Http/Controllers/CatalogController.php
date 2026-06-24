<?php
namespace App\Http\Controllers;

use App\Http\Requests\Catalog\StoreCatalogRequest;
use App\Http\Requests\Catalog\UpdateCatalogRequest;
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
     * Muestra el listado de catálogos filtrado por categoría.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only([
            'type',
            'search',
            'status',
            'per_page',
        ]);

        $filters['type'] = $filters['type'] ?? 'DOCUMENT_TYPE';

        return Inertia::render('Catalogs/Index', [
            'catalogs' => $this->catalogService->paginate($filters),
            'filters'  => $filters,
        ]);
    }

    /**
     * Muestra el formulario de creación.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Catalogs/Create', [
            'type' => $request->query('type', 'DOCUMENT_TYPE'),
        ]);
    }

    /**
     * Guarda un nuevo catálogo.
     */
    public function store(StoreCatalogRequest $request): RedirectResponse
    {
        $catalog = $this->catalogService->create($request->validated());

        return redirect()
            ->route('catalogs.index', ['type' => $catalog->type])
            ->with('success', 'Registro creado correctamente.');
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
            ->route('catalogs.index', ['type' => $catalog->type])
            ->with('success', 'Registro actualizado correctamente.');
    }

    /**
     * Activa o desactiva un catálogo.
     */
    public function toggleStatus(Catalog $catalog): RedirectResponse
    {
        $this->catalogService->toggleStatus($catalog);

        return back()->with('success', 'Estado actualizado correctamente.');
    }
}
