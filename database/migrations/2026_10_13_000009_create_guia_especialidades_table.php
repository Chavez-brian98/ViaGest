<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guia_especialidades', function (Blueprint $table) {
            $table->foreignId('guia_id')->constrained('proveedores')->cascadeOnDelete()->comment('Proveedor de tipo guia');
            $table->enum('especialidad', ['historia', 'aventura', 'naturaleza', 'gastronomia', 'cultura', 'playa', 'fotografia', 'general']);

            $table->primary(['guia_id', 'especialidad']);
            $table->index('especialidad', 'idx_guia_especialidades_especialidad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guia_especialidades');
    }
};
