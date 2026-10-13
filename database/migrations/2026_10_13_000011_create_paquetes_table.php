<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paquetes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30);
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable()->comment('Resumen del itinerario base');
            $table->enum('tipo', ['nacional', 'internacional'])->default('nacional');
            $table->foreignId('ciudad_destino_id')->nullable()->constrained('ciudades');
            $table->unsignedSmallInteger('duracion_dias');
            $table->unsignedSmallInteger('duracion_noches');
            $table->enum('margen_tipo', ['porcentaje', 'monto_fijo'])->default('porcentaje');
            $table->decimal('margen_valor', 10, 2)->default(0);
            $table->enum('estado', ['borrador', 'activo', 'inactivo'])->default('borrador');
            $table->timestamps();

            $table->unique('codigo', 'uq_paquetes_codigo');
            $table->index(['estado', 'tipo'], 'idx_paquetes_estado_tipo');
            $table->index('nombre', 'idx_paquetes_nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paquetes');
    }
};
