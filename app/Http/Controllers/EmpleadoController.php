<?php

namespace App\Http\Controllers;

use App\Http\Requests\Empleados\EmpleadoEstadoRequest;
use App\Http\Requests\Empleados\EmpleadoRequest;
use App\Models\Empleado;
use App\Service\Empleados\EmpleadoService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class EmpleadoController extends Controller
{
    public function __construct(private readonly EmpleadoService $empleadoService) {}

    public function index(Request $request): View|Response
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $filtros = $this->filtros($request);
        $empleados = $this->empleadoService->listar($buscar, $filtros);
        $parametrosListado = array_filter(
            ['buscar' => $buscar, ...$filtros],
            fn ($valor) => $valor !== '',
        );

        if ($request->ajax()) {
            return response()->view('empleados._tabla', compact('empleados', 'buscar', 'filtros', 'parametrosListado'));
        }

        return view('empleados.index', [
            'empleados' => $empleados,
            'buscar' => $buscar,
            'filtros' => $filtros,
            'parametrosListado' => $parametrosListado,
            'sucursales' => $this->empleadoService->sucursalesActivas(),
        ]);
    }

    public function create(): View
    {
        return view('empleados.create', [
            'empleado' => new Empleado([
                'codigo' => $this->empleadoService->siguienteCodigo(),
            ]),
            'sucursales' => $this->empleadoService->sucursalesActivas(),
        ]);
    }

    public function store(EmpleadoRequest $request): RedirectResponse
    {
        $empleado = $this->empleadoService->crear($request->validated());

        return redirect()
            ->route('empleados.index')
            ->with('success', "Empleado {$empleado->nombreCompleto()} creado correctamente.");
    }

    public function edit(Empleado $empleado): View
    {
        return view('empleados.edit', [
            'empleado' => $empleado,
            'sucursales' => $this->empleadoService->sucursalesActivas(),
        ]);
    }

    public function update(EmpleadoRequest $request, Empleado $empleado): RedirectResponse
    {
        $empleado = $this->empleadoService->actualizar($empleado, $request->validated());

        return redirect()
            ->route('empleados.index')
            ->with('success', "Empleado {$empleado->nombreCompleto()} actualizado correctamente.");
    }

    public function actualizarEstado(EmpleadoEstadoRequest $request, Empleado $empleado): RedirectResponse|JsonResponse
    {
        $empleado = $this->empleadoService->actualizarEstado(
            $empleado,
            (string) $request->validated('estado_destino'),
        );

        $accion = $empleado->estado === 'activo' ? 'habilitado' : 'deshabilitado';
        $mensaje = "Empleado {$empleado->nombreCompleto()} {$accion} correctamente.";

        if ($request->expectsJson()) {
            return response()->json([
                'estado' => $empleado->estado,
                'mensaje' => $mensaje,
            ]);
        }

        return $this->volverAlListado($request)->with('success', $mensaje);
    }

    public function deshabilitar(Request $request, Empleado $empleado): RedirectResponse
    {
        $this->empleadoService->deshabilitar($empleado);

        return $this->volverAlListado($request)
            ->with('success', "Empleado {$empleado->nombreCompleto()} deshabilitado. Ya puedes eliminarlo si es necesario.");
    }

    public function destroy(Request $request, Empleado $empleado): RedirectResponse
    {
        try {
            $this->empleadoService->eliminar($empleado);
        } catch (DomainException $excepcion) {
            return $this->volverAlListado($request)->with('error', $excepcion->getMessage());
        }

        return $this->volverAlListado($request)
            ->with('success', "Empleado {$empleado->nombreCompleto()} eliminado correctamente.");
    }

    private function volverAlListado(Request $request): RedirectResponse
    {
        $buscar = trim((string) $request->input('buscar', ''));
        $parametros = array_filter(
            ['buscar' => $buscar, ...$this->filtros($request)],
            fn ($valor) => $valor !== '',
        );

        return redirect()->route('empleados.index', $parametros);
    }

    private function filtros(Request $request): array
    {
        $estado = $request->input('estado');
        $sucursalId = $request->input('sucursal_id');

        return [
            'estado' => in_array($estado, ['activo', 'inactivo'], true) ? (string) $estado : '',
            'sucursal_id' => is_numeric($sucursalId) && (int) $sucursalId > 0 ? (string) (int) $sucursalId : '',
        ];
    }
}
