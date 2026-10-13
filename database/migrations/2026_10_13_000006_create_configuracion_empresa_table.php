<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_empresa', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->default(1);
            $table->string('nombre_comercial', 150);
            $table->string('razon_social', 150)->nullable();
            $table->string('documento_fiscal', 30)->nullable();
            $table->string('email_contacto', 150)->nullable();
            $table->string('telefono', 25)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('logo_ruta', 255)->nullable();
            $table->json('imagenes_login')->nullable()->comment('Lista de rutas de las imagenes de inicio de sesion');
            $table->string('zona_horaria', 64)->default('America/El_Salvador');
            $table->char('moneda_base', 3)->default('USD')->comment('ISO 4217');
            $table->mediumText('terminos_condiciones')->nullable()->comment('Texto del contrato de terminos y condiciones');
            $table->timestamps();

            $table->primary('id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_empresa');
    }
};
