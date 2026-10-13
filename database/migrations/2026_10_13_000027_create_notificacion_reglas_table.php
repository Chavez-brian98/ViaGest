<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const EVENTOS = [
        'pago_cliente_por_vencer', 'pago_cliente_vencido', 'pago_proveedor_por_vencer',
        'vuelo_proximo', 'presupuesto_por_caducar', 'seguimiento_pendiente',
        'cupo_por_liberar', 'venta_confirmada', 'venta_cancelada',
    ];

    public function up(): void
    {
        Schema::create('notificacion_reglas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->enum('evento', self::EVENTOS);
            $table->enum('canal', ['interna', 'correo', 'ambos'])->default('interna');
            $table->unsignedSmallInteger('dias_anticipacion')->nullable();
            $table->enum('destinatario', ['responsable', 'rol'])->default('responsable');
            $table->foreignId('rol_id')->nullable()->constrained('roles');
            $table->enum('estado', ['activa', 'inactiva'])->default('activa');
            $table->timestamps();

            $table->unique('nombre', 'uq_notificacion_reglas_nombre');
            $table->index(['evento', 'estado'], 'idx_notificacion_reglas_evento_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacion_reglas');
    }
};
