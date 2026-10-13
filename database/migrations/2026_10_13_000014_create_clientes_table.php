<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20);
            $table->enum('tipo', ['persona', 'empresa'])->default('persona');
            $table->string('nombres', 100)->nullable();
            $table->string('apellidos', 100)->nullable();
            $table->string('razon_social', 150)->nullable();
            $table->enum('tipo_documento', ['dui', 'nit', 'carnet_residente', 'otro'])->nullable();
            $table->string('numero_documento', 30)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->char('nacionalidad_pais', 2)->nullable()->comment('ISO 3166-1 alfa-2');
            $table->string('email', 150)->nullable();
            $table->string('telefono', 25)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('pasaporte_numero', 25)->nullable();
            $table->char('pasaporte_pais', 2)->nullable()->comment('Pais emisor, ISO 3166-1 alfa-2');
            $table->date('pasaporte_vence')->nullable();
            $table->text('preferencias_viaje')->nullable();
            $table->foreignId('sucursal_id')->constrained('sucursales')->comment('Sucursal donde se registro');
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();

            $table->unique('codigo', 'uq_clientes_codigo');
            $table->unique(['tipo_documento', 'numero_documento'], 'uq_clientes_documento');
            $table->unique(['pasaporte_pais', 'pasaporte_numero'], 'uq_clientes_pasaporte');
            $table->index('email', 'idx_clientes_email');
            $table->index('telefono', 'idx_clientes_telefono');
            $table->index(['apellidos', 'nombres'], 'idx_clientes_apellidos_nombres');
            $table->index('razon_social', 'idx_clientes_razon_social');
            $table->index(['sucursal_id', 'estado'], 'idx_clientes_sucursal_estado');
            $table->index('pasaporte_vence', 'idx_clientes_pasaporte_vence');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
