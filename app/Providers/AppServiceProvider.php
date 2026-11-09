<?php

namespace App\Providers;

use App\Service\Configuracion\ConfiguracionService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['layouts.app', 'auth.login'], function ($view): void {
            $configuracion = ConfiguracionService::actual();

            $view->with([
                'configuracion' => $configuracion,
                'logoUrl' => ConfiguracionService::logoUrl(),
                'coloresSistema' => ConfiguracionService::colores(),
                'inicialesMarca' => ConfiguracionService::iniciales(),
            ]);
        });
    }
}
