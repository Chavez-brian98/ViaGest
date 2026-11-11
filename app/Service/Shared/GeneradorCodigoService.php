<?php

namespace App\Service\Shared;

class GeneradorCodigoService
{
    /**
     * Genera el siguiente código secuencial disponible para un modelo y prefijo dados.
     * Ejemplo: GeneradorCodigoService::siguiente(Cliente::class, 'CLI-') -> 'CLI-0001'
     */
    public static function siguiente(
        string $modelo,
        string $prefijo,
        string $columna = 'codigo',
        int $longitudNumero = 4
    ): string {
        $prefijoEscapado = str_replace(['%', '_'], ['\%', '\_'], $prefijo);

        $codigos = $modelo::query()
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

        do {
            $codigoGenerado = $prefijo.str_pad((string) $siguienteNumero, $longitudNumero, '0', STR_PAD_LEFT);
            $existe = $modelo::query()->where($columna, $codigoGenerado)->exists();
            $siguienteNumero++;
        } while ($existe);

        return $codigoGenerado;
    }
}
