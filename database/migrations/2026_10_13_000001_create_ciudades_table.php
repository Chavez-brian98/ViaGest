<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ciudades', function (Blueprint $table) {
            $table->id();
            $table->char('pais_codigo', 2)->comment('ISO 3166-1 alfa-2');
            $table->string('nombre', 100);
            $table->timestamps();

            $table->unique(['pais_codigo', 'nombre'], 'uq_ciudades_pais_nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ciudades');
    }
};
