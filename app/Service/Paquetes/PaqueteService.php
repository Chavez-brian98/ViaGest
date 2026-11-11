<?php

namespace App\Service\Paquetes;

use App\Models\Ciudad;
use App\Models\Paquete;
use App\Models\PaqueteServicio;
use App\Models\Presupuesto;
use App\Models\Servicio;
use App\Models\Venta;
use App\Service\Auditoria\AuditoriaService;
use App\Service\Shared\GeneradorCodigoService;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaqueteService
{
    private const POR_PAGINA = 15;

    public function obtenerPaginado(array $filtros, int $porPagina = self::POR_PAGINA): LengthAwarePaginator
    {
        $query = Paquete::query()
            ->leftJoin('ciudades as c', 'c.id', '=', 'paquetes.ciudad_destino_id')
            ->leftJoin('v_paquetes_precio as vp', 'vp.paquete_id', '=', 'paquetes.id')
            ->select(
                'paquetes.*',
                'c.nombre as destino_nombre',
                'c.pais_codigo as destino_pais',
                DB::raw('COALESCE(vp.costo_neto_total, 0) as costo_neto_total'),
                DB::raw('COALESCE(vp.precio_venta, 0) as precio_venta'),
                DB::raw('(SELECT COUNT(*) FROM paquete_servicios ps WHERE ps.paquete_id = paquetes.id) as servicios_count')
            );

        $buscar = trim((string) ($filtros['buscar'] ?? ''));

        if ($buscar !== '') {
            $like = '%'.$buscar.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('paquetes.codigo', 'like', $like)
                    ->orWhere('paquetes.nombre', 'like', $like)
                    ->orWhere('paquetes.descripcion', 'like', $like);
            });
        }

        if (! empty($filtros['tipo'])) {
            $query->where('paquetes.tipo', $filtros['tipo']);
        }

        if (! empty($filtros['estado'])) {
            $query->where('paquetes.estado', $filtros['estado']);
        }

        if (! empty($filtros['ciudad_destino_id'])) {
            $query->where('paquetes.ciudad_destino_id', (int) $filtros['ciudad_destino_id']);
        }

        return $query
            ->orderBy('paquetes.nombre')
            ->paginate($porPagina)
            ->withQueryString();
    }

    public function generarCodigoSugerido(): string
    {
        return GeneradorCodigoService::siguiente(Paquete::class, 'PAQ-');
    }

    public function ciudadesParaSeleccion(): Collection
    {
        return Ciudad::query()
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'pais_codigo']);
    }

    public function serviciosDisponiblesParaPaquete(): Collection
    {
        return Servicio::query()
            ->leftJoin('proveedores as p', 'p.id', '=', 'servicios.proveedor_id')
            ->where('servicios.estado', 'activo')
            ->orderBy('servicios.tipo')
            ->orderBy('servicios.nombre')
            ->get([
                'servicios.id',
                'servicios.codigo',
                'servicios.nombre',
                'servicios.tipo',
                'servicios.costo_neto',
                'servicios.unidad_cobro',
                'p.nombre as proveedor_nombre',
            ]);
    }

    public function buscarServicios(string $termino = '', array $excluirIds = []): Collection
    {
        $query = Servicio::query()
            ->leftJoin('proveedores as p', 'p.id', '=', 'servicios.proveedor_id')
            ->where('servicios.estado', 'activo');

        if (! empty($excluirIds)) {
            $query->whereNotIn('servicios.id', array_map('intval', $excluirIds));
        }

        $termino = trim($termino);
        if ($termino !== '') {
            $like = '%'.$termino.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('servicios.codigo', 'like', $like)
                    ->orWhere('servicios.nombre', 'like', $like)
                    ->orWhere('servicios.tipo', 'like', $like)
                    ->orWhere('p.nombre', 'like', $like);
            });
        }

        return $query
            ->orderBy('servicios.tipo')
            ->orderBy('servicios.nombre')
            ->limit(30)
            ->get([
                'servicios.id',
                'servicios.codigo',
                'servicios.nombre',
                'servicios.tipo',
                'servicios.costo_neto',
                'servicios.unidad_cobro',
                'p.nombre as proveedor_nombre',
            ]);
    }

    public function obtenerServiciosDePaquete(int $paqueteId): Collection
    {
        return PaqueteServicio::query()
            ->join('servicios as s', 's.id', '=', 'paquete_servicios.servicio_id')
            ->leftJoin('proveedores as p', 'p.id', '=', 's.proveedor_id')
            ->where('paquete_servicios.paquete_id', $paqueteId)
            ->orderBy('paquete_servicios.dia_inicio')
            ->orderBy('paquete_servicios.id')
            ->select(
                'paquete_servicios.id',
                'paquete_servicios.servicio_id',
                'paquete_servicios.dia_inicio',
                'paquete_servicios.cantidad',
                's.codigo as servicio_codigo',
                's.nombre as servicio_nombre',
                's.tipo as servicio_tipo',
                's.costo_neto',
                's.unidad_cobro',
                'p.nombre as proveedor_nombre'
            )
            ->get();
    }

    public function crear(array $datos, ?int $empleadoId = null): Paquete
    {
        return DB::transaction(function () use ($datos, $empleadoId) {
            $servicios = $datos['servicios'] ?? [];
            unset($datos['servicios']);

            $datos['estado'] ??= 'borrador';

            if ($datos['estado'] === 'activo' && $servicios === []) {
                throw new DomainException('Para activar un paquete debes asociarle al menos un servicio turístico.');
            }

            $paquete = Paquete::create($datos);

            $this->sincronizarServicios($paquete->id, $servicios);

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'crear',
                'tabla' => 'paquetes',
                'registro_id' => $paquete->id,
                'datos_nuevos' => $paquete->toArray(),
            ]);

            return $paquete;
        });
    }

    public function actualizar(Paquete $paquete, array $datos, ?int $empleadoId = null): void
    {
        DB::transaction(function () use ($paquete, $datos, $empleadoId): void {
            $anterior = $paquete->toArray();

            $servicios = $datos['servicios'] ?? [];
            unset($datos['servicios']);

            $estadoDestino = $datos['estado'] ?? $paquete->estado;

            if ($estadoDestino === 'activo' && $servicios === []) {
                throw new DomainException('Para activar un paquete debes asociarle al menos un servicio turístico.');
            }

            $paquete->update($datos);
            $paquete->refresh();

            $this->sincronizarServicios($paquete->id, $servicios);

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'actualizar',
                'tabla' => 'paquetes',
                'registro_id' => $paquete->id,
                'datos_anteriores' => $anterior,
                'datos_nuevos' => $paquete->toArray(),
            ]);
        });
    }

    public function cambiarEstado(Paquete $paquete, string $nuevoEstado, ?int $empleadoId = null): void
    {
        DB::transaction(function () use ($paquete, $nuevoEstado, $empleadoId): void {
            if (! in_array($nuevoEstado, ['borrador', 'activo', 'inactivo'], true)) {
                throw new DomainException('Estado no permitido.');
            }

            if ($paquete->estado === $nuevoEstado) {
                return;
            }

            if ($nuevoEstado === 'activo' && ! $this->tieneServicios($paquete)) {
                throw new DomainException('Para activar un paquete debes asociarle al menos un servicio turístico.');
            }

            $anterior = $paquete->toArray();

            $paquete->update(['estado' => $nuevoEstado]);
            $paquete->refresh();

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'actualizar',
                'tabla' => 'paquetes',
                'registro_id' => $paquete->id,
                'datos_anteriores' => $anterior,
                'datos_nuevos' => $paquete->toArray(),
            ]);
        });
    }

    public function eliminar(Paquete $paquete, ?int $empleadoId = null): void
    {
        DB::transaction(function () use ($paquete, $empleadoId): void {
            $tieneVentas = Venta::query()->where('paquete_id', $paquete->id)->exists();
            $tienePresupuestos = Presupuesto::query()->where('paquete_id', $paquete->id)->exists();

            if ($tieneVentas || $tienePresupuestos) {
                throw new DomainException('No se puede eliminar el paquete porque está vinculado a ventas o cotizaciones.');
            }

            $anterior = $paquete->toArray();

            $paquete->delete();

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'eliminar',
                'tabla' => 'paquetes',
                'registro_id' => $paquete->id,
                'datos_anteriores' => $anterior,
            ]);
        });
    }

    private function tieneServicios(Paquete $paquete): bool
    {
        return PaqueteServicio::query()->where('paquete_id', $paquete->id)->exists();
    }

    private function sincronizarServicios(int $paqueteId, array $servicios): void
    {
        PaqueteServicio::query()->where('paquete_id', $paqueteId)->delete();

        if (empty($servicios)) {
            return;
        }

        $items = [];
        foreach ($servicios as $s) {
            $clave = (int) $s['servicio_id'].'-'.(int) ($s['dia_inicio'] ?? 1);
            $items[$clave] = [
                'paquete_id' => $paqueteId,
                'servicio_id' => (int) $s['servicio_id'],
                'dia_inicio' => (int) ($s['dia_inicio'] ?? 1),
                'cantidad' => (float) ($s['cantidad'] ?? 1),
            ];
        }

        PaqueteServicio::insert(array_values($items));
    }
}
