<?php

namespace App\Service\Empleados;

use App\Models\Empleado;
use App\Models\Sucursal;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EmpleadoService
{
    private const POR_PAGINA = 12;

    public function listar(?string $buscar = null, array $filtros = []): LengthAwarePaginator
    {
        $buscar = trim((string) $buscar);
        $estado = (string) ($filtros['estado'] ?? '');
        $sucursalId = (string) ($filtros['sucursal_id'] ?? '');

        return Empleado::query()
            ->with('sucursal')
            ->when($buscar !== '', function (Builder $query) use ($buscar): void {
                $like = '%'.$buscar.'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query->where('codigo', 'like', $like)
                        ->orWhere('nombres', 'like', $like)
                        ->orWhere('apellidos', 'like', $like)
                        ->orWhere('numero_documento', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('telefono', 'like', $like)
                        ->orWhere('cargo', 'like', $like);
                });
            })
            ->when($estado !== '', fn (Builder $query) => $query->where('estado', $estado))
            ->when($sucursalId !== '', fn (Builder $query) => $query->where('sucursal_id', (int) $sucursalId))
            ->orderByRaw('apellidos asc')
            ->orderBy('nombres')
            ->paginate(self::POR_PAGINA)
            ->onEachSide(1)
            ->withQueryString();
    }

    public function crear(array $datos): Empleado
    {
        $datos['estado'] ??= 'activo';

        return Empleado::create($datos);
    }

    public function actualizar(Empleado $empleado, array $datos): Empleado
    {
        $empleado->update($datos);

        return $empleado->refresh();
    }

    public function actualizarEstado(Empleado $empleado, string $estado): Empleado
    {
        if ($empleado->estado !== $estado) {
            $empleado->update(['estado' => $estado]);
        }

        return $empleado;
    }

    public function deshabilitar(Empleado $empleado): void
    {
        $this->actualizarEstado($empleado, 'inactivo');
    }

    public function eliminar(Empleado $empleado): void
    {
        if ($empleado->estado !== 'inactivo') {
            throw new DomainException('Primero deshabilita al empleado antes de eliminarlo.');
        }

        // En este sistema, Empleado es la entidad de Auth,
        // por lo que debemos verificar que no sea el último administrador o tenga dependencias críticas.
        // Por ahora seguimos el patrón de Cliente.

        $empleado->delete();
    }

    public function sucursalesActivas(): Collection
    {
        return Sucursal::query()
            ->where('estado', 'activa')
            ->orderBy('nombre')
            ->get();
    }

    public function siguienteCodigo(): string
    {
        $numero = ((int) Empleado::query()->max('id')) + 1;

        do {
            $codigo = 'EMP-'.str_pad((string) $numero, 4, '0', STR_PAD_LEFT);
            $existe = Empleado::query()->where('codigo', $codigo)->exists();
            $numero++;
        } while ($existe);

        return $codigo;
    }
}
