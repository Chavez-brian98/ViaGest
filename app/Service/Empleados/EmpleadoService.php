<?php

namespace App\Service\Empleados;

use App\Models\Empleado;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Service\Auditoria\AuditoriaService;
use App\Service\Roles\RolService;
use App\Service\Shared\GeneradorCodigoService;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmpleadoService
{
    private const POR_PAGINA = 12;

    public function listar(?string $buscar = null, array $filtros = []): LengthAwarePaginator
    {
        $buscar = trim((string) $buscar);
        $estado = (string) ($filtros['estado'] ?? '');
        $sucursalId = (string) ($filtros['sucursal_id'] ?? '');

        return Empleado::query()
            ->with(['sucursal', 'rol'])
            ->when($buscar !== '', function (Builder $query) use ($buscar): void {
                $like = '%'.$buscar.'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query->where('codigo', 'like', $like)
                        ->orWhere('nombres', 'like', $like)
                        ->orWhere('apellidos', 'like', $like)
                        ->orWhere('numero_documento', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('telefono', 'like', $like)
                        ->orWhere('cargo', 'like', $like);
                });
            })
            ->when($estado !== '', fn (Builder $query) => $query->where('estado', $estado))
            ->when($sucursalId !== '', fn (Builder $query) => $query->where('sucursal_id', (int) $sucursalId))
            ->orderByRaw('apellidos asc')
            ->orderBy('nombres')
            ->paginate(self::POR_PAGINA)
            ->onEachSide(1)
            ->withQueryString();
    }

    public function crear(array $datos): Empleado
    {
        $datos['estado'] ??= 'activo';
        if (empty($datos['password'])) {
            unset($datos['password']);
        } else {
            $datos['acceso'] ??= 'activo';
        }

        $empleado = Empleado::create($datos);

        AuditoriaService::registrar([
            'empleado_id' => Auth::id(),
            'accion' => 'crear',
            'tabla' => 'empleados',
            'registro_id' => $empleado->id,
            'datos_nuevos' => $empleado->toArray(),
        ]);

        return $empleado;
    }

    public function actualizar(Empleado $empleado, array $datos): Empleado
    {
        $anterior = $empleado->toArray();

        if (empty($datos['password'])) {
            unset($datos['password']);
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

    public function actualizarEstado(Empleado $empleado, string $estado): Empleado
    {
        if ($empleado->estado !== $estado) {
            $anterior = $empleado->toArray();

            $empleado->update(['estado' => $estado]);
            $empleado->refresh();

            AuditoriaService::registrar([
                'empleado_id' => Auth::id(),
                'accion' => 'actualizar',
                'tabla' => 'empleados',
                'registro_id' => $empleado->id,
                'datos_anteriores' => $anterior,
                'datos_nuevos' => $empleado->toArray(),
            ]);
        }

        return $empleado;
    }

    public function deshabilitar(Empleado $empleado): void
    {
        $this->actualizarEstado($empleado, 'inactivo');
    }

    public function actualizarPermisos(Empleado $empleado, array $permisosSeleccionados): Empleado
    {
        if ($empleado->esAdministrador()) {
            throw new DomainException('No se pueden sobrescribir permisos para el administrador principal.');
        }

        return DB::transaction(function () use ($empleado, $permisosSeleccionados) {
            $anterior = ['overrides' => $empleado->overridesPermisos()];

            DB::table('empleado_permisos')->where('empleado_id', $empleado->id)->delete();

            $modulosValidos = array_keys(RolService::MODULOS_ETIQUETAS);
            $accionesValidas = RolService::ACCIONES;

            $permisosRol = [];
            if ($empleado->rol_id) {
                $filasRol = DB::table('rol_permisos')
                    ->where('rol_id', $empleado->rol_id)
                    ->get();
                foreach ($filasRol as $fr) {
                    $permisosRol[$fr->modulo][$fr->accion] = true;
                }
            }

            $rows = [];

            // Detectar si los permisos vienen como lista de acciones [modulo => ['ver', 'crear']] (igual que roles)
            // o como mapa asociativo [modulo => ['ver' => 'permitir'|'denegar']] (formato anterior)
            $esFormatoLista = false;
            foreach ($permisosSeleccionados as $acciones) {
                if (is_array($acciones) && ! empty($acciones)) {
                    $primerKey = array_key_first($acciones);
                    if (is_int($primerKey)) {
                        $esFormatoLista = true;
                    }
                    break;
                }
            }

            if ($esFormatoLista || empty($permisosSeleccionados)) {
                // Matriz idéntica a Roles: cada casilla marcada significa que el empleado debe tener ese permiso.
                // Si el empleado no tiene rol, o el rol no lo tiene y se marcó => efecto 'permitir'.
                // Si el rol lo tiene y NO se marcó => efecto 'denegar'.
                // Si coincide con lo que el rol ya otorga o no otorga => no se inserta override (permanece limpio).
                foreach ($modulosValidos as $modulo) {
                    $accionesElegidas = isset($permisosSeleccionados[$modulo]) && is_array($permisosSeleccionados[$modulo])
                        ? $permisosSeleccionados[$modulo]
                        : [];

                    foreach ($accionesValidas as $accion) {
                        $marcado = in_array($accion, $accionesElegidas, true);
                        $rolTiene = isset($permisosRol[$modulo][$accion]);

                        if ($marcado && ! $rolTiene) {
                            $rows[] = [
                                'empleado_id' => $empleado->id,
                                'modulo' => $modulo,
                                'accion' => $accion,
                                'efecto' => 'permitir',
                            ];
                        } elseif (! $marcado && $rolTiene) {
                            $rows[] = [
                                'empleado_id' => $empleado->id,
                                'modulo' => $modulo,
                                'accion' => $accion,
                                'efecto' => 'denegar',
                            ];
                        }
                    }
                }
            } else {
                // Formato asociativo [modulo => [accion => 'permitir'|'denegar']]
                foreach ($permisosSeleccionados as $modulo => $acciones) {
                    if (! in_array($modulo, $modulosValidos, true) || ! is_array($acciones)) {
                        continue;
                    }

                    foreach ($acciones as $accion => $efecto) {
                        if (in_array($accion, $accionesValidas, true) && in_array($efecto, ['permitir', 'denegar'], true)) {
                            $rows[] = [
                                'empleado_id' => $empleado->id,
                                'modulo' => $modulo,
                                'accion' => $accion,
                                'efecto' => $efecto,
                            ];
                        }
                    }
                }
            }

            if (! empty($rows)) {
                DB::table('empleado_permisos')->insertOrIgnore($rows);
            }

            AuditoriaService::registrar([
                'empleado_id' => Auth::id(),
                'accion' => 'actualizar',
                'tabla' => 'empleado_permisos',
                'registro_id' => $empleado->id,
                'datos_anteriores' => $anterior,
                'datos_nuevos' => ['overrides' => $empleado->overridesPermisos()],
            ]);

            return $empleado;
        });
    }

    public function eliminar(Empleado $empleado): void
    {
        if ($empleado->estado !== 'inactivo') {
            throw new DomainException('Primero deshabilita al empleado antes de eliminarlo.');
        }

        // En este sistema, Empleado es la entidad de Auth,
        // por lo que debemos verificar que no sea el último administrador o tenga dependencias críticas.
        // Por ahora seguimos el patrón de Cliente.

        $anterior = $empleado->toArray();

        $empleado->delete();

        AuditoriaService::registrar([
            'empleado_id' => Auth::id(),
            'accion' => 'eliminar',
            'tabla' => 'empleados',
            'registro_id' => $empleado->id,
            'datos_anteriores' => $anterior,
        ]);
    }

    public function sucursalesActivas(): Collection
    {
        return Sucursal::query()
            ->where('estado', 'activa')
            ->orderBy('nombre')
            ->get();
    }

    public function rolesActivos(): Collection
    {
        return Rol::query()
            ->where('estado', 'activo')
            ->orderBy('nombre')
            ->get();
    }

    public function siguienteCodigo(): string
    {
        return GeneradorCodigoService::siguiente(Empleado::class, 'EMP-');
    }
}
