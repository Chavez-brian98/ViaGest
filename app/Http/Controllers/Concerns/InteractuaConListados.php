<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

trait InteractuaConListados
{
    /**
     * Filtra los parámetros eliminando cadenas vacías y nulos.
     */
    protected function limpiarParametros(array $parametros): array
    {
        return array_filter(
            $parametros,
            fn ($valor) => $valor !== '' && $valor !== null
        );
    }

    /**
     * Redirige al listado de un módulo conservando filtros activos.
     */
    protected function redireccionarAListado(string $ruta, array $parametros = []): RedirectResponse
    {
        return redirect()->route($ruta, $this->limpiarParametros($parametros));
    }

    /**
     * Genera una respuesta JSON unificada para cambios de estado, compatible con todos los clientes JS.
     */
    protected function respuestaJsonEstado(string $estado, string $mensaje, bool $exito = true, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => $exito,
            'estado' => $estado,
            'mensaje' => $mensaje,
            'message' => $mensaje,
        ], $status);
    }

    /**
     * Genera una respuesta JSON unificada para errores de dominio en peticiones AJAX.
     */
    protected function respuestaJsonError(string $mensaje, int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $mensaje,
            'message' => $mensaje,
        ], $status);
    }
}
