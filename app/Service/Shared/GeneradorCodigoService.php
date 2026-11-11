<?php

namespace App\Service\Shared;

use Illuminate\Support\Facades\DB;

class GeneradorCodigoService
{
    /**
     * Genera el siguiente código secuencial disponible para una tabla y prefijo dados.
     * Ejemplo: GeneradorCodigoService::siguiente('clientes', 'CLI-') -> 'CLI-0001'
     */
    public static function siguiente(
        string $tabla,
        string $prefijo,
        string $columna = 'codigo',
        int $longitudNumero = 4
    ): string {
        $prefijoEscapado = str_replace(['%', '_'], ['\%', '\_'], $prefijo);

        $codigos = DB::table($tabla)
            ->where($columna, 'like', $prefijoEscapado.'%')
            ->pluck($columna);

        $maxSecuencia = 0;
        $patron = '/^'.preg_quote($prefijo, '/').'(\d+)$/';

        foreach ($codigos as $codigo) {
            if (is_string($codigo) && preg_match($patron, $codigo, $coincidencias) === 1) {
                $maxSecuencia = max($maxSecuencia, (int) $coincidencias[1]);
            }
        }

        $siguienteNumero = $maxSecuencia + 1;

        // Si la tabla tiene IDs mayores o saltos, asegurarse de que no colisione
        do {
            $codigoGenerado = $prefijo.str_pad((string) $siguienteNumero, $longitudNumero, '0', STR_PAD_LEFT);
            $existe = DB::table($tabla)->where($columna, $codigoGenerado)->exists();
            $siguienteNumero++;
        } while ($existe);

        return $codigoGenerado;
    }
}
