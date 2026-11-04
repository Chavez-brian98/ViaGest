<?php

namespace App\Http\Controllers;

use App\Service\Auditoria\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AuditoriaController extends Controller
{
    public function __construct(private readonly AuditoriaService $auditoriaService) {}

    public function index(Request $request): View|Response
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $filtros = $this->filtros($request);

        $registros = $this->auditoriaService->listar($buscar, $filtros);
        $modulos = $this->auditoriaService->modulosConRegistros();
        $empleados = $this->auditoriaService->empleadosParaFiltro();

        $parametrosListado = array_filter(
            ['buscar' => $buscar, ...$filtros],
            fn ($v) => $v !== '',
        );

        if ($request->ajax()) {
            return response()->view('auditoria._tabla', compact(
                'registros', 'buscar', 'filtros', 'modulos', 'empleados', 'parametrosListado'
            ));
        }

        return view('auditoria.index', compact(
            'registros', 'buscar', 'filtros', 'modulos', 'empleados', 'parametrosListado'
        ));
    }

    private function filtros(Request $request): array
    {
        $accionesValidas = ['crear', 'actualizar', 'eliminar', 'inicio_sesion', 'cierre_sesion', 'exportar'];
        $rangosValidos = ['hoy', '7d', '30d'];

        $accion = $request->input('accion');
        $tabla = $request->input('tabla');
        $empleadoId = $request->input('empleado_id');
        $rango = $request->input('rango');

        return [
            'accion' => in_array($accion, $accionesValidas, true) ? (string) $accion : '',
            'tabla' => is_string($tabla) && $tabla !== '' ? $tabla : '',
            'empleado_id' => is_numeric($empleadoId) && (int) $empleadoId > 0 ? (string) (int) $empleadoId : '',
            'rango' => in_array($rango, $rangosValidos, true) ? (string) $rango : '',
        ];
    }
}
