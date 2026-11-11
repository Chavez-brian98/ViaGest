<?php

namespace App\Service\Proveedores;

use App\Models\Comision;
use App\Models\CuentaPagar;
use App\Models\GuiaAgenda;
use App\Models\Proveedor;
use App\Models\Servicio;
use App\Service\Auditoria\AuditoriaService;
use App\Service\Shared\GeneradorCodigoService;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProveedorService
{
    private const POR_PAGINA = 15;

    public function obtenerPaginado(array $filtros, int $porPagina = self::POR_PAGINA): LengthAwarePaginator
    {
        $query = Proveedor::query();

        $buscar = trim((string) ($filtros['buscar'] ?? ''));

        if ($buscar !== '') {
            $like = '%'.$buscar.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('codigo', 'like', $like)
                    ->orWhere('nombre', 'like', $like)
                    ->orWhere('razon_social', 'like', $like)
                    ->orWhere('documento_fiscal', 'like', $like)
                    ->orWhere('contacto_nombre', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('telefono', 'like', $like);
            });
        }

        if (! empty($filtros['tipo'])) {
            $query->where('tipo', $filtros['tipo']);
        }

        if (! empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        return $query
            ->orderBy('nombre')
            ->paginate($porPagina)
            ->withQueryString();
    }

    public function crear(array $datos, ?int $empleadoId = null): Proveedor
    {
        return DB::transaction(function () use ($datos, $empleadoId) {
            $datos['estado'] ??= 'activo';
            $datos['dias_credito'] ??= 0;

            $proveedor = Proveedor::create($datos);

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'crear',
                'tabla' => 'proveedores',
                'registro_id' => $proveedor->id,
                'datos_nuevos' => $proveedor->toArray(),
            ]);

            return $proveedor;
        });
    }

    public function actualizar(Proveedor $proveedor, array $datos, ?int $empleadoId = null): Proveedor
    {
        return DB::transaction(function () use ($proveedor, $datos, $empleadoId) {
            $anterior = $proveedor->toArray();

            $datos['dias_credito'] ??= 0;

            $proveedor->update($datos);
            $proveedor->refresh();

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'actualizar',
                'tabla' => 'proveedores',
                'registro_id' => $proveedor->id,
                'datos_anteriores' => $anterior,
                'datos_nuevos' => $proveedor->toArray(),
            ]);

            return $proveedor;
        });
    }

    public function cambiarEstado(Proveedor $proveedor, string $estadoDestino, ?int $empleadoId = null): Proveedor
    {
        return DB::transaction(function () use ($proveedor, $estadoDestino, $empleadoId) {
            $anterior = $proveedor->toArray();

            $proveedor->update(['estado' => $estadoDestino]);
            $proveedor->refresh();

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'actualizar',
                'tabla' => 'proveedores',
                'registro_id' => $proveedor->id,
                'datos_anteriores' => $anterior,
                'datos_nuevos' => $proveedor->toArray(),
            ]);

            return $proveedor;
        });
    }

    public function eliminar(Proveedor $proveedor, ?int $empleadoId = null): void
    {
        DB::transaction(function () use ($proveedor, $empleadoId): void {
            if ($proveedor->estado === 'activo') {
                throw new DomainException('No se puede eliminar un proveedor activo. Desactívelo primero.');
            }

            if ($this->tieneRegistrosRelacionados($proveedor)) {
                throw new DomainException('No se puede eliminar el proveedor porque tiene servicios u otros registros asociados.');
            }

            $anterior = $proveedor->toArray();

            $proveedor->delete();

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'eliminar',
                'tabla' => 'proveedores',
                'registro_id' => $proveedor->id,
                'datos_anteriores' => $anterior,
            ]);
        });
    }

    public function generarCodigoSugerido(): string
    {
        return GeneradorCodigoService::siguiente(Proveedor::class, 'PROV-');
    }

    private function tieneRegistrosRelacionados(Proveedor $proveedor): bool
    {
        return Servicio::query()->where('proveedor_id', $proveedor->id)->exists()
            || CuentaPagar::query()->where('proveedor_id', $proveedor->id)->exists()
            || Comision::query()->where('proveedor_id', $proveedor->id)->exists()
            || GuiaAgenda::query()->where('guia_id', $proveedor->id)->exists()
            || DB::table('guia_idiomas')->where('guia_id', $proveedor->id)->exists()
            || DB::table('guia_especialidades')->where('guia_id', $proveedor->id)->exists();
    }
}
