<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion_empresa', function (Blueprint $table) {
            $table->string('color_primary', 7)->default('#0b4f6c');
            $table->string('color_secondary', 7)->default('#1ca3aa');
            $table->string('color_accent', 7)->default('#f28f3b');
            $table->string('color_background', 7)->default('#f5f8fa');
            $table->string('color_surface', 7)->default('#ffffff');
            $table->string('color_ink', 7)->default('#1f2933');
        });
    }

    public function down(): void
    {
        Schema::table('configuracion_empresa', function (Blueprint $table) {
            $table->dropColumn([
                'color_primary',
                'color_secondary',
                'color_accent',
                'color_background',
                'color_surface',
                'color_ink',
            ]);
        });
    }
};
