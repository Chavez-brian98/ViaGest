<?php

namespace App\Http\Controllers;

use App\Service\Dashboard\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function index(): View
    {
        $resumen = $this->dashboardService->resumen();

        return view('dashboard', [
            'metricas' => $resumen['metricas'] ?? [],
            'graficas' => $resumen['graficas'] ?? [],
            'ventasPorMes' => $resumen['ventasPorMes'] ?? [],
            'ventasPorServicio' => $resumen['ventasPorServicio'] ?? [],
            'ventasPorSucursal' => $resumen['ventasPorSucursal'] ?? [],
            'cuotasProximas' => $resumen['cuotasProximas'] ?? [],
        ]);
    }
}


