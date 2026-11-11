<?php

namespace App\Service\Perfil;

use App\Models\Empleado;
use App\Models\Sucursal;
use App\Service\Auditoria\AuditoriaService;
use App\Service\Shared\GestorArchivosService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class PerfilService
{
    public function actualizar(Empleado $empleado, array $datos, ?UploadedFile $foto = null): Empleado
    {
        $anterior = $empleado->toArray();
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

        if ($foto || $eliminarFoto) {
            $datos['foto_perfil'] = GestorArchivosService::reemplazar(
                $foto,
                $empleado->foto_perfil,
                'perfiles/'.$empleado->getKey(),
                $eliminarFoto
            );
        }

        $empleado->update($datos);
        $empleado->refresh();

        AuditoriaService::registrar([
            'empleado_id' => Auth::id(),
            'accion' => 'actualizar',
            'tabla' => 'empleados',
            'registro_id' => $empleado->id,
            'datos_anteriores' => $anterior,
            'datos_nuevos' => $empleado->toArray(),
        ]);

        return $empleado;
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
}
