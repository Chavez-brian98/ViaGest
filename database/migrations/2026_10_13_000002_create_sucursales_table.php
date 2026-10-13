<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20);
            $table->string('nombre', 100);
            $table->enum('tipo', ['matriz', 'filial'])->default('filial');
            $table->foreignId('ciudad_id')->constrained('ciudades');
            $table->string('direccion', 255)->nullable();
            $table->string('telefono', 25)->nullable();
            $table->enum('estado', ['activa', 'inactiva'])->default('activa');
            $table->unsignedTinyInteger('matriz_unica')->nullable()->virtualAs("CASE WHEN tipo = 'matriz' THEN 1 ELSE NULL END");
            $table->timestamps();

            $table->unique('codigo', 'uq_sucursales_codigo');
            $table->unique('nombre', 'uq_sucursales_nombre');
            $table->unique('matriz_unica', 'uq_sucursales_matriz_unica');
            $table->index('estado', 'idx_sucursales_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sucursales');
    }
};
