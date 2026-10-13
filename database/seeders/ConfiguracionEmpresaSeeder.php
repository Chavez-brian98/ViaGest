<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfiguracionEmpresaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('configuracion_empresa')->updateOrInsert(
            ['id' => 1],
            ['nombre_comercial' => 'Mi Agencia de Viajes'],
        );
    }
}
