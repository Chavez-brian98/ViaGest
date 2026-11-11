<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractuaConListados;
use App\Http\Requests\Cotizaciones\CotizacionEstadoRequest;
use App\Http\Requests\Cotizaciones\CotizacionRequest;
use App\Models\Paquete;
use App\Models\Presupuesto;
use App\Service\Cotizaciones\CotizacionService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CotizacionController extends Controller
{
    use InteractuaConListados;

    public function __construct(private readonly CotizacionService $cotizacionService) {}

    public function index(Request $request): View|Response
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $filtros = [
            'estado' => trim((string) $request->query('estado', '')),
            'sucursal_id' => trim((string) $request->query('sucursal_id', '')),
            'vendedor_id' => trim((string) $request->query('vendedor_id', '')),
        ];

        $cotizaciones = $this->cotizacionService->listar($buscar, $filtros);
        $parametrosListado = $this->limpiarParametros(['buscar' => $buscar, ...$filtros]);

        if ($request->ajax()) {
            return response()->view('cotizaciones._tabla', compact('cotizaciones', 'buscar', 'filtros', 'parametrosListado'));
        }

        return view('cotizaciones.index', [
            'cotizaciones' => $cotizaciones,
            'buscar' => $buscar,
            'filtros' => $filtros,
            'parametrosListado' => $parametrosListado,
            'sucursales' => $this->cotizacionService->sucursalesActivas(),
            'vendedores' => $this->cotizacionService->vendedoresActivos(),
            'estados' => CotizacionService::ESTADOS,
        ]);
    }

    public function create(Request $request): View
    {
        $cotizacion = (object) [
            'id' => null,
            'codigo' => $this->cotizacionService->siguienteCodigo(),
            'prospecto_id' => old('prospecto_id', $request->query('prospecto_id')),
            'cliente_id' => old('cliente_id', $request->query('cliente_id')),
            'paquete_id' => old('paquete_id', $request->query('paquete_id')),
            'vendedor_id' => old('vendedor_id', auth()->id()),
            'sucursal_id' => old('sucursal_id', auth()->user()?->sucursal_id),
            'fecha_emision' => old('fecha_emision', now()->toDateString()),
            'fecha_caducidad' => old('fecha_caducidad', now()->addDays(15)->toDateString()),
            'fecha_viaje_inicio' => old('fecha_viaje_inicio'),
            'fecha_viaje_fin' => old('fecha_viaje_fin'),
            'numero_viajeros' => old('numero_viajeros', 1),
            'descuento_monto' => old('descuento_monto', '0.00'),
            'estado' => old('estado', 'borrador'),
            'notas' => old('notas'),
            'servicios' => collect(),
        ];

        return view('cotizaciones.create', [
            'cotizacion' => $cotizacion,
            'sucursales' => $this->cotizacionService->sucursalesActivas(),
            'vendedores' => $this->cotizacionService->vendedoresActivos(),
            'clientes' => $this->cotizacionService->clientesActivos(),
            'prospectos' => $this->cotizacionService->prospectosActivos(),
            'paquetes' => $this->cotizacionService->paquetesActivos(),
            'serviciosDisponibles' => $this->cotizacionService->serviciosDisponibles(),
            'estados' => CotizacionService::ESTADOS,
        ]);
    }

    public function store(CotizacionRequest $request): RedirectResponse
    {
        try {
            $this->cotizacionService->crear($request->validated());

            return redirect()
                ->route('cotizaciones.index')
                ->with('success', 'Cotización registrada correctamente.');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(Presupuesto $cotizacion): View
    {
        $registro = $this->cotizacionService->encontrar($cotizacion->id);

        return view('cotizaciones.edit', [
            'cotizacion' => $registro,
            'sucursales' => $this->cotizacionService->sucursalesActivas(),
            'vendedores' => $this->cotizacionService->vendedoresActivos(),
            'clientes' => $this->cotizacionService->clientesActivos(),
            'prospectos' => $this->cotizacionService->prospectosActivos(),
            'paquetes' => $this->cotizacionService->paquetesActivos(),
            'serviciosDisponibles' => $this->cotizacionService->serviciosDisponibles(),
            'estados' => CotizacionService::ESTADOS,
        ]);
    }

    public function update(CotizacionRequest $request, Presupuesto $cotizacion): RedirectResponse
    {
        try {
            $this->cotizacionService->actualizar($cotizacion, $request->validated());

            return redirect()
                ->route('cotizaciones.index')
                ->with('success', 'Cotización actualizada correctamente.');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function actualizarEstado(CotizacionEstadoRequest $request, Presupuesto $cotizacion): RedirectResponse|JsonResponse
    {
        try {
            $actualizada = $this->cotizacionService->actualizarEstado(
                $cotizacion,
                (string) $request->validated('estado_destino')
            );

            $mensaje = "Estado de la cotización {$actualizada->codigo} actualizado a {$actualizada->estado}.";

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'estado' => $actualizada->estado,
                    'mensaje' => $mensaje,
                ]);
            }

            return $this->volverAlListado($request)->with('success', $mensaje);
        } catch (DomainException $e) {
            if ($request->expectsJson()) {
                return $this->respuestaJsonError($e->getMessage());
            }

            return $this->volverAlListado($request)->with('error', $e->getMessage());
        }
    }

    public function serviciosPaquete(Paquete $paquete): JsonResponse
    {
        $servicios = $this->cotizacionService->serviciosPorPaquete($paquete->id);

        return response()->json($servicios);
    }

    public function destroy(Request $request, Presupuesto $cotizacion): RedirectResponse
    {
        try {
            $this->cotizacionService->eliminar($cotizacion);

            return $this->volverAlListado($request)
                ->with('success', 'Cotización eliminada correctamente.');
        } catch (DomainException $e) {
            return $this->volverAlListado($request)->with('error', $e->getMessage());
        }
    }

    private function volverAlListado(Request $request): RedirectResponse
    {
        return $this->redireccionarAListado('cotizaciones.index', [
            'buscar' => trim((string) $request->input('buscar', '')),
            'estado' => (string) $request->input('estado', ''),
            'sucursal_id' => (string) $request->input('sucursal_id', ''),
            'vendedor_id' => (string) $request->input('vendedor_id', ''),
        ]);
    }
}
