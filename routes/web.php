<?php

use App\Http\Controllers\auth\LoginController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PerfilController;
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

    Route::patch('/clientes/{cliente}/estado', [ClienteController::class, 'actualizarEstado'])
        ->name('clientes.estado');

    Route::patch('/clientes/{cliente}/deshabilitar', [ClienteController::class, 'deshabilitar'])
        ->name('clientes.deshabilitar');

    Route::patch('/empleados/{empleado}/estado', [App\Http\Controllers\EmpleadoController::class, 'actualizarEstado'])
        ->name('empleados.estado');

    Route::resource('clientes', ClienteController::class)->except(['show']);
    Route::resource('empleados', App\Http\Controllers\EmpleadoController::class)->except(['show']);
});
