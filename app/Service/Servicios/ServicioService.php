<?php

namespace App\Service\Servicios;

use App\Service\Auditoria\AuditoriaService;
use App\Service\Shared\GeneradorCodigoService;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ServicioService
{
    private const POR_PAGINA = 15;

    public function obtenerPaginado(array $filtros, int $porPagina = self::POR_PAGINA): LengthAwarePaginator
    {
        $query = DB::table('servicios as s')
            ->leftJoin('proveedores as p', 'p.id', '=', 's.proveedor_id')
            ->leftJoin('ciudades as co', 'co.id', '=', 's.ciudad_origen_id')
            ->leftJoin('ciudades as cd', 'cd.id', '=', 's.ciudad_destino_id')
            ->select(
                's.*',
                'p.nombre as proveedor_nombre',
                'co.nombre as origen_nombre',
                'cd.nombre as destino_nombre',
            );

        $buscar = trim((string) ($filtros['buscar'] ?? ''));

        if ($buscar !== '') {
            $like = '%'.$buscar.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('s.codigo', 'like', $like)
                    ->orWhere('s.nombre', 'like', $like)
                    ->orWhere('s.descripcion', 'like', $like);
            });
        }

        if (! empty($filtros['tipo'])) {
            $query->where('s.tipo', $filtros['tipo']);
        }

        if (! empty($filtros['estado'])) {
            $query->where('s.estado', $filtros['estado']);
        }

        if (! empty($filtros['proveedor_id'])) {
            $query->where('s.proveedor_id', (int) $filtros['proveedor_id']);
        }

        return $query
            ->orderBy('s.nombre')
            ->paginate($porPagina)
            ->withQueryString();
    }

    public function proveedoresParaSeleccion(?int $actualId = null): Collection
    {
        return DB::table('proveedores')
            ->where(function ($query) use ($actualId): void {
                $query->where('estado', 'activo');

                if ($actualId !== null) {
                    $query->orWhere('id', $actualId);
                }
            })
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo']);
    }

    public function proveedoresParaFiltro(): Collection
    {
        return DB::table('proveedores')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo']);
    }

    public function ciudadesParaSeleccion(): Collection
    {
        return DB::table('ciudades')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'pais_codigo']);
    }

    public function crear(array $datos, ?int $empleadoId = null): int
    {
        return DB::transaction(function () use ($datos, $empleadoId) {
            $datos['estado'] ??= 'activo';
            $datos['unidad_cobro'] ??= 'por_persona';
            $datos['created_at'] = now();
            $datos['updated_at'] = now();

            $servicioId = DB::table('servicios')->insertGetId($datos);

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'crear',
                'tabla' => 'servicios',
                'registro_id' => $servicioId,
                'datos_nuevos' => (array) DB::table('servicios')->find($servicioId),
            ]);

            return $servicioId;
        });
    }

    public function actualizar(int $id, array $datos, ?int $empleadoId = null): void
    {
        DB::transaction(function () use ($id, $datos, $empleadoId): void {
            $anterior = DB::table('servicios')->find($id);

            if (! $anterior) {
                throw new DomainException('El servicio no existe.');
            }

            $datos['updated_at'] = now();

            DB::table('servicios')->where('id', $id)->update($datos);

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'actualizar',
                'tabla' => 'servicios',
                'registro_id' => $id,
                'datos_anteriores' => (array) $anterior,
                'datos_nuevos' => (array) DB::table('servicios')->find($id),
            ]);
        });
    }

    public function cambiarEstado(int $id, string $estadoDestino, ?int $empleadoId = null): void
    {
        DB::transaction(function () use ($id, $estadoDestino, $empleadoId): void {
            $anterior = DB::table('servicios')->find($id);

            if (! $anterior) {
                throw new DomainException('El servicio no existe.');
            }

            DB::table('servicios')->where('id', $id)->update([
                'estado' => $estadoDestino,
                'updated_at' => now(),
            ]);

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'actualizar',
                'tabla' => 'servicios',
                'registro_id' => $id,
                'datos_anteriores' => (array) $anterior,
                'datos_nuevos' => (array) DB::table('servicios')->find($id),
            ]);
        });
    }

    public function eliminar(int $id, ?int $empleadoId = null): void
    {
        DB::transaction(function () use ($id, $empleadoId): void {
            $servicio = DB::table('servicios')->find($id);

            if (! $servicio) {
                throw new DomainException('El servicio no existe.');
            }

            if ($servicio->estado === 'activo') {
                throw new DomainException('No se puede eliminar un servicio activo. Desactívelo primero.');
            }

            if ($this->tieneRegistrosRelacionados($id)) {
                throw new DomainException('No se puede eliminar el servicio porque está asociado a paquetes, cupos o ventas.');
            }

            DB::table('servicios')->where('id', $id)->delete();

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'eliminar',
                'tabla' => 'servicios',
                'registro_id' => $id,
                'datos_anteriores' => (array) $servicio,
            ]);
        });
    }

    public function generarCodigoSugerido(): string
    {
        return GeneradorCodigoService::siguiente('servicios', 'SRV-');
    }

    private function tieneRegistrosRelacionados(int $id): bool
    {
        $referencias = [
            ['paquete_servicios', 'servicio_id'],
            ['cupos', 'servicio_id'],
            ['venta_servicios', 'servicio_id'],
        ];

        foreach ($referencias as [$tabla, $columna]) {
            if (DB::table($tabla)->where($columna, $id)->exists()) {
                return true;
            }
        }

        return false;
    }
}
