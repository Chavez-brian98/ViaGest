<?php

namespace App\Http\Controllers;

use App\Http\Requests\Clientes\ClienteEstadoRequest;
use App\Http\Requests\Clientes\ClienteRequest;
use App\Models\Cliente;
use App\Service\Clientes\ClienteService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function __construct(private readonly ClienteService $clienteService) {}

    public function index(Request $request): View|Response
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $filtros = $this->filtros($request);
        $clientes = $this->clienteService->listar($buscar, $filtros);
        $parametrosListado = array_filter(
            ['buscar' => $buscar, ...$filtros],
            fn ($valor) => $valor !== '',
        );

        if ($request->ajax()) {
            return response()->view('clientes._tabla', compact('clientes', 'buscar', 'filtros', 'parametrosListado'));
        }

        return view('clientes.index', [
            'clientes' => $clientes,
            'buscar' => $buscar,
            'filtros' => $filtros,
            'parametrosListado' => $parametrosListado,
            'sucursales' => $this->clienteService->sucursalesActivas(),
        ]);
    }

    public function create(): View
    {
        return view('clientes.create', [
            'cliente' => new Cliente([
                'tipo' => 'persona',
                'codigo' => $this->clienteService->siguienteCodigo(),
            ]),
            'sucursales' => $this->clienteService->sucursalesActivas(),
        ]);
    }

    public function store(ClienteRequest $request): RedirectResponse
    {
        $cliente = $this->clienteService->crear($request->validated());

        return redirect()
            ->route('clientes.index')
            ->with('success', "Cliente {$cliente->nombreMostrado()} creado correctamente.");
    }

    public function edit(Cliente $cliente): View
    {
        return view('clientes.edit', [
            'cliente' => $cliente,
            'sucursales' => $this->clienteService->sucursalesActivas(),
        ]);
    }

    public function update(ClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $cliente = $this->clienteService->actualizar($cliente, $request->validated());

        return redirect()
            ->route('clientes.index')
            ->with('success', "Cliente {$cliente->nombreMostrado()} actualizado correctamente.");
    }

    public function actualizarEstado(ClienteEstadoRequest $request, Cliente $cliente): RedirectResponse|JsonResponse
    {
        $cliente = $this->clienteService->actualizarEstado(
            $cliente,
            (string) $request->validated('estado_destino'),
        );

        $accion = $cliente->estado === 'activo' ? 'habilitado' : 'deshabilitado';
        $mensaje = "Cliente {$cliente->nombreMostrado()} {$accion} correctamente.";

        if ($request->expectsJson()) {
            return response()->json([
                'estado' => $cliente->estado,
                'mensaje' => $mensaje,
            ]);
        }

        return $this->volverAlListado($request)->with('success', $mensaje);
    }

    public function deshabilitar(Request $request, Cliente $cliente): RedirectResponse
    {
        $this->clienteService->deshabilitar($cliente);

        return $this->volverAlListado($request)
            ->with('success', "Cliente {$cliente->nombreMostrado()} deshabilitado. Ya puedes eliminarlo si es necesario.");
    }

    public function destroy(Request $request, Cliente $cliente): RedirectResponse
    {
        try {
            $this->clienteService->eliminar($cliente);
        } catch (DomainException $excepcion) {
            return $this->volverAlListado($request)->with('error', $excepcion->getMessage());
        }

        return $this->volverAlListado($request)
            ->with('success', "Cliente {$cliente->nombreMostrado()} eliminado correctamente.");
    }

    private function volverAlListado(Request $request): RedirectResponse
    {
        $buscar = trim((string) $request->input('buscar', ''));
        $parametros = array_filter(
            ['buscar' => $buscar, ...$this->filtros($request)],
            fn ($valor) => $valor !== '',
        );

        return redirect()->route('clientes.index', $parametros);
    }

    private function filtros(Request $request): array
    {
        $estado = $request->input('estado');
        $tipo = $request->input('tipo');
        $sucursalId = $request->input('sucursal_id');

        return [
            'estado' => in_array($estado, ['activo', 'inactivo'], true) ? (string) $estado : '',
            'tipo' => in_array($tipo, ['persona', 'empresa'], true) ? (string) $tipo : '',
            'sucursal_id' => is_numeric($sucursalId) && (int) $sucursalId > 0 ? (string) (int) $sucursalId : '',
        ];
    }
}
