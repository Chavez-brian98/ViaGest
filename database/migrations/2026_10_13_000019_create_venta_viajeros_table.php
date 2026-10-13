<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venta_viajeros', function (Blueprint $table) {
            $table->foreignId('venta_id')->constrained('ventas');
            $table->foreignId('cliente_id')->constrained('clientes')->comment('Cada viajero es un registro de clientes');
            $table->enum('tipo', ['adulto', 'menor', 'infante'])->default('adulto');
            $table->boolean('es_titular')->default(false);
            $table->unsignedBigInteger('titular_unico')->nullable()->virtualAs('CASE WHEN es_titular = 1 THEN venta_id ELSE NULL END');

            $table->primary(['venta_id', 'cliente_id']);
            $table->unique('titular_unico', 'uq_venta_viajeros_titular');
            $table->index('cliente_id', 'idx_venta_viajeros_cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_viajeros');
    }
};
