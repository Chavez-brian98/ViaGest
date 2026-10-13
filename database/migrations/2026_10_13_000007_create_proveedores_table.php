<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20);
            $table->enum('tipo', ['aerolinea', 'hospedaje', 'transporte', 'guia', 'operador', 'otro']);
            $table->string('nombre', 150)->comment('Para guias y conductores: nombre completo');
            $table->string('razon_social', 150)->nullable();
            $table->string('documento_fiscal', 30)->nullable();
            $table->char('pais_codigo', 2)->comment('ISO 3166-1 alfa-2');
            $table->string('contacto_nombre', 120)->nullable()->comment('Contacto de reservas');
            $table->string('email', 150)->nullable();
            $table->string('telefono', 25)->nullable();
            $table->unsignedSmallInteger('dias_credito')->default(0);
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->text('notas')->nullable()->comment('Politicas (p. ej. equipaje de una aerolinea), observaciones');
            $table->timestamps();

            $table->unique('codigo', 'uq_proveedores_codigo');
            $table->unique('documento_fiscal', 'uq_proveedores_documento_fiscal');
            $table->index(['tipo', 'estado'], 'idx_proveedores_tipo_estado');
            $table->index('nombre', 'idx_proveedores_nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
