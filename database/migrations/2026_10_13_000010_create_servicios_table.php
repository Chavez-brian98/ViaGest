<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30);
            $table->enum('tipo', ['vuelo', 'hospedaje', 'traslado', 'actividad', 'guia', 'seguro', 'otro']);
            $table->string('nombre', 150)->comment('Ej.: Vuelo SAL-MIA, Habitacion doble Hotel X, Traslado aeropuerto-hotel');
            $table->text('descripcion')->nullable();
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->foreignId('ciudad_origen_id')->nullable()->constrained('ciudades')->comment('Vuelos y traslados');
            $table->foreignId('ciudad_destino_id')->nullable()->constrained('ciudades')->comment('Vuelos y traslados; en hospedajes, la ciudad del hotel');
            $table->enum('unidad_cobro', ['por_persona', 'por_habitacion_noche', 'por_vehiculo', 'por_dia', 'por_grupo', 'por_unidad'])->default('por_persona');
            $table->decimal('costo_neto', 12, 2)->comment('Tarifa neta negociada con el proveedor');
            $table->date('vigente_desde')->nullable();
            $table->date('vigente_hasta')->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();

            $table->unique('codigo', 'uq_servicios_codigo');
            $table->index(['tipo', 'estado'], 'idx_servicios_tipo_estado');
            $table->index(['ciudad_origen_id', 'ciudad_destino_id'], 'idx_servicios_origen_destino');
            $table->index('ciudad_destino_id', 'idx_servicios_destino');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};
