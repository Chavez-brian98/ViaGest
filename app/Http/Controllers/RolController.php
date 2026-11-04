<?php

namespace App\Http\Controllers;

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
    public function __construct(private readonly RolService $rolService) {}

    public function index(Request $request): View|Response
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $filtros = $this->filtros($request);
        $roles = $this->rolService->listar($buscar, $filtros);

        $parametrosListado = array_filter(
            ['buscar' => $buscar, ...$filtros],
            fn ($valor) => $valor !== '',
        );

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

    public function edit(Rol $role): View
    {
        return view('roles.edit', [
            'rol' => $role,
            'permisosSeleccionados' => $role->permisosAgrupados(),
            'modulosEtiquetas' => RolService::MODULOS_ETIQUETAS,
            'acciones' => RolService::ACCIONES,
        ]);
    }

    public function update(RolRequest $request, Rol $role): RedirectResponse
    {
        $this->rolService->actualizar($role, $request->validated());

        return redirect()
            ->route('roles.index')
            ->with('success', "Rol \"{$role->nombre}\" actualizado correctamente.");
    }

    public function actualizarEstado(Request $request, Rol $role): RedirectResponse|JsonResponse
    {
        $estadoDestino = (string) $request->input('estado_destino');

        try {
            $rol = $this->rolService->actualizarEstado($role, $estadoDestino);
        } catch (DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return $this->volverAlListado($request)->with('error', $e->getMessage());
        }

        $accion = $rol->estado === 'activo' ? 'habilitado' : 'deshabilitado';
        $mensaje = "Rol \"{$rol->nombre}\" {$accion} correctamente.";

        if ($request->expectsJson()) {
            return response()->json([
                'estado' => $rol->estado,
                'mensaje' => $mensaje,
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
        $buscar = trim((string) $request->input('buscar', ''));
        $parametros = array_filter(
            ['buscar' => $buscar, ...$this->filtros($request)],
            fn ($valor) => $valor !== '',
        );

        return redirect()->route('roles.index', $parametros);
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
