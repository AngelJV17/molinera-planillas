<?php
namespace App\Http\Controllers;

use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{

    public function __construct(
        protected RoleService $service
    ) {
    }

    /**
     * Muestra el listado principal de roles del sistema.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Roles/Index', [
            'roles' => $this->service->paginate(
                $request->search,
                $request->integer('per_page', 10),
            ),

            'filters' => [
                'search' => $request->search,
                'per_page' => $request->per_page,
            ],
        ]);
    }

    /**
     * Muestra el formulario para crear un rol.
     */
    public function create(): Response
    {
        return Inertia::render('Roles/Create', [
            'permissions' => $this->service->permissionsGrouped(),
        ]);
    }

    /**
     * Registra un nuevo rol.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()
            ->route('roles.index')
            ->with('success', 'Rol registrado correctamente.');
    }

    /**
     * Muestra el formulario para editar un rol.
     */
    public function edit(Role $role): Response
    {
        return Inertia::render('Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions()
                    ->pluck('name')
                    ->toArray(),
                'is_protected' => $this->service->isProtectedRole($role),
            ],

            'permissions' => $this->service->permissionsGrouped(),
        ]);
    }

    /**
     * Actualiza el rol y sus permisos.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->service->update($role, $request->validated());

        return redirect()
            ->route('roles.index')
            ->with('success', 'Rol actualizado correctamente.');
    }
}
