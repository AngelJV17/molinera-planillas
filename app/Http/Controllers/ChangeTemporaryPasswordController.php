<?php
namespace App\Http\Controllers;

use App\Http\Requests\UpdateTemporaryPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class ChangeTemporaryPasswordController extends Controller
{
    public function edit(Request $request): Response | RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->must_change_password) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/ChangeTemporaryPassword');
    }

    public function update(UpdateTemporaryPasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->update([
            'password'             => Hash::make($request->validated('password')),
            'must_change_password' => false,
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Contraseña actualizada correctamente.');
    }
}
