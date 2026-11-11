<?php

namespace App\Service\Proveedores;

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
        $query = DB::table('proveedores');

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

    public function crear(array $datos, ?int $empleadoId = null): int
    {
        return DB::transaction(function () use ($datos, $empleadoId) {
            $datos['estado'] ??= 'activo';
            $datos['dias_credito'] ??= 0;
            $datos['created_at'] = now();
            $datos['updated_at'] = now();

            $proveedorId = DB::table('proveedores')->insertGetId($datos);

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'crear',
                'tabla' => 'proveedores',
                'registro_id' => $proveedorId,
                'datos_nuevos' => (array) DB::table('proveedores')->find($proveedorId),
            ]);

            return $proveedorId;
        });
    }

    public function actualizar(int $id, array $datos, ?int $empleadoId = null): void
    {
        DB::transaction(function () use ($id, $datos, $empleadoId): void {
            $anterior = DB::table('proveedores')->find($id);

            if (! $anterior) {
                throw new DomainException('El proveedor no existe.');
            }

            $datos['dias_credito'] ??= 0;
            $datos['updated_at'] = now();

            DB::table('proveedores')->where('id', $id)->update($datos);

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'actualizar',
                'tabla' => 'proveedores',
                'registro_id' => $id,
                'datos_anteriores' => (array) $anterior,
                'datos_nuevos' => (array) DB::table('proveedores')->find($id),
            ]);
        });
    }

    public function cambiarEstado(int $id, string $estadoDestino, ?int $empleadoId = null): void
    {
        DB::transaction(function () use ($id, $estadoDestino, $empleadoId): void {
            $anterior = DB::table('proveedores')->find($id);

            if (! $anterior) {
                throw new DomainException('El proveedor no existe.');
            }

            DB::table('proveedores')->where('id', $id)->update([
                'estado' => $estadoDestino,
                'updated_at' => now(),
            ]);

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'actualizar',
                'tabla' => 'proveedores',
                'registro_id' => $id,
                'datos_anteriores' => (array) $anterior,
                'datos_nuevos' => (array) DB::table('proveedores')->find($id),
            ]);
        });
    }

    public function eliminar(int $id, ?int $empleadoId = null): void
    {
        DB::transaction(function () use ($id, $empleadoId): void {
            $proveedor = DB::table('proveedores')->find($id);

            if (! $proveedor) {
                throw new DomainException('El proveedor no existe.');
            }

            if ($proveedor->estado === 'activo') {
                throw new DomainException('No se puede eliminar un proveedor activo. Desactívelo primero.');
            }

            if ($this->tieneRegistrosRelacionados($id)) {
                throw new DomainException('No se puede eliminar el proveedor porque tiene servicios u otros registros asociados.');
            }

            DB::table('proveedores')->where('id', $id)->delete();

            AuditoriaService::registrar([
                'empleado_id' => $empleadoId,
                'accion' => 'eliminar',
                'tabla' => 'proveedores',
                'registro_id' => $id,
                'datos_anteriores' => (array) $proveedor,
            ]);
        });
    }

    public function generarCodigoSugerido(): string
    {
        return GeneradorCodigoService::siguiente('proveedores', 'PROV-');
    }

    private function tieneRegistrosRelacionados(int $id): bool
    {
        $referencias = [
            ['servicios', 'proveedor_id'],
            ['cuentas_por_pagar', 'proveedor_id'],
            ['comisiones', 'proveedor_id'],
            ['guia_idiomas', 'guia_id'],
            ['guia_especialidades', 'guia_id'],
            ['guia_agenda', 'guia_id'],
        ];

        foreach ($referencias as [$tabla, $columna]) {
            if (DB::table($tabla)->where($columna, $id)->exists()) {
                return true;
            }
        }

        return false;
    }
}
