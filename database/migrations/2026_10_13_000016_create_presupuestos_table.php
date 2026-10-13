<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuestos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20);
            $table->foreignId('prospecto_id')->nullable()->constrained('prospectos');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes');
            $table->foreignId('vendedor_id')->constrained('empleados');
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->foreignId('paquete_id')->nullable()->constrained('paquetes')->comment('Paquete de origen, si se baso en uno');
            $table->date('fecha_emision');
            $table->date('fecha_caducidad');
            $table->date('fecha_viaje_inicio')->nullable();
            $table->date('fecha_viaje_fin')->nullable();
            $table->unsignedSmallInteger('numero_viajeros')->default(1);
            $table->decimal('descuento_monto', 12, 2)->default(0);
            $table->enum('estado', ['borrador', 'enviado', 'aprobado', 'rechazado', 'caducado', 'convertido'])->default('borrador');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique('codigo', 'uq_presupuestos_codigo');
            $table->index(['estado', 'fecha_caducidad'], 'idx_presupuestos_estado_caducidad');
            $table->index(['vendedor_id', 'estado'], 'idx_presupuestos_vendedor_estado');
            $table->index(['sucursal_id', 'fecha_emision'], 'idx_presupuestos_sucursal_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuestos');
    }
};
