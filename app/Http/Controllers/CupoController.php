<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cupos\CupoRequest;
use App\Service\Cupos\CupoService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CupoController extends Controller
{
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
        $parametrosListado = array_filter(
            $filtros,
            fn ($valor) => $valor !== '',
        );

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
            $this->cupoService->crear($request->validated(), (int) auth()->id());

            return redirect()
                ->route('cupos.index')
                ->with('success', 'Cupo registrado correctamente.');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(int $cupo): View
    {
        $registro = DB::table('cupos')->find($cupo);

        if (! $registro) {
            abort(404);
        }

        $registro->exists = true;

        return view('cupos.edit', [
            'cupo' => $registro,
            'servicios' => $this->cupoService->serviciosParaSeleccion((int) $registro->servicio_id),
        ]);
    }

    public function update(CupoRequest $request, int $cupo): RedirectResponse
    {
        try {
            $this->cupoService->actualizar($cupo, $request->validated(), (int) auth()->id());

            return redirect()
                ->route('cupos.index')
                ->with('success', 'Cupo actualizado correctamente.');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function actualizarEstado(Request $request, int $cupo): RedirectResponse|JsonResponse
    {
        $request->validate(['estado_destino' => ['required', 'in:activo,liberado,cerrado']]);

        try {
            $this->cupoService->cambiarEstado(
                $cupo,
                (string) $request->input('estado_destino'),
                (int) auth()->id(),
            );

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Estado del cupo actualizado correctamente.']);
            }

            return back()->with('success', 'Estado del cupo actualizado correctamente.');
        } catch (DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(int $cupo): RedirectResponse
    {
        try {
            $this->cupoService->eliminar($cupo, (int) auth()->id());

            return redirect()
                ->route('cupos.index')
                ->with('success', 'Cupo eliminado correctamente.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
