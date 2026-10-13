<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuotas_cobro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas');
            $table->unsignedSmallInteger('numero');
            $table->date('fecha_vencimiento');
            $table->decimal('monto', 12, 2);
            $table->enum('estado', ['activa', 'anulada'])->default('activa');
            $table->timestamps();

            $table->unique(['venta_id', 'numero'], 'uq_cuotas_cobro_venta_numero');
            $table->unique(['id', 'venta_id'], 'uq_cuotas_cobro_id_venta');
            $table->index(['fecha_vencimiento', 'estado'], 'idx_cuotas_cobro_vencimiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuotas_cobro');
    }
};
