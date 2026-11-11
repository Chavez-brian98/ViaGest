<?php

namespace App\Service\Prospectos;

use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\Presupuesto;
use App\Models\Prospecto;
use App\Models\Sucursal;
use App\Service\Auditoria\AuditoriaService;
use App\Service\Shared\GeneradorCodigoService;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProspectoService
{
    private const POR_PAGINA = 12;

    public const ORIGENES = [
        'referido' => 'Referido',
        'redes_sociales' => 'Redes sociales',
        'sitio_web' => 'Sitio web',
        'visita_local' => 'Visita al local',
        'telefono' => 'Llamada telefónica',
        'evento' => 'Evento / Feria',
        'otro' => 'Otro',
    ];

    public const ESTADOS = [
        'nuevo' => 'Nuevo',
        'contactado' => 'Contactado',
        'cotizado' => 'Cotizado',
        'convertido' => 'Convertido',
        'perdido' => 'Perdido',
    ];

    public const MOTIVOS_PERDIDA = [
        'precio' => 'Precio elevado',
        'sin_respuesta' => 'Sin respuesta del cliente',
        'eligio_competencia' => 'Eligió a la competencia',
        'cambio_de_planes' => 'Cambio de planes / Canceló viaje',
        'otro' => 'Otro motivo',
    ];

    public function listar(?string $buscar = null, array $filtros = []): LengthAwarePaginator
    {
        $buscar = trim((string) $buscar);
        $estado = (string) ($filtros['estado'] ?? '');
        $origen = (string) ($filtros['origen'] ?? '');
        $sucursalId = (string) ($filtros['sucursal_id'] ?? '');
        $vendedorId = (string) ($filtros['vendedor_id'] ?? '');

        return Prospecto::query()
            ->join('sucursales as s', 's.id', '=', 'prospectos.sucursal_id')
            ->join('empleados as e', 'e.id', '=', 'prospectos.vendedor_id')
            ->leftJoin('clientes as c', 'c.id', '=', 'prospectos.cliente_id')
            ->select(
                'prospectos.*',
                's.nombre as sucursal_nombre',
                'e.nombres as vendedor_nombres',
                'e.apellidos as vendedor_apellidos',
                'c.codigo as cliente_codigo',
                'c.nombres as cliente_nombres',
                'c.apellidos as cliente_apellidos',
                'c.razon_social as cliente_razon_social',
            )
            ->when($buscar !== '', function (Builder $query) use ($buscar): void {
                $like = '%'.$buscar.'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query->where('prospectos.codigo', 'like', $like)
                        ->orWhere('prospectos.nombres', 'like', $like)
                        ->orWhere('prospectos.apellidos', 'like', $like)
                        ->orWhere('prospectos.email', 'like', $like)
                        ->orWhere('prospectos.telefono', 'like', $like)
                        ->orWhere('prospectos.pais_destino', 'like', $like);
                });
            })
            ->when($estado !== '', fn (Builder $query) => $query->where('prospectos.estado', $estado))
            ->when($origen !== '', fn (Builder $query) => $query->where('prospectos.origen', $origen))
            ->when($sucursalId !== '', fn (Builder $query) => $query->where('prospectos.sucursal_id', (int) $sucursalId))
            ->when($vendedorId !== '', fn (Builder $query) => $query->where('prospectos.vendedor_id', (int) $vendedorId))
            ->orderByDesc('prospectos.id')
            ->paginate(self::POR_PAGINA)
            ->onEachSide(1)
            ->withQueryString();
    }

    public function encontrar(int $id): Prospecto
    {
        $prospecto = Prospecto::query()
            ->join('sucursales as s', 's.id', '=', 'prospectos.sucursal_id')
            ->join('empleados as e', 'e.id', '=', 'prospectos.vendedor_id')
            ->leftJoin('clientes as c', 'c.id', '=', 'prospectos.cliente_id')
            ->select(
                'prospectos.*',
                's.nombre as sucursal_nombre',
                'e.nombres as vendedor_nombres',
                'e.apellidos as vendedor_apellidos',
                'c.codigo as cliente_codigo',
                'c.nombres as cliente_nombres',
                'c.apellidos as cliente_apellidos',
                'c.razon_social as cliente_razon_social',
            )
            ->where('prospectos.id', $id)
            ->first();

        if (! $prospecto) {
            throw new DomainException('El prospecto solicitado no existe.');
        }

        return $prospecto;
    }

    public function crear(array $datos): Prospecto
    {
        if (empty($datos['codigo'])) {
            $datos['codigo'] = $this->siguienteCodigo();
        }

        return DB::transaction(function () use ($datos) {
            $prospecto = Prospecto::create([
                'codigo' => $datos['codigo'],
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'] ?? null,
                'email' => $datos['email'] ?? null,
                'telefono' => $datos['telefono'] ?? null,
                'pais_destino' => $datos['pais_destino'] ?? null,
                'fecha_viaje_estimada' => $datos['fecha_viaje_estimada'] ?? null,
                'numero_viajeros' => $datos['numero_viajeros'] ?? null,
                'origen' => $datos['origen'] ?? 'otro',
                'estado' => $datos['estado'] ?? 'nuevo',
                'motivo_perdida' => ($datos['estado'] ?? 'nuevo') === 'perdido' ? ($datos['motivo_perdida'] ?? null) : null,
                'proximo_seguimiento' => $datos['proximo_seguimiento'] ?? null,
                'vendedor_id' => $datos['vendedor_id'],
                'sucursal_id' => $datos['sucursal_id'],
                'cliente_id' => null,
                'notas' => $datos['notas'] ?? null,
            ]);

            AuditoriaService::registrar([
                'empleado_id' => Auth::id(),
                'accion' => 'crear',
                'tabla' => 'prospectos',
                'registro_id' => $prospecto->id,
                'datos_nuevos' => $prospecto->toArray(),
            ]);

            return $prospecto;
        });
    }

    public function actualizar(Prospecto $prospecto, array $datos): void
    {
        DB::transaction(function () use ($prospecto, $datos): void {
            $anterior = $prospecto->toArray();

            $actualizaciones = [
                'codigo' => $datos['codigo'] ?? $prospecto->codigo,
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'] ?? null,
                'email' => $datos['email'] ?? null,
                'telefono' => $datos['telefono'] ?? null,
                'pais_destino' => $datos['pais_destino'] ?? null,
                'fecha_viaje_estimada' => $datos['fecha_viaje_estimada'] ?? null,
                'numero_viajeros' => $datos['numero_viajeros'] ?? null,
                'origen' => $datos['origen'] ?? $prospecto->origen,
                'estado' => $datos['estado'] ?? $prospecto->estado,
                'motivo_perdida' => ($datos['estado'] ?? $prospecto->estado) === 'perdido' ? ($datos['motivo_perdida'] ?? null) : null,
                'proximo_seguimiento' => $datos['proximo_seguimiento'] ?? null,
                'vendedor_id' => $datos['vendedor_id'],
                'sucursal_id' => $datos['sucursal_id'],
                'notas' => $datos['notas'] ?? null,
            ];

            $prospecto->update($actualizaciones);
            $prospecto->refresh();

            AuditoriaService::registrar([
                'empleado_id' => Auth::id(),
                'accion' => 'actualizar',
                'tabla' => 'prospectos',
                'registro_id' => $prospecto->id,
                'datos_anteriores' => $anterior,
                'datos_nuevos' => $prospecto->toArray(),
            ]);
        });
    }

    public function actualizarEstado(Prospecto $prospecto, string $nuevoEstado, ?string $motivoPerdida = null): Prospecto
    {
        if ($prospecto->estado === 'convertido' && $nuevoEstado !== 'convertido') {
            throw new DomainException('No se puede cambiar el estado de un prospecto ya convertido a cliente.');
        }

        if ($nuevoEstado === 'perdido' && empty($motivoPerdida)) {
            throw new DomainException('Debes indicar un motivo de pérdida.');
        }

        DB::transaction(function () use ($prospecto, $nuevoEstado, $motivoPerdida): void {
            $anterior = $prospecto->toArray();

            $prospecto->update([
                'estado' => $nuevoEstado,
                'motivo_perdida' => $nuevoEstado === 'perdido' ? $motivoPerdida : null,
            ]);
            $prospecto->refresh();

            AuditoriaService::registrar([
                'empleado_id' => Auth::id(),
                'accion' => 'actualizar',
                'tabla' => 'prospectos',
                'registro_id' => $prospecto->id,
                'datos_anteriores' => $anterior,
                'datos_nuevos' => $prospecto->toArray(),
            ]);
        });

        return $prospecto;
    }

    public function convertirACliente(Prospecto $prospecto, array $datosClienteAdicionales = []): Cliente
    {
        if ($prospecto->estado === 'convertido' || $prospecto->cliente_id) {
            throw new DomainException('Este prospecto ya ha sido convertido en cliente anteriormente.');
        }

        return DB::transaction(function () use ($prospecto, $datosClienteAdicionales) {
            $codigoCliente = GeneradorCodigoService::siguiente(Cliente::class, 'CLI-');

            $cliente = Cliente::create([
                'codigo' => $codigoCliente,
                'tipo' => 'persona',
                'nombres' => $prospecto->nombres,
                'apellidos' => $prospecto->apellidos,
                'email' => $prospecto->email,
                'telefono' => $prospecto->telefono,
                'tipo_documento' => $datosClienteAdicionales['tipo_documento'] ?? null,
                'numero_documento' => $datosClienteAdicionales['numero_documento'] ?? null,
                'sucursal_id' => $prospecto->sucursal_id,
                'estado' => 'activo',
                'notas' => 'Convertido desde prospecto '.$prospecto->codigo.'. '.($prospecto->notas ?? ''),
            ]);

            AuditoriaService::registrar([
                'empleado_id' => Auth::id(),
                'accion' => 'crear',
                'tabla' => 'clientes',
                'registro_id' => $cliente->id,
                'datos_nuevos' => $cliente->toArray(),
            ]);

            $anteriorProspecto = $prospecto->toArray();

            $prospecto->update([
                'estado' => 'convertido',
                'cliente_id' => $cliente->id,
                'motivo_perdida' => null,
            ]);
            $prospecto->refresh();

            AuditoriaService::registrar([
                'empleado_id' => Auth::id(),
                'accion' => 'actualizar',
                'tabla' => 'prospectos',
                'registro_id' => $prospecto->id,
                'datos_anteriores' => $anteriorProspecto,
                'datos_nuevos' => $prospecto->toArray(),
            ]);

            return $cliente;
        });
    }

    public function eliminar(Prospecto $prospecto): void
    {
        $tienePresupuestos = Presupuesto::query()->where('prospecto_id', $prospecto->id)->exists();

        if ($tienePresupuestos) {
            throw new DomainException('No se puede eliminar: el prospecto tiene cotizaciones / presupuestos asociados.');
        }

        if ($prospecto->cliente_id || $prospecto->estado === 'convertido') {
            throw new DomainException('No se puede eliminar un prospecto que ya fue convertido en cliente.');
        }

        if ($prospecto->estado !== 'perdido') {
            throw new DomainException('Para eliminar un prospecto primero debe estar marcado como "Perdido".');
        }

        DB::transaction(function () use ($prospecto): void {
            $anterior = $prospecto->toArray();

            $prospecto->delete();

            AuditoriaService::registrar([
                'empleado_id' => Auth::id(),
                'accion' => 'eliminar',
                'tabla' => 'prospectos',
                'registro_id' => $prospecto->id,
                'datos_anteriores' => $anterior,
            ]);
        });
    }

    public function sucursalesActivas(): Collection
    {
        return Sucursal::query()
            ->where('estado', 'activo')
            ->orderBy('nombre')
            ->get();
    }

    public function vendedoresActivos(): Collection
    {
        return Empleado::query()
            ->where('estado', 'activo')
            ->orderBy('nombres')
            ->orderBy('apellidos')
            ->get();
    }

    public function siguienteCodigo(): string
    {
        return GeneradorCodigoService::siguiente(Prospecto::class, 'LEAD-');
    }
}
