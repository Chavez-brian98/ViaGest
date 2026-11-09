<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ACCIONES = ['ver', 'crear', 'editar', 'eliminar'];

    private const ACCIONES_ELIMINADAS = ['exportar', 'aprobar'];

    public function up(): void
    {
        DB::table('rol_permisos')->whereIn('accion', self::ACCIONES_ELIMINADAS)->delete();

        if (Schema::hasTable('empleado_permisos')) {
            DB::table('empleado_permisos')->whereIn('accion', self::ACCIONES_ELIMINADAS)->delete();
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('rol_permisos', function (Blueprint $table): void {
            $table->enum('accion', self::ACCIONES)->change();
        });

        if (Schema::hasTable('empleado_permisos')) {
            Schema::table('empleado_permisos', function (Blueprint $table): void {
                $table->enum('accion', self::ACCIONES)->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('rol_permisos', function (Blueprint $table): void {
            $table->enum('accion', [...self::ACCIONES, ...self::ACCIONES_ELIMINADAS])->change();
        });

        if (Schema::hasTable('empleado_permisos')) {
            Schema::table('empleado_permisos', function (Blueprint $table): void {
                $table->enum('accion', [...self::ACCIONES, ...self::ACCIONES_ELIMINADAS])->change();
            });
        }
    }
};
