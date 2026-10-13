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
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->comment('Destinatario');
            $table->foreignId('regla_id')->nullable()->constrained('notificacion_reglas');
            $table->enum('evento', self::EVENTOS);
            $table->enum('canal', ['interna', 'correo'])->default('interna');
            $table->string('titulo', 150);
            $table->string('mensaje', 500);
            $table->enum('referencia_tipo', ['prospecto', 'presupuesto', 'venta', 'cuota_cobro', 'cuenta_por_pagar', 'venta_servicio', 'cupo'])->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->timestamp('leida_at')->nullable();
            $table->timestamp('enviada_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['empleado_id', 'leida_at', 'created_at'], 'idx_notificaciones_empleado_leida');
            $table->index(['referencia_tipo', 'referencia_id'], 'idx_notificaciones_referencia');
            $table->index('regla_id', 'idx_notificaciones_regla_id');
            $table->index(['evento', 'created_at'], 'idx_notificaciones_evento_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
