<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractuaConListados;
use App\Http\Requests\Cupos\CupoEstadoRequest;
use App\Http\Requests\Cupos\CupoRequest;
use App\Models\Cupo;
use App\Service\Cupos\CupoService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CupoController extends Controller
{
    use InteractuaConListados;

    public function __construct(private readonly CupoService $cupoService) {}

    public function index(Request $request): View|Response
    {
        $filtros = [
            'buscar' => trim((string) $request->query('buscar', '')),
            'tipo' => trim((string) $request->query('tipo', '')),
            'estado' => trim((string) $request->query('estado', '')),
            'servicio_id' => trim((string) $request->query('servicio_id', '')),
        ];

        $cupos = $this->cupoService->obtenerPaginado($filtros);
        $parametrosListado = $this->limpiarParametros($filtros);

        if ($request->ajax()) {
            return response()->view('cupos._tabla', compact('cupos', 'parametrosListado'));
        }

        return view('cupos.index', [
            'cupos' => $cupos,
            'filtros' => $filtros,
            'parametrosListado' => $parametrosListado,
            'buscar' => $filtros['buscar'],
            'servicios' => $this->cupoService->serviciosParaSeleccion(),
        ]);
    }

    public function create(): View
    {
        $cupo = (object) [
            'exists' => false,
            'servicio_id' => old('servicio_id'),
            'tipo' => old('tipo', 'bloqueo'),
            'fecha_inicio' => old('fecha_inicio'),
            'fecha_fin' => old('fecha_fin'),
            'cantidad_total' => old('cantidad_total', 10),
            'fecha_liberacion' => old('fecha_liberacion'),
            'estado' => old('estado', 'activo'),
        ];

        return view('cupos.create', [
            'cupo' => $cupo,
            'servicios' => $this->cupoService->serviciosParaSeleccion(),
        ]);
    }

    public function store(CupoRequest $request): RedirectResponse
    {
        try {
            $this->cupoService->crear($request->validated());

            return redirect()
                ->route('cupos.index')
                ->with('success', 'Cupo registrado correctamente.');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(Cupo $cupo): View
    {
        return view('cupos.edit', [
            'cupo' => $cupo,
            'servicios' => $this->cupoService->serviciosParaSeleccion((int) $cupo->servicio_id),
        ]);
    }

    public function update(CupoRequest $request, Cupo $cupo): RedirectResponse
    {
        try {
            $this->cupoService->actualizar($cupo, $request->validated());

            return redirect()
                ->route('cupos.index')
                ->with('success', 'Cupo actualizado correctamente.');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function actualizarEstado(CupoEstadoRequest $request, Cupo $cupo): RedirectResponse|JsonResponse
    {
        $estadoDestino = (string) $request->validated('estado_destino');

        try {
            $this->cupoService->cambiarEstado($cupo, $estadoDestino);

            $mensaje = 'Estado del cupo actualizado correctamente.';

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

    public function destroy(Cupo $cupo): RedirectResponse
    {
        try {
            $this->cupoService->eliminar($cupo);

            return redirect()
                ->route('cupos.index')
                ->with('success', 'Cupo eliminado correctamente.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
