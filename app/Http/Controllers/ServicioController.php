<?php

namespace App\Http\Controllers;

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
        $parametrosListado = array_filter(
            $filtros,
            fn ($valor) => $valor !== '',
        );

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
            $this->servicioService->crear($request->validated(), (int) auth()->id());

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
            $this->servicioService->actualizar($servicio, $request->validated(), (int) auth()->id());

            return redirect()
                ->route('servicios.index')
                ->with('success', 'Servicio actualizado correctamente.');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function actualizarEstado(Request $request, int $servicio): RedirectResponse|JsonResponse
    {
        $request->validate(['estado_destino' => ['required', 'in:activo,inactivo']]);

        try {
            $this->servicioService->cambiarEstado(
                $servicio,
                (string) $request->input('estado_destino'),
                (int) auth()->id(),
            );

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Estado actualizado correctamente.']);
            }

            return back()->with('success', 'Estado actualizado correctamente.');
        } catch (DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(int $servicio): RedirectResponse
    {
        try {
            $this->servicioService->eliminar($servicio, (int) auth()->id());

            return redirect()
                ->route('servicios.index')
                ->with('success', 'Servicio eliminado correctamente.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
