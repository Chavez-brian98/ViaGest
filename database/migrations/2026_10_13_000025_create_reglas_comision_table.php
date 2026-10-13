<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reglas_comision', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->enum('beneficiario', ['vendedor', 'guia', 'conductor'])->comment('Guia y conductor = proveedor de tipo guia o transporte');
            $table->enum('metodo', ['porcentaje', 'monto_fijo']);
            $table->decimal('valor', 10, 4);
            $table->enum('base_calculo', ['precio_venta', 'utilidad'])->nullable()->comment('Obligatoria si el metodo es porcentaje');
            $table->decimal('venta_minima', 12, 2)->nullable();
            $table->enum('tipo_servicio', ['vuelo', 'hospedaje', 'traslado', 'actividad', 'guia', 'seguro', 'otro'])->nullable();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales');
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->unsignedSmallInteger('prioridad')->default(100)->comment('Menor numero = se evalua primero');
            $table->enum('estado', ['activa', 'inactiva'])->default('activa');
            $table->timestamps();

            $table->unique('nombre', 'uq_reglas_comision_nombre');
            $table->index(['beneficiario', 'estado', 'vigente_desde', 'vigente_hasta'], 'idx_reglas_comision_busqueda');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglas_comision');
    }
};
