<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20);
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->enum('tipo_documento', ['dui', 'pasaporte', 'carnet_residente', 'otro'])->default('dui');
            $table->string('numero_documento', 30);
            $table->string('telefono', 25)->nullable();
            $table->string('cargo', 80);
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->enum('tipo_contrato', ['indefinido', 'plazo_fijo', 'servicios_profesionales'])->default('indefinido');
            $table->date('fecha_ingreso');
            $table->date('fecha_salida')->nullable();
            $table->decimal('salario_base', 12, 2)->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->string('email', 150)->comment('Contacto y usuario de acceso');
            $table->string('password', 255)->nullable()->comment('Hash; NULL si no tiene acceso al sistema');
            $table->foreignId('rol_id')->nullable()->constrained('roles')->comment('NULL si no tiene acceso al sistema');
            $table->enum('acceso', ['sin_acceso', 'activo', 'bloqueado'])->default('sin_acceso');
            $table->timestamp('ultimo_acceso_at')->nullable();
            $table->timestamps();

            $table->unique('codigo', 'uq_empleados_codigo');
            $table->unique(['tipo_documento', 'numero_documento'], 'uq_empleados_documento');
            $table->unique('email', 'uq_empleados_email');
            $table->index(['sucursal_id', 'estado'], 'idx_empleados_sucursal_estado');
            $table->index(['apellidos', 'nombres'], 'idx_empleados_apellidos_nombres');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
