<?php

namespace App\Service\Perfil;

use App\Models\Empleado;
use App\Models\Sucursal;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PerfilService
{
    private const DISCO = 'public';

    public function actualizar(Empleado $empleado, array $datos, ?UploadedFile $foto = null): Empleado
    {
        $eliminarFoto = (bool) ($datos['eliminar_foto'] ?? false);

        unset(
            $datos['foto'],
            $datos['eliminar_foto'],
            $datos['password_actual'],
            $datos['password_confirmation'],
        );

        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        if ($foto) {
            $ruta = $foto->store('perfiles/'.$empleado->getKey(), self::DISCO);

            if (! is_string($ruta)) {
                throw new RuntimeException('No se pudo guardar la foto de perfil.');
            }

            $this->eliminarArchivo($empleado->foto_perfil);
            $datos['foto_perfil'] = $ruta;
        } elseif ($eliminarFoto) {
            $this->eliminarArchivo($empleado->foto_perfil);
            $datos['foto_perfil'] = null;
        }

        $empleado->update($datos);

        return $empleado->refresh();
    }

    public function sucursales(?int $actualId = null): Collection
    {
        return Sucursal::query()
            ->where(function ($query) use ($actualId): void {
                $query->where('estado', 'activa');

                if ($actualId) {
                    $query->orWhere('id', $actualId);
                }
            })
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'estado']);
    }

    private function eliminarArchivo(?string $ruta): void
    {
        if ($ruta && Storage::disk(self::DISCO)->exists($ruta)) {
            Storage::disk(self::DISCO)->delete($ruta);
        }
    }
}
