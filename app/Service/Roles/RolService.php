<?php

namespace App\Service\Roles;

use App\Models\Rol;
use App\Service\Auditoria\AuditoriaService;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RolService
{
    private const POR_PAGINA = 12;

    public const MODULOS_ETIQUETAS = [
        'dashboard_resumen' => 'Dashboard: Resumen',
        'dashboard_alertas' => 'Dashboard: Alertas',
        'prospectos' => 'Prospectos',
        'clientes' => 'Clientes',
        'ventas_reservas' => 'Ventas y Reservas',
        'paquetes' => 'Paquetes',
        'inventario_cupos' => 'Inventario de Cupos',
        'vuelos' => 'Vuelos',
        'hospedajes' => 'Hospedajes',
        'transporte' => 'Transporte',
        'guias' => 'Guías turísticos',
        'gestion_documental' => 'Gestión Documental',
        'cuentas_por_cobrar' => 'Cuentas por Cobrar',
        'cuentas_por_pagar' => 'Cuentas por Pagar',
        'comisiones' => 'Comisiones',
        'reportes' => 'Reportes',
        'sucursales' => 'Sucursales',
        'empleados' => 'Empleados',
        'roles_permisos' => 'Roles y Permisos',
        'ajustes_generales' => 'Ajustes Generales',
        'notificaciones' => 'Notificaciones',
    ];

    public const ACCIONES = ['ver', 'crear', 'editar', 'eliminar'];

    public function listar(?string $buscar = null, array $filtros = []): LengthAwarePaginator
    {
        $buscar = trim((string) $buscar);
        $estado = (string) ($filtros['estado'] ?? '');
        $alcance = (string) ($filtros['alcance'] ?? '');

        return Rol::query()
            ->withCount('empleados')
            ->when($buscar !== '', function (Builder $query) use ($buscar): void {
                $like = '%'.$buscar.'%';
                $query->where(function (Builder $q) use ($like): void {
                    $q->where('nombre', 'like', $like)
                        ->orWhere('descripcion', 'like', $like);
                });
            })
            ->when($estado !== '', fn (Builder $q) => $q->where('estado', $estado))
            ->when($alcance !== '', fn (Builder $q) => $q->where('alcance', $alcance))
            ->orderBy('nombre')
            ->paginate(self::POR_PAGINA)
            ->onEachSide(1)
            ->withQueryString();
    }

    public function crear(array $datos): Rol
    {
        $permisos = $datos['permisos'] ?? [];
        unset($datos['permisos']);

        $datos['estado'] ??= 'activo';

        return DB::transaction(function () use ($datos, $permisos) {
            $rol = Rol::create($datos);

            $this->sincronizarPermisos($rol, $permisos);

            AuditoriaService::registrar([
                'empleado_id' => Auth::id(),
                'accion' => 'crear',
                'tabla' => 'roles',
                'registro_id' => $rol->id,
                'datos_nuevos' => array_merge($rol->toArray(), ['permisos' => $permisos]),
            ]);

            return $rol;
        });
    }

    public function actualizar(Rol $rol, array $datos): Rol
    {
        if ($rol->esAdministrador()) {
            // El rol administrador es inmutable en nombre y estado
            unset($datos['nombre'], $datos['estado']);
        }

        $permisos = $datos['permisos'] ?? [];
        unset($datos['permisos']);

        return DB::transaction(function () use ($rol, $datos, $permisos) {
            $anterior = array_merge($rol->toArray(), ['permisos' => $rol->permisosAgrupados()]);

            $rol->update($datos);
            $rol->refresh();

            // Los administradores siempre retienen todos los permisos
            if (! $rol->esAdministrador()) {
                $this->sincronizarPermisos($rol, $permisos);
            }

            AuditoriaService::registrar([
                'empleado_id' => Auth::id(),
                'accion' => 'actualizar',
                'tabla' => 'roles',
                'registro_id' => $rol->id,
                'datos_anteriores' => $anterior,
                'datos_nuevos' => array_merge($rol->toArray(), ['permisos' => $rol->permisosAgrupados()]),
            ]);

            return $rol;
        });
    }

    public function actualizarEstado(Rol $rol, string $estado): Rol
    {
        if ($rol->esAdministrador() && $estado === 'inactivo') {
            throw new DomainException('No se puede desactivar el rol de administrador.');
        }

        if ($rol->estado !== $estado) {
            $anterior = $rol->toArray();
            $rol->update(['estado' => $estado]);
            $rol->refresh();

            AuditoriaService::registrar([
                'empleado_id' => Auth::id(),
                'accion' => 'actualizar',
                'tabla' => 'roles',
                'registro_id' => $rol->id,
                'datos_anteriores' => $anterior,
                'datos_nuevos' => $rol->toArray(),
            ]);
        }

        return $rol;
    }

    public function deshabilitar(Rol $rol): void
    {
        $this->actualizarEstado($rol, 'inactivo');
    }

    public function eliminar(Rol $rol): void
    {
        if ($rol->esAdministrador()) {
            throw new DomainException('El rol de administrador es inmutable y no se puede eliminar.');
        }

        if ($rol->estado !== 'inactivo') {
            throw new DomainException('Primero deshabilita el rol antes de eliminarlo.');
        }

        if ($rol->empleados()->exists()) {
            throw new DomainException('No se puede eliminar: existen empleados asignados a este rol.');
        }

        $anterior = array_merge($rol->toArray(), ['permisos' => $rol->permisosAgrupados()]);

        DB::transaction(function () use ($rol, $anterior) {
            DB::table('rol_permisos')->where('rol_id', $rol->id)->delete();
            $rol->delete();

            AuditoriaService::registrar([
                'empleado_id' => Auth::id(),
                'accion' => 'eliminar',
                'tabla' => 'roles',
                'registro_id' => $rol->id,
                'datos_anteriores' => $anterior,
            ]);
        });
    }

    public function actualizarPermisos(Rol $rol, array $permisos): Rol
    {
        if ($rol->esAdministrador()) {
            throw new DomainException('Los permisos del rol administrador no pueden ser modificados.');
        }

        return DB::transaction(function () use ($rol, $permisos) {
            $anterior = ['permisos' => $rol->permisosAgrupados()];

            $this->sincronizarPermisos($rol, $permisos);

            AuditoriaService::registrar([
                'empleado_id' => Auth::id(),
                'accion' => 'actualizar',
                'tabla' => 'roles',
                'registro_id' => $rol->id,
                'datos_anteriores' => $anterior,
                'datos_nuevos' => ['permisos' => $rol->permisosAgrupados()],
            ]);

            return $rol;
        });
    }

    private function sincronizarPermisos(Rol $rol, array $permisosSeleccionados): void
    {
        DB::table('rol_permisos')->where('rol_id', $rol->id)->delete();

        $rows = [];
        $modulosValidos = array_keys(self::MODULOS_ETIQUETAS);

        foreach ($permisosSeleccionados as $modulo => $acciones) {
            if (! in_array($modulo, $modulosValidos, true) || ! is_array($acciones)) {
                continue;
            }

            foreach ($acciones as $accion) {
                if (in_array($accion, self::ACCIONES, true)) {
                    $rows[] = [
                        'rol_id' => $rol->id,
                        'modulo' => $modulo,
                        'accion' => $accion,
                    ];
                }
            }
        }

        if (! empty($rows)) {
            DB::table('rol_permisos')->insertOrIgnore($rows);
        }
    }
}
