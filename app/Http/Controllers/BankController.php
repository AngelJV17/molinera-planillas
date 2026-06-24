<?php
namespace App\Http\Controllers;

use App\Http\Requests\Bank\StoreBankRequest;
use App\Http\Requests\Bank\UpdateBankRequest;
use App\Models\Bank;
use App\Services\BankService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ==========================================================================
 * CONTROLADOR DE BANCOS
 * ==========================================================================
 *
 * Gestiona las entidades bancarias utilizadas por el sistema.
 */
class BankController extends Controller
{
    public function __construct(
        protected BankService $service
    ) {}

    /**
     * Listado de bancos.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Banks/Index', [
            'banks'   => $this->service->paginate(
                search: $request->search,
                status: $request->status,
                perPage: $request->integer('per_page', 10)
            ),

            'filters' => [
                'search'   => $request->search,
                'status'   => $request->status,
                'per_page' => $request->per_page,
            ],
        ]);
    }

    /**
     * Formulario de creación.
     */
    public function create(): Response
    {
        return Inertia::render('Banks/Create');
    }

    /**
     * Guardar banco.
     */
    public function store(StoreBankRequest $request): RedirectResponse {
        $this->service->create($request->validated());

        return redirect()
            ->route('organizational-structure.index')
            ->with(
                'success',
                'Banco registrado correctamente.'
            );
    }

    /**
     * Formulario edición.
     */
    public function edit(Bank $bank): Response
    {
        return Inertia::render('Banks/Edit', [
            'bank' => $bank,
        ]);
    }

    /**
     * Actualizar banco.
     */
    public function update(UpdateBankRequest $request,Bank $bank): RedirectResponse {
        $this->service->update($bank, $request->validated());

        return redirect()
            ->route('organizational - structure . index')
            ->with(
                'success',
                'Banco actualizado correctamente.'
            );
    }

    /**
     * Cambiar estado.
     */
    public function toggleStatus(Bank $bank): RedirectResponse {
        $this->service->toggleStatus($bank);

        return back()->with(
            'success',
            'Estado del banco actualizado.'
        );
    }
}
