<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_por_pagar', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20);
            $table->enum('tipo', ['proveedor', 'gasto_operativo']);
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores');
            $table->enum('categoria_gasto', ['alquiler', 'servicios_basicos', 'planilla', 'publicidad', 'impuestos', 'mantenimiento', 'suministros', 'otro'])->nullable();
            $table->foreignId('venta_servicio_id')->nullable()->constrained('venta_servicios')->comment('Servicio vendido que origina la deuda con el proveedor');
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->string('descripcion', 255);
            $table->string('numero_documento', 50)->nullable()->comment('Factura o documento del proveedor');
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->date('fecha_programada_pago')->nullable();
            $table->decimal('monto', 12, 2);
            $table->enum('estado', ['pendiente', 'pagada', 'anulada'])->default('pendiente');
            $table->date('fecha_pago')->nullable();
            $table->enum('metodo_pago', ['efectivo', 'transferencia', 'tarjeta', 'cheque', 'deposito', 'otro'])->nullable();
            $table->string('referencia_pago', 80)->nullable();
            $table->timestamps();

            $table->unique('codigo', 'uq_cuentas_por_pagar_codigo');
            $table->index(['proveedor_id', 'fecha_vencimiento'], 'idx_cuentas_por_pagar_proveedor_venc');
            $table->index(['estado', 'fecha_programada_pago'], 'idx_cuentas_por_pagar_estado_programada');
            $table->index(['sucursal_id', 'fecha_vencimiento'], 'idx_cuentas_por_pagar_sucursal_venc');
            $table->index('venta_servicio_id', 'idx_cuentas_por_pagar_venta_servicio_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_por_pagar');
    }
};
