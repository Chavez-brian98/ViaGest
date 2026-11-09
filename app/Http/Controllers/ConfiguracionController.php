<?php

namespace App\Http\Controllers;

use App\Http\Requests\Configuracion\ConfiguracionRequest;
use App\Service\Configuracion\ConfiguracionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConfiguracionController extends Controller
{
    public function __construct(private readonly ConfiguracionService $configuracionService) {}

    public function edit(): View
    {
        $configuracion = ConfiguracionService::actual();
        $usuario = auth()->user();

        return view('configuracion.edit', [
            'configuracion' => $configuracion,
            'logoUrl' => ConfiguracionService::logoUrl(),
            'inicialesMarca' => ConfiguracionService::iniciales(),
            'puedeEditar' => $usuario?->tienePermiso('ajustes_generales', 'editar') ?? false,
            'zonasHorarias' => $this->zonasHorarias($configuracion->zona_horaria),
            'monedas' => $this->monedas(),
        ]);
    }

    public function update(ConfiguracionRequest $request): RedirectResponse
    {
        $this->configuracionService->actualizar(
            $request->validated(),
            $request->file('logo'),
        );

        return redirect()
            ->route('configuracion.edit')
            ->with('success', 'Configuración actualizada correctamente.');
    }

    private function zonasHorarias(?string $actual): array
    {
        $zonas = [
            'America/El_Salvador' => 'El Salvador (GMT-6)',
            'America/Guatemala' => 'Guatemala (GMT-6)',
            'America/Tegucigalpa' => 'Honduras (GMT-6)',
            'America/Managua' => 'Nicaragua (GMT-6)',
            'America/Costa_Rica' => 'Costa Rica (GMT-6)',
            'America/Panama' => 'Panamá (GMT-5)',
            'America/Mexico_City' => 'Ciudad de México (GMT-6)',
            'America/Bogota' => 'Bogotá (GMT-5)',
            'America/Lima' => 'Lima (GMT-5)',
            'America/Santiago' => 'Santiago (GMT-4)',
            'America/Argentina/Buenos_Aires' => 'Buenos Aires (GMT-3)',
            'America/Sao_Paulo' => 'São Paulo (GMT-3)',
            'America/New_York' => 'Nueva York (GMT-5)',
            'America/Los_Angeles' => 'Los Ángeles (GMT-8)',
            'Europe/Madrid' => 'Madrid (GMT+1)',
            'UTC' => 'UTC',
        ];

        if ($actual && ! isset($zonas[$actual]) && in_array($actual, timezone_identifiers_list(), true)) {
            $zonas[$actual] = $actual;
        }

        return $zonas;
    }

    private function monedas(): array
    {
        return [
            'USD' => 'USD — Dólar estadounidense',
            'EUR' => 'EUR — Euro',
            'GTQ' => 'GTQ — Quetzal guatemalteco',
            'HNL' => 'HNL — Lempira hondureño',
            'NIO' => 'NIO — Córdoba nicaragüense',
            'CRC' => 'CRC — Colón costarricense',
            'PAB' => 'PAB — Balboa panameño',
            'DOP' => 'DOP — Peso dominicano',
            'MXN' => 'MXN — Peso mexicano',
            'COP' => 'COP — Peso colombiano',
            'PEN' => 'PEN — Sol peruano',
            'CLP' => 'CLP — Peso chileno',
            'ARS' => 'ARS — Peso argentino',
            'BRL' => 'BRL — Real brasileño',
            'CAD' => 'CAD — Dólar canadiense',
            'GBP' => 'GBP — Libra esterlina',
        ];
    }
}
