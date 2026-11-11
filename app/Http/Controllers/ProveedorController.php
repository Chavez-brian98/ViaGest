<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractuaConListados;
use App\Http\Requests\Proveedores\ProveedorEstadoRequest;
use App\Http\Requests\Proveedores\ProveedorRequest;
use App\Models\Proveedor;
use App\Service\Proveedores\ProveedorService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ProveedorController extends Controller
{
    use InteractuaConListados;

    public function __construct(private readonly ProveedorService $proveedorService) {}

    public function index(Request $request): View|Response
    {
        $filtros = [
            'buscar' => trim((string) $request->query('buscar', '')),
            'tipo' => trim((string) $request->query('tipo', '')),
            'estado' => trim((string) $request->query('estado', '')),
        ];

        $proveedores = $this->proveedorService->obtenerPaginado($filtros);
        $parametrosListado = $this->limpiarParametros($filtros);

        if ($request->ajax()) {
            return response()->view('proveedores._tabla', compact('proveedores', 'parametrosListado'));
        }

        return view('proveedores.index', [
            'proveedores' => $proveedores,
            'filtros' => $filtros,
            'parametrosListado' => $parametrosListado,
            'buscar' => $filtros['buscar'],
        ]);
    }

    public function create(): View
    {
        $proveedor = (object) [
            'exists' => false,
            'codigo' => $this->proveedorService->generarCodigoSugerido(),
            'tipo' => old('tipo', 'operador'),
            'nombre' => old('nombre'),
            'razon_social' => old('razon_social'),
            'documento_fiscal' => old('documento_fiscal'),
            'pais_codigo' => old('pais_codigo', 'SV'),
            'contacto_nombre' => old('contacto_nombre'),
            'email' => old('email'),
            'telefono' => old('telefono'),
            'dias_credito' => old('dias_credito', 0),
            'notas' => old('notas'),
            'estado' => old('estado', 'activo'),
        ];

        return view('proveedores.create', compact('proveedor'));
    }

    public function store(ProveedorRequest $request): RedirectResponse
    {
        try {
            $this->proveedorService->crear($request->validated());

            return redirect()
                ->route('proveedores.index')
                ->with('success', 'Proveedor registrado correctamente.');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(Proveedor $proveedor): View
    {
        return view('proveedores.edit', ['proveedor' => $proveedor]);
    }

    public function update(ProveedorRequest $request, Proveedor $proveedor): RedirectResponse
    {
        try {
            $this->proveedorService->actualizar($proveedor, $request->validated());

            return redirect()
                ->route('proveedores.index')
                ->with('success', 'Proveedor actualizado correctamente.');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function actualizarEstado(ProveedorEstadoRequest $request, Proveedor $proveedor): RedirectResponse|JsonResponse
    {
        $estadoDestino = (string) $request->validated('estado_destino');

        try {
            $this->proveedorService->cambiarEstado($proveedor, $estadoDestino);

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

    public function destroy(Proveedor $proveedor): RedirectResponse
    {
        try {
            $this->proveedorService->eliminar($proveedor);

            return redirect()
                ->route('proveedores.index')
                ->with('success', 'Proveedor eliminado correctamente.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
