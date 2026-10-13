<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regla_id')->constrained('reglas_comision');
            $table->foreignId('venta_id')->constrained('ventas');
            $table->foreignId('venta_servicio_id')->nullable()->constrained('venta_servicios')->comment('Servicio concreto (guias y conductores)');
            $table->foreignId('empleado_id')->nullable()->constrained('empleados')->comment('Beneficiario interno (vendedor)');
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->comment('Beneficiario externo (guia o conductor)');
            $table->decimal('base_monto', 12, 2);
            $table->decimal('valor_aplicado', 10, 4);
            $table->decimal('monto', 12, 2);
            $table->enum('estado', ['pendiente', 'aprobada', 'pagada', 'anulada'])->default('pendiente');
            $table->date('fecha_calculo');
            $table->date('fecha_pago')->nullable();
            $table->timestamps();

            $table->index(['empleado_id', 'estado'], 'idx_comisiones_empleado_estado');
            $table->index(['proveedor_id', 'estado'], 'idx_comisiones_proveedor_estado');
            $table->index('venta_id', 'idx_comisiones_venta_id');
            $table->index('venta_servicio_id', 'idx_comisiones_venta_servicio_id');
            $table->index('regla_id', 'idx_comisiones_regla_id');
            $table->index('fecha_calculo', 'idx_comisiones_fecha_calculo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comisiones');
    }
};
