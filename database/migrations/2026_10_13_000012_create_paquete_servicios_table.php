<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paquete_servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paquete_id')->constrained('paquetes')->cascadeOnDelete();
            $table->foreignId('servicio_id')->constrained('servicios');
            $table->unsignedSmallInteger('dia_inicio')->default(1);
            $table->decimal('cantidad', 8, 2)->default(1);

            $table->unique(['paquete_id', 'servicio_id', 'dia_inicio'], 'uq_paquete_servicios_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paquete_servicios');
    }
};
