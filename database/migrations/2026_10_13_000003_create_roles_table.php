<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50);
            $table->string('descripcion', 255)->nullable();
            $table->enum('alcance', ['propia_sucursal', 'todas_sucursales'])->default('propia_sucursal');
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();

            $table->unique('nombre', 'uq_roles_nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
