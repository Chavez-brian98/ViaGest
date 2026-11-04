<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPermiso
{
    /**
     * Valida si el empleado autenticado tiene el permiso para el módulo y acción requeridos.
     * Los administradores siempre tienen pase libre.
     */
    public function handle(Request $request, Closure $next, string $modulo, string $accion = 'ver'): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! $usuario->tienePermiso($modulo, $accion)) {
            abort(403, 'No tienes permisos suficientes para realizar esta acción.');
        }

        return $next($request);
    }
}
