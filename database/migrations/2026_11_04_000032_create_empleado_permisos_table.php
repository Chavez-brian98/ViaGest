<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleado_permisos', function (Blueprint $table) {
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->enum('modulo', [
                'dashboard_resumen', 'dashboard_alertas', 'prospectos', 'clientes', 'ventas_reservas',
                'paquetes', 'inventario_cupos', 'vuelos', 'hospedajes', 'transporte', 'guias',
                'gestion_documental', 'cuentas_por_cobrar', 'cuentas_por_pagar', 'comisiones',
                'reportes', 'sucursales', 'empleados', 'roles_permisos', 'ajustes_generales',
                'notificaciones',
            ]);
            $table->enum('accion', ['ver', 'crear', 'editar', 'eliminar', 'exportar', 'aprobar']);
            $table->enum('efecto', ['permitir', 'denegar'])->default('permitir');

            $table->primary(['empleado_id', 'modulo', 'accion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleado_permisos');
    }
};
