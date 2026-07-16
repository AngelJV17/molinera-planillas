<?php
namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        protected UserService $service
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Users/Index', [
            'users'   => $this->service->paginate(
                $request->search,
                $request->status,
                $request->integer('per_page', 10),
            ),

            'filters' => [
                'search'   => $request->search,
                'status'   => $request->status,
                'per_page' => $request->per_page,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Users/Create', [
            'roles' => $this->service->roles(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $result = $this->service->create($request->validated());

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario registrado correctamente.')
            ->with('temporary_username', $result['user']->username)
            ->with('temporary_password', $result['temporary_password']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): Response
    {
        $this->ensureManageableUser($user);

        $user->load('roles:id,name');

        return Inertia::render('Users/Edit', [
            'user'  => [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
                'email'    => $user->email,
                'status'   => $user->status,
                'roles'    => $user->roles->pluck('name')->toArray(),
            ],

            'roles' => $this->service->roles(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->ensureManageableUser($user);

        $this->service->update($user, $request->validated());

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Toggle the status of the specified resource.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        $this->ensureManageableUser($user);

        $this->service->toggleStatus($user);

        return back()->with('success', 'Estado del usuario actualizado correctamente.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $this->ensureManageableUser($user);

        $temporaryPassword = $this->service->resetPassword($user);

        return redirect()
            ->route('users.index')
            ->with('success', 'Contraseña temporal restablecida correctamente.')
            ->with('temporary_username', $user->username)
            ->with('temporary_password', $temporaryPassword);
    }

    private function ensureManageableUser(User $user): void
    {
        abort_if(
            $user->hasRole(UserService::SUPPORT_ROLE),
            403,
            'La cuenta de soporte no puede administrarse desde este modulo.'
        );
    }
}
