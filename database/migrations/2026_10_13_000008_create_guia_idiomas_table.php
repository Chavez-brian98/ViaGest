<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guia_idiomas', function (Blueprint $table) {
            $table->foreignId('guia_id')->constrained('proveedores')->cascadeOnDelete()->comment('Proveedor de tipo guia');
            $table->enum('idioma', ['es', 'en', 'fr', 'de', 'pt', 'it', 'zh', 'ja'])->comment('ISO 639-1');
            $table->enum('nivel', ['basico', 'intermedio', 'avanzado', 'nativo'])->default('intermedio');

            $table->primary(['guia_id', 'idioma']);
            $table->index(['idioma', 'nivel'], 'idx_guia_idiomas_idioma_nivel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guia_idiomas');
    }
};
