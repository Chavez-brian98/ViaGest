<?php

namespace App\Service\Auditoria;

use App\Models\Auditoria;
use App\Models\Empleado;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditoriaService
{
    private const POR_PAGINA = 20;

    /** Campos que nunca se incluyen en datos_anteriores / datos_nuevos */
    private const CAMPOS_EXCLUIDOS = ['password', 'remember_token'];

    /**
     * Registra un evento de auditoría. Método estático para poder
     * llamarse desde cualquier servicio sin inyección de dependencias.
     */
    public static function registrar(array $datos): void
    {
        Auditoria::create([
            'empleado_id' => $datos['empleado_id'] ?? Auth::id(),
            'accion' => $datos['accion'],
            'tabla' => $datos['tabla'] ?? null,
            'registro_id' => $datos['registro_id'] ?? null,
            'datos_anteriores' => isset($datos['datos_anteriores'])
                ? self::limpiar($datos['datos_anteriores'])
                : null,
            'datos_nuevos' => isset($datos['datos_nuevos'])
                ? self::limpiar($datos['datos_nuevos'])
                : null,
            'ip' => Request::ip(),
            'created_at' => now(),
        ]);
    }

    private static function limpiar(array $datos): array
    {
        return array_diff_key($datos, array_flip(self::CAMPOS_EXCLUIDOS));
    }

    public function listar(?string $buscar = null, array $filtros = []): LengthAwarePaginator
    {
        $buscar = trim((string) $buscar);
        $accion = (string) ($filtros['accion'] ?? '');
        $tabla = (string) ($filtros['tabla'] ?? '');
        $empleadoId = (string) ($filtros['empleado_id'] ?? '');
        $rango = (string) ($filtros['rango'] ?? '');

        return Auditoria::query()
            ->with('empleado')
            ->when($buscar !== '', function (Builder $q) use ($buscar): void {
                $like = '%'.$buscar.'%';
                $q->where(function (Builder $q) use ($like): void {
                    $q->whereHas('empleado', fn (Builder $eq) => $eq
                        ->where('nombres', 'like', $like)
                        ->orWhere('apellidos', 'like', $like)
                        ->orWhere('codigo', 'like', $like)
                    )->orWhere('ip', 'like', $like);
                });
            })
            ->when($accion !== '', fn (Builder $q) => $q->where('accion', $accion))
            ->when($tabla !== '', fn (Builder $q) => $q->where('tabla', $tabla))
            ->when($empleadoId !== '', fn (Builder $q) => $q->where('empleado_id', (int) $empleadoId))
            ->when($rango !== '', function (Builder $q) use ($rango): void {
                match ($rango) {
                    'hoy' => $q->whereDate('created_at', today()),
                    '7d' => $q->where('created_at', '>=', now()->subDays(7)),
                    '30d' => $q->where('created_at', '>=', now()->subDays(30)),
                    default => null,
                };
            })
            ->orderByDesc('created_at')
            ->paginate(self::POR_PAGINA)
            ->onEachSide(1)
            ->withQueryString();
    }

    /**
     * Devuelve pares [tabla => etiqueta] de los módulos con al menos 1 registro.
     * Excluye null (eventos de sesión que se muestran por separado en el filtro de acción).
     */
    public function modulosConRegistros(): array
    {
        return Auditoria::query()
            ->select('tabla')
            ->whereNotNull('tabla')
            ->distinct()
            ->orderBy('tabla')
            ->pluck('tabla')
            ->mapWithKeys(fn ($t) => [$t => self::etiquetaTabla($t)])
            ->toArray();
    }

    public static function etiquetaTabla(?string $tabla): string
    {
        return match ($tabla) {
            'clientes' => 'Clientes',
            'empleados' => 'Empleados',
            'configuracion_empresa' => 'Configuración',
            null => 'Sesión',
            default => ucfirst(str_replace('_', ' ', (string) $tabla)),
        };
    }

    public static function etiquetaAccion(string $accion): string
    {
        return match ($accion) {
            'crear' => 'Creación',
            'actualizar' => 'Actualización',
            'eliminar' => 'Eliminación',
            'inicio_sesion' => 'Inicio de sesión',
            'cierre_sesion' => 'Cierre de sesión',
            'exportar' => 'Exportación',
            default => ucfirst($accion),
        };
    }

    /**
     * Calcula los campos que realmente cambiaron entre datos_anteriores y datos_nuevos.
     *
     * - Creación  (anterior=null): todos los campos de datos_nuevos, con antes=null.
     * - Eliminación (nuevo=null) : todos los campos de datos_anteriores, con despues=null.
     * - Actualización             : sólo los campos cuyo valor difiere.
     *
     * @return array<string, array{antes: mixed, despues: mixed}>
     */
    public static function calcularDiff(?array $anterior, ?array $nuevo): array
    {
        if ($anterior === null && $nuevo !== null) {
            return collect($nuevo)
                ->map(fn ($v) => ['antes' => null, 'despues' => $v])
                ->toArray();
        }

        if ($anterior !== null && $nuevo === null) {
            return collect($anterior)
                ->map(fn ($v) => ['antes' => $v, 'despues' => null])
                ->toArray();
        }

        if ($anterior !== null && $nuevo !== null) {
            $diff = [];
            $claves = array_unique(array_merge(array_keys($anterior), array_keys($nuevo)));

            foreach ($claves as $campo) {
                $antes = $anterior[$campo] ?? null;
                $despues = $nuevo[$campo] ?? null;

                // Comparación estricta casteada a string para evitar falsos positivos por tipo
                if ((string) $antes !== (string) $despues) {
                    $diff[$campo] = ['antes' => $antes, 'despues' => $despues];
                }
            }

            return $diff;
        }

        return [];
    }

    /** Colección de todos los empleados para el filtro del índice */
    public function empleadosParaFiltro(): Collection
    {
        return Empleado::query()
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get(['id', 'nombres', 'apellidos', 'codigo']);
    }
}
