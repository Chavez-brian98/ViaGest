<?php

namespace App\Service\Paquetes;

use App\Service\Auditoria\AuditoriaService;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaqueteService
{
    private const POR_PAGINA = 15;

    public function obtenerPaginado(array $filtros, int $porPagina = self::POR_PAGINA): LengthAwarePaginator
    {
        $query = DB::table('paquetes as p')
            ->leftJoin('ciudades as c', 'c.id', '=', 'p.ciudad_destino_id')
            ->leftJoin('v_paquetes_precio as vp', 'vp.paquete_id', '=', 'p.id')
            ->select(
                'p.*',
                'c.nombre as destino_nombre',
                'c.pais_codigo as destino_pais',
                DB::raw('COALESCE(vp.costo_neto_total, 0) as costo_neto_total'),
                DB::raw('COALESCE(vp.precio_venta, 0) as precio_venta'),
                DB::raw('(SELECT COUNT(*) FROM paquete_servicios ps WHERE ps.paquete_id = p.id) as servicios_count')
            );

        $buscar = trim((string) ($filtros['buscar'] ?? ''));

        if ($buscar !== '') {
            $like = '%'.$buscar.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('p.codigo', 'like', $like)
                    ->orWhere('p.nombre', 'like', $like)
                    ->orWhere('p.descripcion', 'like', $like);
            });
        }

        if (! empty($filtros['tipo'])) {
            $query->where('p.tipo', $filtros['tipo']);
        }

        if (! empty($filtros['estado'])) {
            $query->where('p.estado', $filtros['estado']);
        }

        if (! empty($filtros['ciudad_destino_id'])) {
            $query->where('p.ciudad_destino_id', (int) $filtros['ciudad_destino_id']);
        }

        return $query
            ->orderBy('p.nombre')
            ->paginate($porPagina)
            ->withQueryString();
    }

    public function generarCodigoSugerido(): string
    {
        $ultimoId = (int) DB::table('paquetes')->max('id');

        return sprintf('PAQ-%04d', $ultimoId + 1);
    }

    public function ciudadesParaSeleccion(): Collection
    {
        return DB::table('ciudades')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'pais_codigo']);
    }

    public function serviciosDisponiblesParaPaquete(): Collection
    {
        return DB::table('servicios as s')
            ->leftJoin('proveedores as p', 'p.id', '=', 's.proveedor_id')
            ->where('s.estado', 'activo')
            ->orderBy('s.tipo')
            ->orderBy('s.nombre')
            ->get([
                's.id',
                's.codigo',
                's.nombre',
                's.tipo',
                's.costo_neto',
                's.unidad_cobro',
                'p.nombre as proveedor_nombre',
            ]);
    }

    public function buscarServicios(string $termino = '', array $excluirIds = []): Collection
    {
        $query = DB::table('servicios as s')
            ->leftJoin('proveedores as p', 'p.id', '=', 's.proveedor_id')
            ->where('s.estado', 'activo');

        if (! empty($excluirIds)) {
            $query->whereNotIn('s.id', array_map('intval', $excluirIds));
        }

        $termino = trim($termino);
        if ($termino !== '') {
            $like = '%'.$termino.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('s.codigo', 'like', $like)
                    ->orWhere('s.nombre', 'like', $like)
                    ->orWhere('s.tipo', 'like', $like)
                    ->orWhere('p.nombre', 'like', $like);
            });
        }

        return $query
            ->orderBy('s.tipo')
            ->orderBy('s.nombre')
            ->limit(30)
            ->get([
                's.id',
                's.codigo',
                's.nombre',
                's.tipo',
                's.costo_neto',
                's.unidad_cobro',
                'p.nombre as proveedor_nombre',
            ]);
    }

    public function obtenerServiciosDePaquete(int $paqueteId): Collection
    {
        return DB::table('paquete_servicios as ps')
            ->join('servicios as s', 's.id', '=', 'ps.servicio_id')
            ->leftJoin('proveedores as p', 'p.id', '=', 's.proveedor_id')
            ->where('ps.paquete_id', $paqueteId)
            ->orderBy('ps.dia_inicio')
            ->orderBy('ps.id')
            ->select(
                'ps.id',
                'ps.servicio_id',
                'ps.dia_inicio',
                'ps.cantidad',
                's.codigo as servicio_codigo',
                's.nombre as servicio_nombre',
                's.tipo as servicio_tipo',
                's.costo_neto',
                's.unidad_cobro',
                'p.nombre as proveedor_nombre'
            )
            ->get();
    }

    public function crear(array $datos, int $empleadoId): int
    {
        return DB::transaction(function () use ($datos, $empleadoId) {
            $servicios = $datos['servicios'] ?? [];
            unset($datos['servicios']);

            $datos['estado'] ??= 'borrador';
            $datos['created_at'] = now();
            $datos['updated_at'] = now();

            $paqueteId = DB::table('paquetes')->insertGetId($datos);

            $this->sincronizarServicios($paqueteId, $servicios);

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'crear',
                'tabla' => 'paquetes',
                'registro_id' => $paqueteId,
                'datos_nuevos' => (array) DB::table('paquetes')->find($paqueteId),
            ]);

            return $paqueteId;
        });
    }

    public function actualizar(int $paqueteId, array $datos, int $empleadoId): void
    {
        DB::transaction(function () use ($paqueteId, $datos, $empleadoId): void {
            $actual = DB::table('paquetes')->find($paqueteId);

            if (! $actual) {
                throw new DomainException('El paquete no existe.');
            }

            $servicios = $datos['servicios'] ?? [];
            unset($datos['servicios']);

            $datos['updated_at'] = now();

            DB::table('paquetes')->where('id', $paqueteId)->update($datos);

            $this->sincronizarServicios($paqueteId, $servicios);

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'actualizar',
                'tabla' => 'paquetes',
                'registro_id' => $paqueteId,
                'datos_anteriores' => (array) $actual,
                'datos_nuevos' => (array) DB::table('paquetes')->find($paqueteId),
            ]);
        });
    }

    public function cambiarEstado(int $paqueteId, string $nuevoEstado, int $empleadoId): void
    {
        DB::transaction(function () use ($paqueteId, $nuevoEstado, $empleadoId): void {
            $actual = DB::table('paquetes')->find($paqueteId);

            if (! $actual) {
                throw new DomainException('El paquete no existe.');
            }

            if (! in_array($nuevoEstado, ['borrador', 'activo', 'inactivo'], true)) {
                throw new DomainException('Estado no permitido.');
            }

            if ($actual->estado === $nuevoEstado) {
                return;
            }

            // Si se pasa a activo, validar que tenga al menos un servicio configurado
            if ($nuevoEstado === 'activo') {
                $tieneServicios = DB::table('paquete_servicios')->where('paquete_id', $paqueteId)->exists();
                if (! $tieneServicios) {
                    throw new DomainException('Para activar un paquete debes asociarle al menos un servicio turístico.');
                }
            }

            DB::table('paquetes')->where('id', $paqueteId)->update([
                'estado' => $nuevoEstado,
                'updated_at' => now(),
            ]);

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'actualizar',
                'tabla' => 'paquetes',
                'registro_id' => $paqueteId,
                'datos_anteriores' => (array) $actual,
                'datos_nuevos' => (array) DB::table('paquetes')->find($paqueteId),
            ]);
        });
    }

    public function eliminar(int $paqueteId, int $empleadoId): void
    {
        DB::transaction(function () use ($paqueteId, $empleadoId): void {
            $actual = DB::table('paquetes')->find($paqueteId);

            if (! $actual) {
                throw new DomainException('El paquete no existe.');
            }

            $tieneVentas = DB::table('ventas')->where('paquete_id', $paqueteId)->exists();
            $tienePresupuestos = DB::table('presupuestos')->where('paquete_id', $paqueteId)->exists();

            if ($tieneVentas || $tienePresupuestos) {
                throw new DomainException('No se puede eliminar el paquete porque está vinculado a ventas o cotizaciones.');
            }

            // Los paquete_servicios se eliminan por cascadeOnDelete en la migración
            DB::table('paquetes')->where('id', $paqueteId)->delete();

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'eliminar',
                'tabla' => 'paquetes',
                'registro_id' => $paqueteId,
                'datos_anteriores' => (array) $actual,
            ]);
        });
    }

    private function sincronizarServicios(int $paqueteId, array $servicios): void
    {
        DB::table('paquete_servicios')->where('paquete_id', $paqueteId)->delete();

        if (empty($servicios)) {
            return;
        }

        $items = [];
        foreach ($servicios as $s) {
            $items[] = [
                'paquete_id' => $paqueteId,
                'servicio_id' => (int) $s['servicio_id'],
                'dia_inicio' => (int) ($s['dia_inicio'] ?? 1),
                'cantidad' => (float) ($s['cantidad'] ?? 1),
            ];
        }

        DB::table('paquete_servicios')->insert($items);
    }
}
