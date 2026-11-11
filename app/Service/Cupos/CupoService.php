<?php

namespace App\Service\Cupos;

use App\Models\Cupo;
use App\Models\Servicio;
use App\Models\VentaServicio;
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
        $query = Cupo::query()
            ->join('servicios as s', 's.id', '=', 'cupos.servicio_id')
            ->leftJoin('proveedores as p', 'p.id', '=', 's.proveedor_id')
            ->leftJoin('v_cupos_disponibles as vd', 'vd.cupo_id', '=', 'cupos.id')
            ->select(
                'cupos.*',
                's.codigo as servicio_codigo',
                's.nombre as servicio_nombre',
                's.tipo as servicio_tipo',
                'p.nombre as proveedor_nombre',
                DB::raw('COALESCE(vd.vendido, 0) as vendido'),
                DB::raw('COALESCE(vd.disponible, cupos.cantidad_total) as disponible')
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
            $query->where('cupos.tipo', $filtros['tipo']);
        }

        if (! empty($filtros['estado'])) {
            $query->where('cupos.estado', $filtros['estado']);
        }

        if (! empty($filtros['servicio_id'])) {
            $query->where('cupos.servicio_id', (int) $filtros['servicio_id']);
        }

        return $query
            ->orderByDesc('cupos.fecha_inicio')
            ->orderBy('cupos.id', 'desc')
            ->paginate($porPagina)
            ->withQueryString();
    }

    public function serviciosParaSeleccion(?int $actualId = null): Collection
    {
        return Servicio::query()
            ->leftJoin('proveedores as p', 'p.id', '=', 'servicios.proveedor_id')
            ->where(function ($query) use ($actualId): void {
                $query->where('servicios.estado', 'activo');

                if ($actualId !== null) {
                    $query->orWhere('servicios.id', $actualId);
                }
            })
            ->orderBy('servicios.nombre')
            ->get([
                'servicios.id',
                'servicios.codigo',
                'servicios.nombre',
                'servicios.tipo',
                'p.nombre as proveedor_nombre',
            ]);
    }

    public function crear(array $datos, ?int $empleadoId = null): Cupo
    {
        return DB::transaction(function () use ($datos, $empleadoId) {
            $datos['estado'] ??= 'activo';

            $cupo = Cupo::create($datos);

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'crear',
                'tabla' => 'cupos',
                'registro_id' => $cupo->id,
                'datos_nuevos' => $cupo->toArray(),
            ]);

            return $cupo;
        });
    }

    public function actualizar(Cupo $cupo, array $datos, ?int $empleadoId = null): Cupo
    {
        return DB::transaction(function () use ($cupo, $datos, $empleadoId) {
            $vendidos = (int) VentaServicio::query()
                ->where('cupo_id', $cupo->id)
                ->where('estado', '<>', 'cancelado')
                ->sum('cantidad');

            if (isset($datos['cantidad_total']) && (int) $datos['cantidad_total'] < $vendidos) {
                throw new DomainException("La cantidad total no puede ser inferior a la cantidad ya vendida ({$vendidos}).");
            }

            if ($vendidos > 0) {
                foreach (['servicio_id', 'fecha_inicio', 'fecha_fin'] as $campo) {
                    if (array_key_exists($campo, $datos) && (string) $datos[$campo] !== (string) $cupo->getRawOriginal($campo)) {
                        throw new DomainException('El cupo tiene ventas vinculadas, por lo que el servicio y las fechas de vigencia no pueden modificarse.');
                    }
                }
            }

            $anterior = $cupo->toArray();

            $cupo->update($datos);
            $cupo->refresh();

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'actualizar',
                'tabla' => 'cupos',
                'registro_id' => $cupo->id,
                'datos_anteriores' => $anterior,
                'datos_nuevos' => $cupo->toArray(),
            ]);

            return $cupo;
        });
    }

    public function cambiarEstado(Cupo $cupo, string $nuevoEstado, ?int $empleadoId = null): Cupo
    {
        return DB::transaction(function () use ($cupo, $nuevoEstado, $empleadoId) {
            if (! in_array($nuevoEstado, ['activo', 'liberado', 'cerrado'], true)) {
                throw new DomainException('Estado no permitido.');
            }

            if ($cupo->estado === $nuevoEstado) {
                return $cupo;
            }

            $anterior = $cupo->toArray();

            $cupo->update(['estado' => $nuevoEstado]);
            $cupo->refresh();

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'actualizar',
                'tabla' => 'cupos',
                'registro_id' => $cupo->id,
                'datos_anteriores' => $anterior,
                'datos_nuevos' => $cupo->toArray(),
            ]);

            return $cupo;
        });
    }

    public function eliminar(Cupo $cupo, ?int $empleadoId = null): void
    {
        DB::transaction(function () use ($cupo, $empleadoId): void {
            $tieneVentas = VentaServicio::query()
                ->where('cupo_id', $cupo->id)
                ->exists();

            if ($tieneVentas) {
                throw new DomainException('No se puede eliminar el cupo porque tiene ventas o reservas vinculadas.');
            }

            $anterior = $cupo->toArray();

            $cupo->delete();

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'eliminar',
                'tabla' => 'cupos',
                'registro_id' => $cupo->id,
                'datos_anteriores' => $anterior,
            ]);
        });
    }
}
