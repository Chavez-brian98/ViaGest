<?php

use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\auth\LoginController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\CupoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\PaqueteController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ProspectoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\ServicioController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [LoginController::class, 'index'])->name('login');
    Route::post('/', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');

    Route::get('/configuracion', [ConfiguracionController::class, 'edit'])
        ->middleware('permiso:ajustes_generales,ver')
        ->name('configuracion.edit');

    Route::put('/configuracion', [ConfiguracionController::class, 'update'])
        ->middleware('permiso:ajustes_generales,editar')
        ->name('configuracion.update');

    Route::patch('/clientes/{cliente}/estado', [ClienteController::class, 'actualizarEstado'])
        ->name('clientes.estado');

    Route::patch('/clientes/{cliente}/deshabilitar', [ClienteController::class, 'deshabilitar'])
        ->name('clientes.deshabilitar');

    Route::patch('/empleados/{empleado}/estado', [EmpleadoController::class, 'actualizarEstado'])
        ->name('empleados.estado');

    Route::patch('/empleados/{empleado}/deshabilitar', [EmpleadoController::class, 'deshabilitar'])
        ->name('empleados.deshabilitar');

    Route::patch('/roles/{role}/estado', [RolController::class, 'actualizarEstado'])
        ->middleware('permiso:roles_permisos,ver')
        ->name('roles.estado');

    Route::patch('/roles/{role}/permisos', [RolController::class, 'actualizarPermisos'])
        ->middleware('permiso:roles_permisos,ver')
        ->name('roles.permisos.update');

    Route::patch('/empleados/{empleado}/permisos', [EmpleadoController::class, 'actualizarPermisos'])
        ->middleware('permiso:roles_permisos,ver')
        ->name('empleados.permisos.update');

    Route::resource('clientes', ClienteController::class)->except(['show']);
    Route::resource('empleados', EmpleadoController::class)->except(['show']);

    Route::resource('prospectos', ProspectoController::class)->except(['show'])->parameters(['prospectos' => 'prospecto']);
    Route::patch('prospectos/{prospecto}/estado', [ProspectoController::class, 'actualizarEstado'])->name('prospectos.estado');
    Route::post('prospectos/{prospecto}/convertir', [ProspectoController::class, 'convertir'])->name('prospectos.convertir');

    Route::get('cotizaciones/servicios-paquete/{paquete}', [CotizacionController::class, 'serviciosPaquete'])->name('cotizaciones.servicios-paquete');
    Route::resource('cotizaciones', CotizacionController::class)->except(['show'])->parameters(['cotizaciones' => 'cotizacion']);
    Route::patch('cotizaciones/{cotizacion}/estado', [CotizacionController::class, 'actualizarEstado'])->name('cotizaciones.estado');

    Route::middleware('permiso:roles_permisos,ver')->group(function () {
        Route::resource('roles', RolController::class)->except(['show', 'edit']);
    });

    Route::get('paquetes/buscar-servicios', [PaqueteController::class, 'buscarServicios'])->name('paquetes.buscar-servicios');
    Route::resource('paquetes', PaqueteController::class)->except(['show'])->parameters(['paquetes' => 'paquete']);
    Route::patch('paquetes/{paquete}/estado', [PaqueteController::class, 'actualizarEstado'])->name('paquetes.estado');

    Route::resource('proveedores', ProveedorController::class)->except(['show'])->parameters(['proveedores' => 'proveedor']);
    Route::patch('proveedores/{proveedor}/estado', [ProveedorController::class, 'actualizarEstado'])->name('proveedores.estado');

    Route::resource('servicios', ServicioController::class)->except(['show'])->parameters(['servicios' => 'servicio']);
    Route::patch('servicios/{servicio}/estado', [ServicioController::class, 'actualizarEstado'])->name('servicios.estado');

    Route::resource('cupos', CupoController::class)->except(['show'])->parameters(['cupos' => 'cupo']);
    Route::patch('cupos/{cupo}/estado', [CupoController::class, 'actualizarEstado'])->name('cupos.estado');

    Route::middleware('es_administrador')->group(function () {
        Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
    });
});
