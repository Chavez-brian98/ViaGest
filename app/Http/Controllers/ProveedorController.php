<?php

namespace App\Http\Controllers;

use App\Http\Requests\Proveedores\ProveedorRequest;
use App\Service\Proveedores\ProveedorService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProveedorController extends Controller
{
    public function __construct(private readonly ProveedorService $proveedorService) {}

    public function index(Request $request): View|Response
    {
        $filtros = [
            'buscar' => trim((string) $request->query('buscar', '')),
            'tipo' => trim((string) $request->query('tipo', '')),
            'estado' => trim((string) $request->query('estado', '')),
        ];

        $proveedores = $this->proveedorService->obtenerPaginado($filtros);
        $parametrosListado = array_filter(
            $filtros,
            fn ($valor) => $valor !== '',
        );

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
            $this->proveedorService->crear($request->validated(), (int) auth()->id());

            return redirect()
                ->route('proveedores.index')
                ->with('success', 'Proveedor registrado correctamente.');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(int $proveedor): View
    {
        $registro = DB::table('proveedores')->find($proveedor);

        if (! $registro) {
            abort(404);
        }

        $registro->exists = true;

        return view('proveedores.edit', ['proveedor' => $registro]);
    }

    public function update(ProveedorRequest $request, int $proveedor): RedirectResponse
    {
        try {
            $this->proveedorService->actualizar($proveedor, $request->validated(), (int) auth()->id());

            return redirect()
                ->route('proveedores.index')
                ->with('success', 'Proveedor actualizado correctamente.');
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function actualizarEstado(Request $request, int $proveedor): RedirectResponse|JsonResponse
    {
        $request->validate(['estado_destino' => ['required', 'in:activo,inactivo']]);

        try {
            $this->proveedorService->cambiarEstado(
                $proveedor,
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

    public function destroy(int $proveedor): RedirectResponse
    {
        try {
            $this->proveedorService->eliminar($proveedor, (int) auth()->id());

            return redirect()
                ->route('proveedores.index')
                ->with('success', 'Proveedor eliminado correctamente.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
