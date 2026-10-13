<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_cliente', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 20)->comment('Numero de recibo');
            $table->foreignId('venta_id')->constrained('ventas');
            $table->unsignedBigInteger('cuota_id')->nullable()->comment('Cuota a la que se aplica; NULL si es un abono libre');
            $table->foreignId('sucursal_id')->constrained('sucursales')->comment('Sucursal donde se recibio el pago');
            $table->date('fecha_pago');
            $table->decimal('monto', 12, 2);
            $table->enum('metodo_pago', ['efectivo', 'transferencia', 'tarjeta', 'cheque', 'deposito', 'otro']);
            $table->string('referencia', 80)->nullable();
            $table->foreignId('registrado_por')->constrained('empleados')->comment('Empleado (cajero) que registro el pago');
            $table->enum('estado', ['aplicado', 'anulado'])->default('aplicado');
            $table->timestamps();

            $table->unique('folio', 'uq_pagos_cliente_folio');
            $table->index(['venta_id', 'estado'], 'idx_pagos_cliente_venta_estado');
            $table->index(['cuota_id', 'venta_id'], 'idx_pagos_cliente_cuota_venta');
            $table->index(['sucursal_id', 'fecha_pago'], 'idx_pagos_cliente_sucursal_fecha');
            $table->index('fecha_pago', 'idx_pagos_cliente_fecha');
            $table->index('registrado_por', 'idx_pagos_cliente_registrado_por');

            $table->foreign(['cuota_id', 'venta_id'], 'fk_pagos_cliente_cuota_venta')
                ->references(['id', 'venta_id'])
                ->on('cuotas_cobro');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_cliente');
    }
};
