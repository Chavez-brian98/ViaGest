<?php

namespace App\Service\Shared;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GestorArchivosService
{
    public const DISCO_DEFAULT = 'public';

    /**
     * Elimina un archivo del disco si existe.
     */
    public static function eliminar(?string $ruta, string $disco = self::DISCO_DEFAULT): void
    {
        if ($ruta && Storage::disk($disco)->exists($ruta)) {
            Storage::disk($disco)->delete($ruta);
        }
    }

    /**
     * Reemplaza un archivo existente o lo elimina si se solicita.
     * Devuelve la nueva ruta del archivo o null si fue eliminado o no se subió nuevo archivo.
     */
    public static function reemplazar(
        ?UploadedFile $nuevoArchivo,
        ?string $rutaAnterior,
        string $directorio,
        bool $eliminar = false,
        string $disco = self::DISCO_DEFAULT
    ): ?string {
        if ($nuevoArchivo) {
            $ruta = $nuevoArchivo->store($directorio, $disco);

            if (! is_string($ruta)) {
                throw new RuntimeException("No se pudo guardar el archivo en el directorio {$directorio}.");
            }

            self::eliminar($rutaAnterior, $disco);

            return $ruta;
        }

        if ($eliminar) {
            self::eliminar($rutaAnterior, $disco);

            return null;
        }

        return $rutaAnterior;
    }
}
