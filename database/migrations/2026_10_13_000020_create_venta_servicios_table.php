<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venta_servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas');
            $table->foreignId('servicio_id')->constrained('servicios')->comment('El proveedor del servicio es el proveedor asignado (aerolinea, hotel, guia, conductor)');
            $table->foreignId('cupo_id')->nullable()->constrained('cupos')->comment('Cupo del que se descuenta, si aplica');
            $table->decimal('cantidad', 8, 2)->default(1);
            $table->decimal('costo_neto_unitario', 12, 2);
            $table->decimal('precio_unitario', 12, 2);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->enum('estado', ['pendiente', 'solicitado', 'confirmado', 'cancelado'])->default('pendiente');
            $table->string('localizador', 40)->nullable()->comment('Codigo de reserva del proveedor');

            $table->index('venta_id', 'idx_venta_servicios_venta_id');
            $table->index(['servicio_id', 'fecha_inicio'], 'idx_venta_servicios_servicio_inicio');
            $table->index(['cupo_id', 'estado'], 'idx_venta_servicios_cupo_estado');
            $table->index(['fecha_inicio', 'estado'], 'idx_venta_servicios_inicio_estado');
            $table->index('localizador', 'idx_venta_servicios_localizador');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_servicios');
    }
};
