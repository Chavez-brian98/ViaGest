<?php

namespace App\Service\Clientes;

use App\Models\Cliente;
use App\Models\Sucursal;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClienteService
{
    private const POR_PAGINA = 12;

    public function listar(?string $buscar = null, array $filtros = []): LengthAwarePaginator
    {
        $buscar = trim((string) $buscar);
        $estado = (string) ($filtros['estado'] ?? '');
        $tipo = (string) ($filtros['tipo'] ?? '');
        $sucursalId = (string) ($filtros['sucursal_id'] ?? '');

        return Cliente::query()
            ->with('sucursal')
            ->when($buscar !== '', function (Builder $query) use ($buscar): void {
                $like = '%'.$buscar.'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query->where('codigo', 'like', $like)
                        ->orWhere('nombres', 'like', $like)
                        ->orWhere('apellidos', 'like', $like)
                        ->orWhere('razon_social', 'like', $like)
                        ->orWhere('numero_documento', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('telefono', 'like', $like);
                });
            })
            ->when($estado !== '', fn (Builder $query) => $query->where('estado', $estado))
            ->when($tipo !== '', fn (Builder $query) => $query->where('tipo', $tipo))
            ->when($sucursalId !== '', fn (Builder $query) => $query->where('sucursal_id', (int) $sucursalId))
            ->orderByRaw('COALESCE(razon_social, apellidos, nombres) asc')
            ->orderBy('codigo')
            ->paginate(self::POR_PAGINA)
            ->onEachSide(1)
            ->withQueryString();
    }

    public function crear(array $datos): Cliente
    {
        $datos['estado'] ??= 'activo';

        return Cliente::create($datos);
    }

    public function actualizar(Cliente $cliente, array $datos): Cliente
    {
        $cliente->update($datos);

        return $cliente->refresh();
    }

    public function actualizarEstado(Cliente $cliente, string $estado): Cliente
    {
        if ($cliente->estado !== $estado) {
            $cliente->update(['estado' => $estado]);
        }

        return $cliente;
    }

    public function deshabilitar(Cliente $cliente): void
    {
        $this->actualizarEstado($cliente, 'inactivo');
    }

    public function eliminar(Cliente $cliente): void
    {
        if ($cliente->estado !== 'inactivo') {
            throw new DomainException('Primero deshabilita al cliente antes de eliminarlo.');
        }

        if ($this->tieneRegistrosRelacionados($cliente)) {
            throw new DomainException('No se puede eliminar: el cliente tiene ventas, viajeros o prospectos asociados.');
        }

        $cliente->delete();
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
        $numero = ((int) Cliente::query()->max('id')) + 1;

        do {
            $codigo = 'CLI-'.str_pad((string) $numero, 4, '0', STR_PAD_LEFT);
            $existe = Cliente::query()->where('codigo', $codigo)->exists();
            $numero++;
        } while ($existe);

        return $codigo;
    }

    private function tieneRegistrosRelacionados(Cliente $cliente): bool
    {
        return DB::table('ventas')->where('cliente_id', $cliente->id)->exists()
            || DB::table('venta_viajeros')->where('cliente_id', $cliente->id)->exists()
            || DB::table('prospectos')->where('cliente_id', $cliente->id)->exists();
    }
}
