<?php

namespace App\Service\Servicios;

use App\Models\Ciudad;
use App\Models\Cupo;
use App\Models\PaqueteServicio;
use App\Models\Proveedor;
use App\Models\Servicio;
use App\Models\VentaServicio;
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
        $query = Servicio::query()
            ->leftJoin('proveedores as p', 'p.id', '=', 'servicios.proveedor_id')
            ->leftJoin('ciudades as co', 'co.id', '=', 'servicios.ciudad_origen_id')
            ->leftJoin('ciudades as cd', 'cd.id', '=', 'servicios.ciudad_destino_id')
            ->select(
                'servicios.*',
                'p.nombre as proveedor_nombre',
                'co.nombre as origen_nombre',
                'cd.nombre as destino_nombre',
            );

        $buscar = trim((string) ($filtros['buscar'] ?? ''));

        if ($buscar !== '') {
            $like = '%'.$buscar.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('servicios.codigo', 'like', $like)
                    ->orWhere('servicios.nombre', 'like', $like)
                    ->orWhere('servicios.descripcion', 'like', $like);
            });
        }

        if (! empty($filtros['tipo'])) {
            $query->where('servicios.tipo', $filtros['tipo']);
        }

        if (! empty($filtros['estado'])) {
            $query->where('servicios.estado', $filtros['estado']);
        }

        if (! empty($filtros['proveedor_id'])) {
            $query->where('servicios.proveedor_id', (int) $filtros['proveedor_id']);
        }

        return $query
            ->orderBy('servicios.nombre')
            ->paginate($porPagina)
            ->withQueryString();
    }

    public function proveedoresParaSeleccion(?int $actualId = null): Collection
    {
        return Proveedor::query()
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
        return Proveedor::query()
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo']);
    }

    public function ciudadesParaSeleccion(): Collection
    {
        return Ciudad::query()
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'pais_codigo']);
    }

    public function crear(array $datos, ?int $empleadoId = null): Servicio
    {
        return DB::transaction(function () use ($datos, $empleadoId) {
            $datos['estado'] ??= 'activo';
            $datos['unidad_cobro'] ??= 'por_persona';

            $servicio = Servicio::create($datos);

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'crear',
                'tabla' => 'servicios',
                'registro_id' => $servicio->id,
                'datos_nuevos' => $servicio->toArray(),
            ]);

            return $servicio;
        });
    }

    public function actualizar(Servicio $servicio, array $datos, ?int $empleadoId = null): Servicio
    {
        return DB::transaction(function () use ($servicio, $datos, $empleadoId) {
            $anterior = $servicio->toArray();

            $servicio->update($datos);
            $servicio->refresh();

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'actualizar',
                'tabla' => 'servicios',
                'registro_id' => $servicio->id,
                'datos_anteriores' => $anterior,
                'datos_nuevos' => $servicio->toArray(),
            ]);

            return $servicio;
        });
    }

    public function cambiarEstado(Servicio $servicio, string $estadoDestino, ?int $empleadoId = null): Servicio
    {
        return DB::transaction(function () use ($servicio, $estadoDestino, $empleadoId) {
            $anterior = $servicio->toArray();

            $servicio->update(['estado' => $estadoDestino]);
            $servicio->refresh();

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'actualizar',
                'tabla' => 'servicios',
                'registro_id' => $servicio->id,
                'datos_anteriores' => $anterior,
                'datos_nuevos' => $servicio->toArray(),
            ]);

            return $servicio;
        });
    }

    public function eliminar(Servicio $servicio, ?int $empleadoId = null): void
    {
        DB::transaction(function () use ($servicio, $empleadoId): void {
            if ($servicio->estado === 'activo') {
                throw new DomainException('No se puede eliminar un servicio activo. Desactívelo primero.');
            }

            if ($this->tieneRegistrosRelacionados($servicio)) {
                throw new DomainException('No se puede eliminar el servicio porque está asociado a paquetes, cupos o ventas.');
            }

            $anterior = $servicio->toArray();

            $servicio->delete();

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'eliminar',
                'tabla' => 'servicios',
                'registro_id' => $servicio->id,
                'datos_anteriores' => $anterior,
            ]);
        });
    }

    public function generarCodigoSugerido(): string
    {
        return GeneradorCodigoService::siguiente(Servicio::class, 'SRV-');
    }

    private function tieneRegistrosRelacionados(Servicio $servicio): bool
    {
        return PaqueteServicio::query()->where('servicio_id', $servicio->id)->exists()
            || Cupo::query()->where('servicio_id', $servicio->id)->exists()
            || VentaServicio::query()->where('servicio_id', $servicio->id)->exists();
    }
}
