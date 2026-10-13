<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora_auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->nullable()->constrained('empleados')->comment('Quien realizo la accion');
            $table->enum('accion', ['crear', 'actualizar', 'eliminar', 'inicio_sesion', 'cierre_sesion', 'exportar']);
            $table->string('tabla', 64)->nullable();
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tabla', 'registro_id'], 'idx_bitacora_auditoria_tabla_registro');
            $table->index(['empleado_id', 'created_at'], 'idx_bitacora_auditoria_empleado_fecha');
            $table->index('created_at', 'idx_bitacora_auditoria_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_auditoria');
    }
};
