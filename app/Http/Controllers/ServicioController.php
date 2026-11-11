<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractuaConListados;
use App\Http\Requests\Servicios\ServicioEstadoRequest;
use App\Http\Requests\Servicios\ServicioRequest;
use App\Service\Servicios\ServicioService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ServicioController extends Controller
{
    use InteractuaConListados;

    public function __construct(private readonly ServicioService $servicioService) {}

    public function index(Request $request): View|Response
    {
        $filtros = [
            'buscar' => trim((string) $request->query('buscar', '')),
            'tipo' => trim((string) $request->query('tipo', '')),
            'estado' => trim((string) $request->query('estado', '')),
            'proveedor_id' => trim((string) $request->query('proveedor_id', '')),
        ];

        $servicios = $this->servicioService->obtenerPaginado($filtros);
        $parametrosListado = $this->limpiarParametros($filtros);

        if ($request->ajax()) {
            return response()->view('servicios._tabla', compact('servicios', 'parametrosListado'));
        }

        return view('servicios.index', [
            'servicios' => $servicios,
            'filtros' => $filtros,
            'parametrosListado' => $parametrosListado,
            'buscar' => $filtros['buscar'],
            'proveedores' => $this->servicioService->proveedoresParaFiltro(),
        ]);
    }

    public function create(): View
    {
        $servicio = (object) [
            'exists' => false,
            'codigo' => $this->servicioService->generarCodigoSugerido(),
            'tipo' => old('tipo', 'actividad'),
            'nombre' => old('nombre'),
            'descripcion' => old('descripcion'),
            'proveedor_id' => old('proveedor_id'),
            'ciudad_origen_id' => old('ciudad_origen_id'),
            'ciudad_destino_id' => old('ciudad_destino_id'),
            'unidad_cobro' => old('unidad_cobro', 'por_persona'),
            'costo_neto' => old('costo_neto'),
            'vigente_desde' => old('vigente_desde'),
            'vigente_hasta' => old('vigente_hasta'),
            'estado' => old('estado', 'activo'),
        ];

        return view('servicios.create', [
            'servicio' => $servicio,
            'proveedores' => $this->servicioService->proveedoresParaSeleccion(),
            'ciudades' => $this->servicioService->ciudadesParaSeleccion(),
        ]);
    }

    public function store(ServicioRequest $request): RedirectResponse
    {
        try {
            $this->servicioService->crear($request->validated());

            return redirect()
                ->route('servicios.index')
                ->with('success', 'Servicio registrado correctamente.');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(int $servicio): View
    {
        $registro = DB::table('servicios')->find($servicio);

        if (! $registro) {
            abort(404);
        }

        $registro->exists = true;

        return view('servicios.edit', [
            'servicio' => $registro,
            'proveedores' => $this->servicioService->proveedoresParaSeleccion((int) $registro->proveedor_id),
            'ciudades' => $this->servicioService->ciudadesParaSeleccion(),
        ]);
    }

    public function update(ServicioRequest $request, int $servicio): RedirectResponse
    {
        try {
            $this->servicioService->actualizar($servicio, $request->validated());

            return redirect()
                ->route('servicios.index')
                ->with('success', 'Servicio actualizado correctamente.');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function actualizarEstado(ServicioEstadoRequest $request, int $servicio): RedirectResponse|JsonResponse
    {
        $estadoDestino = (string) $request->validated('estado_destino');

        try {
            $this->servicioService->cambiarEstado($servicio, $estadoDestino);

            $mensaje = 'Estado actualizado correctamente.';

            if ($request->expectsJson()) {
                return $this->respuestaJsonEstado($estadoDestino, $mensaje);
            }

            return back()->with('success', $mensaje);
        } catch (DomainException $e) {
            if ($request->expectsJson()) {
                return $this->respuestaJsonError($e->getMessage());
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(int $servicio): RedirectResponse
    {
        try {
            $this->servicioService->eliminar($servicio);

            return redirect()
                ->route('servicios.index')
                ->with('success', 'Servicio eliminado correctamente.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
