<?php

namespace App\Http\Controllers;

use App\Http\Requests\Perfil\PerfilRequest;
use App\Models\Empleado;
use App\Service\Perfil\PerfilService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function __construct(private readonly PerfilService $perfilService) {}

    public function edit(Request $request): View
    {
        /** @var Empleado $empleado */
        $empleado = $request->user()->loadMissing(['sucursal', 'rol']);

        return view('perfil.edit', [
            'empleado' => $empleado,
            'sucursales' => $this->perfilService->sucursales($empleado->sucursal_id),
        ]);
    }

    public function update(PerfilRequest $request): RedirectResponse
    {
        /** @var Empleado $empleado */
        $empleado = $request->user();

        $this->perfilService->actualizar(
            $empleado,
            $request->validated(),
            $request->file('foto'),
        );

        return redirect()
            ->route('perfil.edit')
            ->with('success', 'Perfil actualizado correctamente.');
    }
}
