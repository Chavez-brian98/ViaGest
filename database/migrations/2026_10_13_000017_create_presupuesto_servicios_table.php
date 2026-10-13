<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuesto_servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presupuesto_id')->constrained('presupuestos')->cascadeOnDelete();
            $table->foreignId('servicio_id')->constrained('servicios');
            $table->decimal('cantidad', 8, 2)->default(1);
            $table->decimal('costo_neto_unitario', 12, 2);
            $table->decimal('precio_unitario', 12, 2);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuesto_servicios');
    }
};
