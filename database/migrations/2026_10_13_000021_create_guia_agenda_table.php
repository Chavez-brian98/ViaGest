<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guia_agenda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guia_id')->constrained('proveedores')->cascadeOnDelete()->comment('Proveedor de tipo guia');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->enum('tipo', ['no_disponible', 'reservado']);
            $table->foreignId('venta_servicio_id')->nullable()->constrained('venta_servicios')->unique();
            $table->string('notas', 255)->nullable();
            $table->timestamps();

            $table->index(['guia_id', 'fecha_inicio', 'fecha_fin'], 'idx_guia_agenda_guia_fechas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guia_agenda');
    }
};
