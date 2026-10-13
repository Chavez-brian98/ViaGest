<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospectos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20);
            $table->string('nombres', 100);
            $table->string('apellidos', 100)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('telefono', 25)->nullable();
            $table->char('pais_destino', 2)->nullable()->comment('Destino de interes, ISO 3166-1 alfa-2');
            $table->date('fecha_viaje_estimada')->nullable();
            $table->unsignedSmallInteger('numero_viajeros')->nullable();
            $table->enum('origen', ['referido', 'redes_sociales', 'sitio_web', 'visita_local', 'telefono', 'evento', 'otro'])->default('otro');
            $table->enum('estado', ['nuevo', 'contactado', 'cotizado', 'convertido', 'perdido'])->default('nuevo');
            $table->enum('motivo_perdida', ['precio', 'sin_respuesta', 'eligio_competencia', 'cambio_de_planes', 'otro'])->nullable();
            $table->date('proximo_seguimiento')->nullable()->comment('Recordatorio de seguimiento');
            $table->foreignId('vendedor_id')->constrained('empleados');
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->unique()->comment('Se llena al convertirse en cliente');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique('codigo', 'uq_prospectos_codigo');
            $table->index(['vendedor_id', 'estado'], 'idx_prospectos_vendedor_estado');
            $table->index(['estado', 'proximo_seguimiento'], 'idx_prospectos_estado_seguimiento');
            $table->index(['sucursal_id', 'estado'], 'idx_prospectos_sucursal_estado');
            $table->index('email', 'idx_prospectos_email');
            $table->index('telefono', 'idx_prospectos_telefono');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospectos');
    }
};
