<?php

namespace App\Service\Cupos;

use App\Service\Auditoria\AuditoriaService;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CupoService
{
    private const POR_PAGINA = 15;

    public function obtenerPaginado(array $filtros, int $porPagina = self::POR_PAGINA): LengthAwarePaginator
    {
        $query = DB::table('cupos as c')
            ->join('servicios as s', 's.id', '=', 'c.servicio_id')
            ->leftJoin('proveedores as p', 'p.id', '=', 's.proveedor_id')
            ->leftJoin('v_cupos_disponibles as vd', 'vd.cupo_id', '=', 'c.id')
            ->select(
                'c.*',
                's.codigo as servicio_codigo',
                's.nombre as servicio_nombre',
                's.tipo as servicio_tipo',
                'p.nombre as proveedor_nombre',
                DB::raw('COALESCE(vd.vendido, 0) as vendido'),
                DB::raw('COALESCE(vd.disponible, c.cantidad_total) as disponible')
            );

        $buscar = trim((string) ($filtros['buscar'] ?? ''));

        if ($buscar !== '') {
            $like = '%'.$buscar.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('s.codigo', 'like', $like)
                    ->orWhere('s.nombre', 'like', $like)
                    ->orWhere('p.nombre', 'like', $like);
            });
        }

        if (! empty($filtros['tipo'])) {
            $query->where('c.tipo', $filtros['tipo']);
        }

        if (! empty($filtros['estado'])) {
            $query->where('c.estado', $filtros['estado']);
        }

        if (! empty($filtros['servicio_id'])) {
            $query->where('c.servicio_id', (int) $filtros['servicio_id']);
        }

        return $query
            ->orderByDesc('c.fecha_inicio')
            ->orderBy('c.id', 'desc')
            ->paginate($porPagina)
            ->withQueryString();
    }

    public function serviciosParaSeleccion(?int $actualId = null): Collection
    {
        return DB::table('servicios as s')
            ->leftJoin('proveedores as p', 'p.id', '=', 's.proveedor_id')
            ->where(function ($query) use ($actualId): void {
                $query->where('s.estado', 'activo');

                if ($actualId !== null) {
                    $query->orWhere('s.id', $actualId);
                }
            })
            ->orderBy('s.nombre')
            ->get([
                's.id',
                's.codigo',
                's.nombre',
                's.tipo',
                'p.nombre as proveedor_nombre',
            ]);
    }

    public function crear(array $datos, int $empleadoId): int
    {
        return DB::transaction(function () use ($datos, $empleadoId) {
            $datos['estado'] ??= 'activo';
            $datos['created_at'] = now();
            $datos['updated_at'] = now();

            $cupoId = DB::table('cupos')->insertGetId($datos);

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'crear',
                'tabla' => 'cupos',
                'registro_id' => $cupoId,
                'datos_nuevos' => (array) DB::table('cupos')->find($cupoId),
            ]);

            return $cupoId;
        });
    }

    public function actualizar(int $cupoId, array $datos, int $empleadoId): void
    {
        DB::transaction(function () use ($cupoId, $datos, $empleadoId): void {
            $actual = DB::table('cupos')->find($cupoId);

            if (! $actual) {
                throw new DomainException('El cupo no existe.');
            }

            // Validar que la nueva cantidad total no sea menor que los cupos ya vendidos
            $vendidos = (int) DB::table('venta_servicios')
                ->where('cupo_id', $cupoId)
                ->where('estado', '<>', 'cancelado')
                ->sum('cantidad');

            if (isset($datos['cantidad_total']) && (int) $datos['cantidad_total'] < $vendidos) {
                throw new DomainException("La cantidad total no puede ser inferior a la cantidad ya vendida ({$vendidos}).");
            }

            $datos['updated_at'] = now();

            DB::table('cupos')->where('id', $cupoId)->update($datos);

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'actualizar',
                'tabla' => 'cupos',
                'registro_id' => $cupoId,
                'datos_anteriores' => (array) $actual,
                'datos_nuevos' => (array) DB::table('cupos')->find($cupoId),
            ]);
        });
    }

    public function cambiarEstado(int $cupoId, string $nuevoEstado, int $empleadoId): void
    {
        DB::transaction(function () use ($cupoId, $nuevoEstado, $empleadoId): void {
            $actual = DB::table('cupos')->find($cupoId);

            if (! $actual) {
                throw new DomainException('El cupo no existe.');
            }

            if (! in_array($nuevoEstado, ['activo', 'liberado', 'cerrado'], true)) {
                throw new DomainException('Estado no permitido.');
            }

            if ($actual->estado === $nuevoEstado) {
                return;
            }

            DB::table('cupos')->where('id', $cupoId)->update([
                'estado' => $nuevoEstado,
                'updated_at' => now(),
            ]);

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'actualizar',
                'tabla' => 'cupos',
                'registro_id' => $cupoId,
                'datos_anteriores' => (array) $actual,
                'datos_nuevos' => (array) DB::table('cupos')->find($cupoId),
            ]);
        });
    }

    public function eliminar(int $cupoId, int $empleadoId): void
    {
        DB::transaction(function () use ($cupoId, $empleadoId): void {
            $actual = DB::table('cupos')->find($cupoId);

            if (! $actual) {
                throw new DomainException('El cupo no existe.');
            }

            $tieneVentas = DB::table('venta_servicios')
                ->where('cupo_id', $cupoId)
                ->exists();

            if ($tieneVentas) {
                throw new DomainException('No se puede eliminar el cupo porque tiene ventas o reservas vinculadas.');
            }

            DB::table('cupos')->where('id', $cupoId)->delete();

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'eliminar',
                'tabla' => 'cupos',
                'registro_id' => $cupoId,
                'datos_anteriores' => (array) $actual,
            ]);
        });
    }
}
