<?php

namespace App\Service\Cotizaciones;

use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\Paquete;
use App\Models\PaqueteServicio;
use App\Models\Presupuesto;
use App\Models\PresupuestoServicio;
use App\Models\Prospecto;
use App\Models\Servicio;
use App\Models\Sucursal;
use App\Models\Venta;
use App\Service\Auditoria\AuditoriaService;
use App\Service\Shared\GeneradorCodigoService;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CotizacionService
{
    private const POR_PAGINA = 12;

    public const ESTADOS = [
        'borrador' => 'Borrador',
        'enviado' => 'Enviada',
        'aprobado' => 'Aprobada',
        'rechazado' => 'Rechazada',
        'caducado' => 'Caducada',
        'convertido' => 'Convertida a venta',
    ];

    public function listar(?string $buscar = null, array $filtros = []): LengthAwarePaginator
    {
        $buscar = trim((string) $buscar);
        $estado = (string) ($filtros['estado'] ?? '');
        $sucursalId = (string) ($filtros['sucursal_id'] ?? '');
        $vendedorId = (string) ($filtros['vendedor_id'] ?? '');

        return Presupuesto::query()
            ->join('sucursales as s', 's.id', '=', 'presupuestos.sucursal_id')
            ->join('empleados as e', 'e.id', '=', 'presupuestos.vendedor_id')
            ->leftJoin('clientes as c', 'c.id', '=', 'presupuestos.cliente_id')
            ->leftJoin('prospectos as pr', 'pr.id', '=', 'presupuestos.prospecto_id')
            ->leftJoin('paquetes as paq', 'paq.id', '=', 'presupuestos.paquete_id')
            ->select(
                'presupuestos.*',
                's.nombre as sucursal_nombre',
                'e.nombres as vendedor_nombres',
                'e.apellidos as vendedor_apellidos',
                'c.codigo as cliente_codigo',
                'c.nombres as cliente_nombres',
                'c.apellidos as cliente_apellidos',
                'c.razon_social as cliente_razon_social',
                'pr.codigo as prospecto_codigo',
                'pr.nombres as prospecto_nombres',
                'pr.apellidos as prospecto_apellidos',
                'paq.nombre as paquete_nombre',
                DB::raw('(SELECT COALESCE(SUM(ps.cantidad * ps.precio_unitario), 0) FROM presupuesto_servicios ps WHERE ps.presupuesto_id = presupuestos.id) as subtotal_servicios'),
                DB::raw('(SELECT COALESCE(SUM(ps.cantidad * ps.costo_neto_unitario), 0) FROM presupuesto_servicios ps WHERE ps.presupuesto_id = presupuestos.id) as costo_total_servicios'),
            )
            ->when($buscar !== '', function (Builder $query) use ($buscar): void {
                $like = '%'.$buscar.'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query->where('presupuestos.codigo', 'like', $like)
                        ->orWhere('c.codigo', 'like', $like)
                        ->orWhere('c.nombres', 'like', $like)
                        ->orWhere('c.apellidos', 'like', $like)
                        ->orWhere('c.razon_social', 'like', $like)
                        ->orWhere('pr.codigo', 'like', $like)
                        ->orWhere('pr.nombres', 'like', $like)
                        ->orWhere('pr.apellidos', 'like', $like)
                        ->orWhere('paq.nombre', 'like', $like);
                });
            })
            ->when($estado !== '', fn (Builder $query) => $query->where('presupuestos.estado', $estado))
            ->when($sucursalId !== '', fn (Builder $query) => $query->where('presupuestos.sucursal_id', (int) $sucursalId))
            ->when($vendedorId !== '', fn (Builder $query) => $query->where('presupuestos.vendedor_id', (int) $vendedorId))
            ->orderByDesc('presupuestos.id')
            ->paginate(self::POR_PAGINA)
            ->onEachSide(1)
            ->withQueryString();
    }

    public function encontrar(int $id): Presupuesto
    {
        $cotizacion = Presupuesto::query()
            ->join('sucursales as s', 's.id', '=', 'presupuestos.sucursal_id')
            ->join('empleados as e', 'e.id', '=', 'presupuestos.vendedor_id')
            ->leftJoin('clientes as c', 'c.id', '=', 'presupuestos.cliente_id')
            ->leftJoin('prospectos as pr', 'pr.id', '=', 'presupuestos.prospecto_id')
            ->leftJoin('paquetes as paq', 'paq.id', '=', 'presupuestos.paquete_id')
            ->select(
                'presupuestos.*',
                's.nombre as sucursal_nombre',
                'e.nombres as vendedor_nombres',
                'e.apellidos as vendedor_apellidos',
                'c.codigo as cliente_codigo',
                'c.nombres as cliente_nombres',
                'c.apellidos as cliente_apellidos',
                'c.razon_social as cliente_razon_social',
                'pr.codigo as prospecto_codigo',
                'pr.nombres as prospecto_nombres',
                'pr.apellidos as prospecto_apellidos',
                'paq.nombre as paquete_nombre',
            )
            ->where('presupuestos.id', $id)
            ->first();

        if (! $cotizacion) {
            throw new DomainException('La cotización solicitada no existe.');
        }

        $servicios = PresupuestoServicio::query()
            ->join('servicios as s', 's.id', '=', 'presupuesto_servicios.servicio_id')
            ->join('proveedores as prov', 'prov.id', '=', 's.proveedor_id')
            ->select(
                'presupuesto_servicios.*',
                's.codigo as servicio_codigo',
                's.nombre as servicio_nombre',
                's.tipo as servicio_tipo',
                'prov.nombre as proveedor_nombre',
            )
            ->where('presupuesto_servicios.presupuesto_id', $id)
            ->get();

        $subtotal = 0;
        $costoNeto = 0;
        foreach ($servicios as $srv) {
            $subtotal += ($srv->cantidad * $srv->precio_unitario);
            $costoNeto += ($srv->cantidad * $srv->costo_neto_unitario);
        }

        $cotizacion->servicios = $servicios;
        $cotizacion->subtotal = round($subtotal, 2);
        $cotizacion->total = max(0, round($subtotal - (float) $cotizacion->descuento_monto, 2));
        $cotizacion->costo_total = round($costoNeto, 2);
        $cotizacion->utilidad = round($cotizacion->total - $costoNeto, 2);

        return $cotizacion;
    }

    public function crear(array $datos): Presupuesto
    {
        if (empty($datos['codigo'])) {
            $datos['codigo'] = $this->siguienteCodigo();
        }

        return DB::transaction(function () use ($datos) {
            $cotizacion = Presupuesto::create([
                'codigo' => $datos['codigo'],
                'prospecto_id' => $datos['prospecto_id'] ?? null,
                'cliente_id' => $datos['cliente_id'] ?? null,
                'vendedor_id' => $datos['vendedor_id'],
                'sucursal_id' => $datos['sucursal_id'],
                'paquete_id' => $datos['paquete_id'] ?? null,
                'fecha_emision' => $datos['fecha_emision'],
                'fecha_caducidad' => $datos['fecha_caducidad'],
                'fecha_viaje_inicio' => $datos['fecha_viaje_inicio'] ?? null,
                'fecha_viaje_fin' => $datos['fecha_viaje_fin'] ?? null,
                'numero_viajeros' => $datos['numero_viajeros'] ?? 1,
                'descuento_monto' => $datos['descuento_monto'] ?? 0.00,
                'estado' => $datos['estado'] ?? 'borrador',
                'notas' => $datos['notas'] ?? null,
            ]);

            $this->sincronizarServicios($cotizacion->id, $datos['servicios'] ?? []);

            AuditoriaService::registrar([
                'empleado_id' => Auth::id(),
                'accion' => 'crear',
                'tabla' => 'presupuestos',
                'registro_id' => $cotizacion->id,
                'datos_nuevos' => array_merge($cotizacion->toArray(), ['servicios' => $datos['servicios'] ?? []]),
            ]);

            return $cotizacion;
        });
    }

    public function actualizar(Presupuesto $cotizacion, array $datos): void
    {
        if ($cotizacion->estado === 'convertido') {
            throw new DomainException('No se puede modificar una cotización que ya fue convertida a venta.');
        }

        DB::transaction(function () use ($cotizacion, $datos): void {
            $anterior = $cotizacion->toArray();

            $actualizaciones = [
                'codigo' => $datos['codigo'] ?? $cotizacion->codigo,
                'prospecto_id' => $datos['prospecto_id'] ?? null,
                'cliente_id' => $datos['cliente_id'] ?? null,
                'vendedor_id' => $datos['vendedor_id'],
                'sucursal_id' => $datos['sucursal_id'],
                'paquete_id' => $datos['paquete_id'] ?? null,
                'fecha_emision' => $datos['fecha_emision'],
                'fecha_caducidad' => $datos['fecha_caducidad'],
                'fecha_viaje_inicio' => $datos['fecha_viaje_inicio'] ?? null,
                'fecha_viaje_fin' => $datos['fecha_viaje_fin'] ?? null,
                'numero_viajeros' => $datos['numero_viajeros'] ?? 1,
                'descuento_monto' => $datos['descuento_monto'] ?? 0.00,
                'estado' => $datos['estado'] ?? $cotizacion->estado,
                'notas' => $datos['notas'] ?? null,
            ];

            $cotizacion->update($actualizaciones);
            $cotizacion->refresh();

            $this->sincronizarServicios($cotizacion->id, $datos['servicios'] ?? []);

            AuditoriaService::registrar([
                'empleado_id' => Auth::id(),
                'accion' => 'actualizar',
                'tabla' => 'presupuestos',
                'registro_id' => $cotizacion->id,
                'datos_anteriores' => $anterior,
                'datos_nuevos' => array_merge($cotizacion->toArray(), ['servicios' => $datos['servicios'] ?? []]),
            ]);
        });
    }

    public function actualizarEstado(Presupuesto $cotizacion, string $nuevoEstado): Presupuesto
    {
        if ($cotizacion->estado === 'convertido') {
            throw new DomainException('No se puede modificar el estado de una cotización ya convertida a venta.');
        }

        DB::transaction(function () use ($cotizacion, $nuevoEstado): void {
            $anterior = $cotizacion->toArray();

            $cotizacion->update(['estado' => $nuevoEstado]);
            $cotizacion->refresh();

            AuditoriaService::registrar([
                'empleado_id' => Auth::id(),
                'accion' => 'actualizar',
                'tabla' => 'presupuestos',
                'registro_id' => $cotizacion->id,
                'datos_anteriores' => $anterior,
                'datos_nuevos' => $cotizacion->toArray(),
            ]);
        });

        return $cotizacion;
    }

    public function eliminar(Presupuesto $cotizacion): void
    {
        $tieneVenta = Venta::query()->where('presupuesto_id', $cotizacion->id)->exists();

        if ($tieneVenta) {
            throw new DomainException('No se puede eliminar la cotización: está vinculada a una venta realizada.');
        }

        if ($cotizacion->estado === 'convertido') {
            throw new DomainException('No se puede eliminar una cotización que ya fue convertida a venta.');
        }

        if (! in_array($cotizacion->estado, ['borrador', 'rechazado', 'caducado'], true)) {
            throw new DomainException('Para eliminar una cotización debe estar en estado Borrador, Rechazada o Caducada.');
        }

        DB::transaction(function () use ($cotizacion): void {
            $anterior = $cotizacion->toArray();

            PresupuestoServicio::query()->where('presupuesto_id', $cotizacion->id)->delete();
            $cotizacion->delete();

            AuditoriaService::registrar([
                'empleado_id' => Auth::id(),
                'accion' => 'eliminar',
                'tabla' => 'presupuestos',
                'registro_id' => $cotizacion->id,
                'datos_anteriores' => $anterior,
            ]);
        });
    }

    private function sincronizarServicios(int $presupuestoId, array $servicios): void
    {
        PresupuestoServicio::query()->where('presupuesto_id', $presupuestoId)->delete();

        $rows = [];
        foreach ($servicios as $item) {
            if (empty($item['servicio_id'])) {
                continue;
            }

            $rows[] = [
                'presupuesto_id' => $presupuestoId,
                'servicio_id' => (int) $item['servicio_id'],
                'cantidad' => (float) ($item['cantidad'] ?? 1),
                'costo_neto_unitario' => (float) ($item['costo_neto_unitario'] ?? 0),
                'precio_unitario' => (float) ($item['precio_unitario'] ?? 0),
                'fecha_inicio' => ! empty($item['fecha_inicio']) ? $item['fecha_inicio'] : null,
                'fecha_fin' => ! empty($item['fecha_fin']) ? $item['fecha_fin'] : null,
            ];
        }

        if (! empty($rows)) {
            PresupuestoServicio::insert($rows);
        }
    }

    public function sucursalesActivas(): Collection
    {
        return Sucursal::query()->where('estado', 'activo')->orderBy('nombre')->get();
    }

    public function vendedoresActivos(): Collection
    {
        return Empleado::query()->where('estado', 'activo')->orderBy('nombres')->get();
    }

    public function clientesActivos(): Collection
    {
        return Cliente::query()
            ->where('estado', 'activo')
            ->orderByRaw('COALESCE(razon_social, apellidos, nombres) asc')
            ->get();
    }

    public function prospectosActivos(): Collection
    {
        return Prospecto::query()
            ->whereNotIn('estado', ['convertido', 'perdido'])
            ->orderBy('nombres')
            ->get();
    }

    public function paquetesActivos(): Collection
    {
        return Paquete::query()
            ->where('estado', 'activo')
            ->orderBy('nombre')
            ->get();
    }

    public function serviciosDisponibles(): Collection
    {
        return Servicio::query()
            ->join('proveedores as p', 'p.id', '=', 'servicios.proveedor_id')
            ->select(
                'servicios.id',
                'servicios.codigo',
                'servicios.nombre',
                'servicios.tipo',
                'servicios.costo_neto',
                'p.nombre as proveedor_nombre',
            )
            ->where('servicios.estado', 'activo')
            ->orderBy('servicios.nombre')
            ->get();
    }

    public function serviciosPorPaquete(int $paqueteId): Collection
    {
        return PaqueteServicio::query()
            ->join('servicios as s', 's.id', '=', 'paquete_servicios.servicio_id')
            ->select(
                's.id as servicio_id',
                's.codigo',
                's.nombre',
                's.tipo',
                'paquete_servicios.cantidad',
                's.costo_neto as costo_neto_unitario',
                's.costo_neto as precio_unitario',
            )
            ->where('paquete_servicios.paquete_id', $paqueteId)
            ->get();
    }

    public function siguienteCodigo(): string
    {
        return GeneradorCodigoService::siguiente(Presupuesto::class, 'COT-');
    }
}
