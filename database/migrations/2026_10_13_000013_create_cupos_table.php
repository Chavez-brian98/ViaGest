<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_id')->constrained('servicios');
            $table->enum('tipo', ['bloqueo', 'compra_adelantada'])->default('bloqueo');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->unsignedInteger('cantidad_total');
            $table->date('fecha_liberacion')->nullable()->comment('Fecha limite para devolver los espacios al proveedor');
            $table->enum('estado', ['activo', 'liberado', 'cerrado'])->default('activo');
            $table->timestamps();

            $table->index(['servicio_id', 'fecha_inicio', 'fecha_fin'], 'idx_cupos_servicio_fechas');
            $table->index(['estado', 'fecha_liberacion'], 'idx_cupos_estado_liberacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cupos');
    }
};
