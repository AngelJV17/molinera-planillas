<?php
namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleService
{
    /**
     * Lista roles con búsqueda, paginación y conteo de permisos/usuarios.
     */
    public function paginate(?string $search, int $perPage = 10): LengthAwarePaginator
    {
        return Role::query()
            ->withCount([
                'permissions',
                'users',
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(min($perPage, 100))
            ->withQueryString();
    }

    /**
     * Obtiene permisos agrupados por módulo para el formulario.
     */
    public function permissionsGrouped(): Collection
    {
        return Permission::query()
            ->orderBy('name')
            ->get(['id', 'name', 'guard_name'])
            ->groupBy(function (Permission $permission) {
                return explode('.', $permission->name)[0];
            })
            ->map(function ($permissions, string $module) {
                return [
                    'module'      => $module,
                    'label'       => $this->moduleLabel($module),
                    'permissions' => $permissions->map(function (Permission $permission) {
                        return [
                            'id'         => $permission->id,
                            'name'       => $permission->name,
                            'label'      => $this->permissionLabel($permission->name),
                            'guard_name' => $permission->guard_name,
                        ];
                    })->values(),
                ];
            })
            ->values();
    }

    /**
     * Crea un rol y sincroniza sus permisos.
     */
    public function create(array $data): Role
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create([
            'name'       => $data['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $role;
    }

    /**
     * Actualiza un rol y sincroniza sus permisos.
     */
    public function update(Role $role, array $data): Role
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if (! $this->isProtectedRole($role)) {
            $role->update([
                'name' => $data['name'],
            ]);
        }

        $role->syncPermissions($data['permissions'] ?? []);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $role;
    }

    /**
     * Identifica roles base que no deberían renombrarse.
     */
    public function isProtectedRole(Role $role): bool
    {
        return in_array($role->name, [
            'Super Admin',
            'Administrador',
        ], true);
    }

    /**
     * Nombres amigables por módulo.
     */
    private function moduleLabel(string $module): string
    {
        return match ($module) {
            'dashboard'     => 'Dashboard',
            'catalogs'      => 'Catálogos',
            'banks'         => 'Bancos',
            'work-shifts'   => 'Turnos de trabajo',
            'workers'       => 'Trabajadores',
            'attendance'    => 'Asistencia',
            'payrolls'      => 'Planillas',
            'payroll-parameters' => 'Parametros de planilla',
            'payment-slips' => 'Boletas de pago',
            'reports'       => 'Reportes',
            'users'         => 'Usuarios',
            'roles'         => 'Roles y permisos',
            default         => ucfirst(str_replace('-', ' ', $module)),
        };
    }

    /**
     * Traduce permisos técnicos a textos comprensibles.
     */
    private function permissionLabel(string $permission): string
    {
        $action = str($permission)->after('.')->toString();

        return match ($action) {
            'view'               => 'Ver',
            'create'             => 'Crear',
            'edit'               => 'Editar',
            'update'             => 'Actualizar',
            'delete'             => 'Eliminar',
            'toggle-status'      => 'Cambiar estado',
            'close'              => 'Cerrar',
            'review'             => 'Revisar',
            'approve'            => 'Aprobar',
            'reject'             => 'Rechazar',
            'pay'                => 'Marcar como pagado',
            'generate'           => 'Generar',
            'download'           => 'Descargar',
            'export'             => 'Exportar',
            'assign-permissions' => 'Asignar permisos',
            default              => ucfirst(str_replace('-', ' ', $action)),
        };
    }
}
