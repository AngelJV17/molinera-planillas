<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Inertia\Support\Header;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isDocumentNavigation($request)) {
            $request->headers->remove(Header::INERTIA);
            $request->headers->remove(Header::VERSION);
        }

        return parent::handle($request, $next);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $this->sharedUser($request),
                'permissions' => fn () => $request->user()
                    ? $request->user()->getAllPermissions()->pluck('name')->values()
                    : [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
                'status' => fn () => $request->session()->get('status'),
                'temporary_username' => fn () => $request->session()->get('temporary_username'),
                'temporary_password' => fn () => $request->session()->get('temporary_password'),
            ],

        ];
    }

    /**
     * Datos minimos del usuario autenticado para la interfaz.
     *
     * Prioridad de etiqueta:
     * - Cargo del trabajador vinculado, si existe.
     * - Rol asignado, si es una cuenta administrativa sin ficha laboral.
     */
    private function sharedUser(Request $request): ?array
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        $user->loadMissing(['employee.position']);

        $roles = $user->getRoleNames()->values();
        $position = $user->employee?->position?->name;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'status' => $user->status,
            'roles' => $roles,
            'employee' => $user->employee ? [
                'id' => $user->employee->id,
                'code' => $user->employee->employee_code,
                'position' => $position,
            ] : null,
            'display_label' => $position ?: $roles->join(', ') ?: 'Usuario sin rol',
        ];
    }

    private function isDocumentNavigation(Request $request): bool
    {
        if (! $request->isMethod('GET') || ! $request->header(Header::INERTIA)) {
            return false;
        }

        return $request->headers->get('Sec-Fetch-Dest') === 'document'
            || $request->headers->get('Sec-Fetch-Mode') === 'navigate';
    }
}
