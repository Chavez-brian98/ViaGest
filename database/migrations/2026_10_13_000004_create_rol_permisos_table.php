<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rol_permisos', function (Blueprint $table) {
            $table->foreignId('rol_id')->constrained('roles')->cascadeOnDelete();
            $table->enum('modulo', [
                'dashboard_resumen', 'dashboard_alertas', 'prospectos', 'clientes', 'ventas_reservas',
                'paquetes', 'inventario_cupos', 'vuelos', 'hospedajes', 'transporte', 'guias',
                'gestion_documental', 'cuentas_por_cobrar', 'cuentas_por_pagar', 'comisiones',
                'reportes', 'sucursales', 'empleados', 'roles_permisos', 'ajustes_generales',
                'notificaciones',
            ]);
            $table->enum('accion', ['ver', 'crear', 'editar', 'eliminar', 'exportar', 'aprobar']);

            $table->primary(['rol_id', 'modulo', 'accion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rol_permisos');
    }
};
