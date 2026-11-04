<?php

namespace App\Service\Auth;

use App\Service\Auditoria\AuditoriaService;
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

        AuditoriaService::registrar([
            'empleado_id' => Auth::id(),
            'accion' => 'inicio_sesion',
        ]);

        return true;
    }

    public function logout(Request $request): void
    {
        $empleadoId = Auth::id();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        AuditoriaService::registrar([
            'empleado_id' => $empleadoId,
            'accion' => 'cierre_sesion',
        ]);
    }
}
