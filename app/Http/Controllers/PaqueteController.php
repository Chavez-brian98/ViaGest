<?php

namespace App\Http\Controllers;

use App\Http\Requests\Paquetes\PaqueteRequest;
use App\Service\Paquetes\PaqueteService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaqueteController extends Controller
{
    public function __construct(private readonly PaqueteService $paqueteService) {}

    public function index(Request $request): View|Response
    {
        $filtros = [
            'buscar' => trim((string) $request->query('buscar', '')),
            'tipo' => trim((string) $request->query('tipo', '')),
            'estado' => trim((string) $request->query('estado', '')),
            'ciudad_destino_id' => trim((string) $request->query('ciudad_destino_id', '')),
        ];

        $paquetes = $this->paqueteService->obtenerPaginado($filtros);
        $parametrosListado = array_filter(
            $filtros,
            fn ($valor) => $valor !== '',
        );

        if ($request->ajax()) {
            return response()->view('paquetes._tabla', compact('paquetes', 'parametrosListado'));
        }

        return view('paquetes.index', [
            'paquetes' => $paquetes,
            'filtros' => $filtros,
            'parametrosListado' => $parametrosListado,
            'buscar' => $filtros['buscar'],
            'ciudades' => $this->paqueteService->ciudadesParaSeleccion(),
        ]);
    }

    public function buscarServicios(Request $request): JsonResponse
    {
        $buscar = (string) $request->query('q', '');
        $excluir = (array) $request->query('excluir', []);

        $servicios = $this->paqueteService->buscarServicios($buscar, $excluir);

        return response()->json($servicios);
    }

    public function create(): View
    {
        $paquete = (object) [
            'exists' => false,
            'codigo' => $this->paqueteService->generarCodigoSugerido(),
            'nombre' => old('nombre'),
            'descripcion' => old('descripcion'),
            'tipo' => old('tipo', 'nacional'),
            'ciudad_destino_id' => old('ciudad_destino_id'),
            'duracion_dias' => old('duracion_dias', 3),
            'duracion_noches' => old('duracion_noches', 2),
            'margen_tipo' => old('margen_tipo', 'porcentaje'),
            'margen_valor' => old('margen_valor', 20),
            'estado' => old('estado', 'borrador'),
        ];

        return view('paquetes.create', [
            'paquete' => $paquete,
            'ciudades' => $this->paqueteService->ciudadesParaSeleccion(),
            'serviciosDisponibles' => $this->paqueteService->serviciosDisponiblesParaPaquete(),
            'serviciosAsignados' => collect(old('servicios', [])),
        ]);
    }

    public function store(PaqueteRequest $request): RedirectResponse
    {
        try {
            $this->paqueteService->crear($request->validated(), (int) auth()->id());

            return redirect()
                ->route('paquetes.index')
                ->with('success', 'Paquete turístico registrado correctamente.');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(int $paquete): View
    {
        $registro = DB::table('paquetes')->find($paquete);

        if (! $registro) {
            abort(404);
        }

        $registro->exists = true;

        $serviciosAsignados = old('servicios') !== null
            ? collect(old('servicios', []))
            : $this->paqueteService->obtenerServiciosDePaquete($paquete);

        return view('paquetes.edit', [
            'paquete' => $registro,
            'ciudades' => $this->paqueteService->ciudadesParaSeleccion(),
            'serviciosDisponibles' => $this->paqueteService->serviciosDisponiblesParaPaquete(),
            'serviciosAsignados' => $serviciosAsignados,
        ]);
    }

    public function update(PaqueteRequest $request, int $paquete): RedirectResponse
    {
        try {
            $this->paqueteService->actualizar($paquete, $request->validated(), (int) auth()->id());

            return redirect()
                ->route('paquetes.index')
                ->with('success', 'Paquete turístico actualizado correctamente.');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function actualizarEstado(Request $request, int $paquete): RedirectResponse|JsonResponse
    {
        $request->validate(['estado_destino' => ['required', 'in:borrador,activo,inactivo']]);

        try {
            $this->paqueteService->cambiarEstado(
                $paquete,
                (string) $request->input('estado_destino'),
                (int) auth()->id(),
            );

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Estado del paquete actualizado correctamente.']);
            }

            return back()->with('success', 'Estado del paquete actualizado correctamente.');
        } catch (DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(int $paquete): RedirectResponse
    {
        try {
            $this->paqueteService->eliminar($paquete, (int) auth()->id());

            return redirect()
                ->route('paquetes.index')
                ->with('success', 'Paquete turístico eliminado correctamente.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
