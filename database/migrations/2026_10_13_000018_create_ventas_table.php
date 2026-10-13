<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->comment('Folio de venta');
            $table->foreignId('cliente_id')->constrained('clientes')->comment('Cliente que contrata y paga');
            $table->foreignId('vendedor_id')->constrained('empleados');
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->foreignId('presupuesto_id')->nullable()->constrained('presupuestos')->unique()->comment('Cotizacion aprobada que origino la venta');
            $table->foreignId('paquete_id')->nullable()->constrained('paquetes');
            $table->enum('tipo', ['paquete', 'servicios_individuales'])->default('paquete');
            $table->date('fecha_venta');
            $table->date('fecha_viaje_inicio')->nullable();
            $table->date('fecha_viaje_fin')->nullable();
            $table->decimal('descuento_monto', 12, 2)->default(0);
            $table->enum('estado', ['reservada', 'confirmada', 'en_curso', 'completada', 'cancelada'])->default('reservada');
            $table->date('fecha_cancelacion')->nullable();
            $table->string('motivo_cancelacion', 255)->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique('codigo', 'uq_ventas_codigo');
            $table->index(['sucursal_id', 'fecha_venta'], 'idx_ventas_sucursal_fecha');
            $table->index(['vendedor_id', 'fecha_venta'], 'idx_ventas_vendedor_fecha');
            $table->index('cliente_id', 'idx_ventas_cliente_id');
            $table->index(['estado', 'fecha_viaje_inicio'], 'idx_ventas_estado_viaje');
            $table->index('paquete_id', 'idx_ventas_paquete_id');
            $table->index('fecha_venta', 'idx_ventas_fecha_venta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
