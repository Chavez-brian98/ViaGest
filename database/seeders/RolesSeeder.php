<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->updateOrInsert(
            ['nombre' => 'administrador'],
            ['descripcion' => 'Acceso total al sistema', 'alcance' => 'todas_sucursales', 'estado' => 'activo'],
        );

        DB::table('roles')->updateOrInsert(
            ['nombre' => 'vendedor'],
            ['descripcion' => 'Prospectos, clientes, cotizaciones y ventas', 'alcance' => 'propia_sucursal', 'estado' => 'activo'],
        );

        DB::table('roles')->updateOrInsert(
            ['nombre' => 'cajero'],
            ['descripcion' => 'Cobros, pagos y caja', 'alcance' => 'propia_sucursal', 'estado' => 'activo'],
        );

        DB::table('roles')->updateOrInsert(
            ['nombre' => 'recursos_humanos'],
            ['descripcion' => 'Empleados y sucursales', 'alcance' => 'todas_sucursales', 'estado' => 'activo'],
        );

        DB::table('roles')->updateOrInsert(
            ['nombre' => 'operaciones'],
            ['descripcion' => 'Proveedores, cupos y documentos de viaje', 'alcance' => 'propia_sucursal', 'estado' => 'activo'],
        );
    }
}
