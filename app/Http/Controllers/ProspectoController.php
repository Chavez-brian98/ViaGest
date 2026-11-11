<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractuaConListados;
use App\Http\Requests\Prospectos\ProspectoConvertirRequest;
use App\Http\Requests\Prospectos\ProspectoEstadoRequest;
use App\Http\Requests\Prospectos\ProspectoRequest;
use App\Models\Prospecto;
use App\Service\Prospectos\ProspectoService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ProspectoController extends Controller
{
    use InteractuaConListados;

    public function __construct(private readonly ProspectoService $prospectoService) {}

    public function index(Request $request): View|Response
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $filtros = [
            'estado' => trim((string) $request->query('estado', '')),
            'origen' => trim((string) $request->query('origen', '')),
            'sucursal_id' => trim((string) $request->query('sucursal_id', '')),
            'vendedor_id' => trim((string) $request->query('vendedor_id', '')),
        ];

        $prospectos = $this->prospectoService->listar($buscar, $filtros);
        $parametrosListado = $this->limpiarParametros(['buscar' => $buscar, ...$filtros]);

        if ($request->ajax()) {
            return response()->view('prospectos._tabla', compact('prospectos', 'buscar', 'filtros', 'parametrosListado'));
        }

        return view('prospectos.index', [
            'prospectos' => $prospectos,
            'buscar' => $buscar,
            'filtros' => $filtros,
            'parametrosListado' => $parametrosListado,
            'sucursales' => $this->prospectoService->sucursalesActivas(),
            'vendedores' => $this->prospectoService->vendedoresActivos(),
            'estados' => ProspectoService::ESTADOS,
            'origenes' => ProspectoService::ORIGENES,
        ]);
    }

    public function create(): View
    {
        $prospecto = (object) [
            'id' => null,
            'codigo' => $this->prospectoService->siguienteCodigo(),
            'nombres' => old('nombres'),
            'apellidos' => old('apellidos'),
            'email' => old('email'),
            'telefono' => old('telefono'),
            'pais_destino' => old('pais_destino'),
            'fecha_viaje_estimada' => old('fecha_viaje_estimada'),
            'numero_viajeros' => old('numero_viajeros', 1),
            'origen' => old('origen', 'redes_sociales'),
            'estado' => old('estado', 'nuevo'),
            'motivo_perdida' => old('motivo_perdida'),
            'proximo_seguimiento' => old('proximo_seguimiento', now()->addDays(2)->format('Y-m-d')),
            'vendedor_id' => old('vendedor_id', auth()->id()),
            'sucursal_id' => old('sucursal_id', auth()->user()?->sucursal_id),
            'notas' => old('notas'),
        ];

        return view('prospectos.create', [
            'prospecto' => $prospecto,
            'sucursales' => $this->prospectoService->sucursalesActivas(),
            'vendedores' => $this->prospectoService->vendedoresActivos(),
            'estados' => ProspectoService::ESTADOS,
            'origenes' => ProspectoService::ORIGENES,
            'motivosPerdida' => ProspectoService::MOTIVOS_PERDIDA,
        ]);
    }

    public function store(ProspectoRequest $request): RedirectResponse
    {
        try {
            $this->prospectoService->crear($request->validated());

            return redirect()
                ->route('prospectos.index')
                ->with('success', 'Prospecto registrado correctamente.');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(Prospecto $prospecto): View
    {
        $registro = $this->prospectoService->encontrar($prospecto->id);

        return view('prospectos.edit', [
            'prospecto' => $registro,
            'sucursales' => $this->prospectoService->sucursalesActivas(),
            'vendedores' => $this->prospectoService->vendedoresActivos(),
            'estados' => ProspectoService::ESTADOS,
            'origenes' => ProspectoService::ORIGENES,
            'motivosPerdida' => ProspectoService::MOTIVOS_PERDIDA,
        ]);
    }

    public function update(ProspectoRequest $request, Prospecto $prospecto): RedirectResponse
    {
        try {
            $this->prospectoService->actualizar($prospecto, $request->validated());

            return redirect()
                ->route('prospectos.index')
                ->with('success', 'Prospecto actualizado correctamente.');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function actualizarEstado(ProspectoEstadoRequest $request, Prospecto $prospecto): RedirectResponse|JsonResponse
    {
        try {
            $prospectoActualizado = $this->prospectoService->actualizarEstado(
                $prospecto,
                (string) $request->validated('estado_destino'),
                $request->validated('motivo_perdida')
            );

            $mensaje = "Estado de prospecto {$prospectoActualizado->codigo} actualizado a {$prospectoActualizado->estado}.";

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'estado' => $prospectoActualizado->estado,
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

    public function convertir(ProspectoConvertirRequest $request, Prospecto $prospecto): RedirectResponse|JsonResponse
    {
        try {
            $cliente = $this->prospectoService->convertirACliente($prospecto, $request->validated());

            $mensaje = "¡Prospecto convertido en cliente ({$cliente->codigo}) exitosamente!";

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'mensaje' => $mensaje,
                    'cliente_id' => $cliente->id,
                    'cliente_codigo' => $cliente->codigo,
                ]);
            }

            return redirect()->route('clientes.index')->with('success', $mensaje);
        } catch (DomainException $e) {
            if ($request->expectsJson()) {
                return $this->respuestaJsonError($e->getMessage());
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Request $request, Prospecto $prospecto): RedirectResponse
    {
        try {
            $this->prospectoService->eliminar($prospecto);

            return $this->volverAlListado($request)
                ->with('success', 'Prospecto eliminado correctamente.');
        } catch (DomainException $e) {
            return $this->volverAlListado($request)->with('error', $e->getMessage());
        }
    }

    private function volverAlListado(Request $request): RedirectResponse
    {
        return $this->redireccionarAListado('prospectos.index', [
            'buscar' => trim((string) $request->input('buscar', '')),
            'estado' => (string) $request->input('estado', ''),
            'origen' => (string) $request->input('origen', ''),
            'sucursal_id' => (string) $request->input('sucursal_id', ''),
            'vendedor_id' => (string) $request->input('vendedor_id', ''),
        ]);
    }
}
