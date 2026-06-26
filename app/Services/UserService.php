<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserService
{
    public function paginate(?string $search, ?string $status, int $perPage = 10): LengthAwarePaginator
    {
        return User::query()
            ->with('roles:id,name')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $query->where('status', (bool) $status);
            })
            ->latest()
            ->paginate(min($perPage, 100))
            ->withQueryString();
    }

    public function roles(): array
    {
        return Role::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn(Role $role) => [
                'id'   => $role->id,
                'name' => $role->name,
            ])
            ->toArray();
    }

    public function create(array $data): array
    {
        return DB::transaction(function () use ($data) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $temporaryPassword = $this->generateTemporaryPassword();

            $user = User::create([
                'name'                 => $data['name'],
                'username'             => $data['username'],
                'email'                => $data['email'] ?? null,
                'password'             => $temporaryPassword,
                'status'               => $data['status'] ?? true,
                'must_change_password' => true,
            ]);

            $user->syncRoles($data['roles'] ?? []);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return [
                'user'               => $user,
                'temporary_password' => $temporaryPassword,
            ];
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $user->update([
                'name'     => $data['name'],
                'username' => $data['username'],
                'email'    => $data['email'] ?? null,
                'status'   => $data['status'] ?? true,
            ]);

            $user->syncRoles($data['roles'] ?? []);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $user;
        });
    }

    public function toggleStatus(User $user): void
    {
        $user->update([
            'status' => ! $user->status,
        ]);
    }

    private function generateTemporaryPassword(): string
    {
        $prefix     = 'MOLI';
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $letters    = '';

        for ($i = 0; $i < 4; $i++) {
            $letters .= $characters[random_int(0, strlen($characters) - 1)];
        }

        $numbers = random_int(1000, 9999);
        $symbols = ['@', '#', '*'];
        $symbol  = $symbols[array_rand($symbols)];

        return "{$prefix}-{$letters}-{$numbers}{$symbol}";
    }

    public function resetPassword(User $user): string
    {
        return DB::transaction(function () use ($user) {
            $temporaryPassword = $this->generateTemporaryPassword();

            $user->update([
                'password'             => $temporaryPassword,
                'must_change_password' => true,
            ]);

            return $temporaryPassword;
        });
    }
}
