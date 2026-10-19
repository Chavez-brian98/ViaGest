<?php

namespace App\Service\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginService
{
    public function authenticate(array $credentials, bool $remember = false): bool
    {
        $authenticated = Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'acceso' => 'activo',
        ], $remember);

        if (! $authenticated) {
            return false;
        }

        session()->regenerate();

        Auth::user()?->forceFill(['ultimo_acceso_at' => now()])->save();

        return true;
    }

    public function logout(Request $request): void
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
