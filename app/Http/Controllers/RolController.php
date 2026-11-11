<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractuaConListados;
use App\Http\Requests\Roles\RolEstadoRequest;
use App\Http\Requests\Roles\RolPermisosRequest;
use App\Http\Requests\Roles\RolRequest;
use App\Models\Rol;
use App\Service\Roles\RolService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RolController extends Controller
{
    use InteractuaConListados;

    public function __construct(private readonly RolService $rolService) {}

    public function index(Request $request): View|Response
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $filtros = $this->filtros($request);
        $roles = $this->rolService->listar($buscar, $filtros);

        $parametrosListado = $this->limpiarParametros(['buscar' => $buscar, ...$filtros]);

        $modulosEtiquetas = RolService::MODULOS_ETIQUETAS;
        $acciones = RolService::ACCIONES;

        if ($request->ajax()) {
            return response()->view('roles._tabla', compact(
                'roles',
                'buscar',
                'filtros',
                'parametrosListado',
                'modulosEtiquetas',
                'acciones'
            ));
        }

        return view('roles.index', compact(
            'roles',
            'buscar',
            'filtros',
            'parametrosListado',
            'modulosEtiquetas',
            'acciones'
        ));
    }

    public function create(): View
    {
        return view('roles.create', [
            'rol' => new Rol([
                'alcance' => 'propia_sucursal',
                'estado' => 'activo',
            ]),
            'permisosSeleccionados' => [],
            'modulosEtiquetas' => RolService::MODULOS_ETIQUETAS,
            'acciones' => RolService::ACCIONES,
        ]);
    }

    public function store(RolRequest $request): RedirectResponse
    {
        $rol = $this->rolService->crear($request->validated());

        return redirect()
            ->route('roles.index')
            ->with('success', "Rol \"{$rol->nombre}\" creado correctamente.");
    }

    public function update(RolRequest $request, Rol $role): RedirectResponse
    {
        $this->rolService->actualizar($role, $request->validated());

        return redirect()
            ->route('roles.index')
            ->with('success', "Rol \"{$role->nombre}\" actualizado correctamente.");
    }

    public function actualizarEstado(RolEstadoRequest $request, Rol $role): RedirectResponse|JsonResponse
    {
        $estadoDestino = (string) $request->validated('estado_destino');

        try {
            $rol = $this->rolService->actualizarEstado($role, $estadoDestino);
        } catch (DomainException $e) {
            if ($request->expectsJson()) {
                return $this->respuestaJsonError($e->getMessage());
            }

            return $this->volverAlListado($request)->with('error', $e->getMessage());
        }

        $accion = $rol->estado === 'activo' ? 'habilitado' : 'deshabilitado';
        $mensaje = "Rol \"{$rol->nombre}\" {$accion} correctamente.";

        if ($request->expectsJson()) {
            return $this->respuestaJsonEstado($rol->estado, $mensaje);
        }

        return $this->volverAlListado($request)->with('success', $mensaje);
    }

    public function actualizarPermisos(RolPermisosRequest $request, Rol $role): RedirectResponse|JsonResponse
    {
        $permisos = $request->validated('permisos', []);

        try {
            $this->rolService->actualizarPermisos($role, is_array($permisos) ? $permisos : []);
        } catch (DomainException $e) {
            if ($request->expectsJson()) {
                return $this->respuestaJsonError($e->getMessage());
            }

            return $this->volverAlListado($request)->with('error', $e->getMessage());
        }

        $mensaje = "Permisos del rol \"{$role->nombre}\" actualizados correctamente.";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'mensaje' => $mensaje,
                'message' => $mensaje,
                'permisos' => $role->permisosAgrupados(),
            ]);
        }

        return $this->volverAlListado($request)->with('success', $mensaje);
    }

    public function destroy(Request $request, Rol $role): RedirectResponse
    {
        try {
            $nombre = $role->nombre;
            $this->rolService->eliminar($role);
        } catch (DomainException $excepcion) {
            return $this->volverAlListado($request)->with('error', $excepcion->getMessage());
        }

        return $this->volverAlListado($request)
            ->with('success', "Rol \"{$nombre}\" eliminado correctamente.");
    }

    private function volverAlListado(Request $request): RedirectResponse
    {
        return $this->redireccionarAListado('roles.index', [
            'buscar' => trim((string) $request->input('buscar', '')),
            ...$this->filtros($request),
        ]);
    }

    private function filtros(Request $request): array
    {
        $estado = $request->input('estado');
        $alcance = $request->input('alcance');

        return [
            'estado' => in_array($estado, ['activo', 'inactivo'], true) ? (string) $estado : '',
            'alcance' => in_array($alcance, ['propia_sucursal', 'todas_sucursales'], true) ? (string) $alcance : '',
        ];
    }
}
