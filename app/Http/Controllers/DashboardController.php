<?php

namespace App\Http\Controllers;

use App\Service\Dashboard\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function index(): View
    {
        return view('dashboard', $this->dashboardService->resumen());
    }
}
