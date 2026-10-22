<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('empleados')->where('email', 'admin@viagest.com')->exists()) {
            return;
        }

        $suc = DB::table('sucursales')->value('id');
        $ciudad = DB::table('ciudades')->value('id');
        $rolId = DB::table('roles')->value('id');

        if (! $ciudad) {
            $ciudad = DB::table('ciudades')->insertGetId([
                'pais_codigo' => 'SV',
                'nombre' => 'San Salvador',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! $suc) {
            $suc = DB::table('sucursales')->insertGetId([
                'codigo' => 'SUC-01',
                'nombre' => 'Sucursal Central',
                'ciudad_id' => $ciudad,
                'direccion' => 'San Salvador',
                'telefono' => '2222-0000',
                'estado' => 'activa',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! $rolId) {
            $rolId = DB::table('roles')->insertGetId([
                'nombre' => 'Administrador',
                'descripcion' => 'Administrador del sistema',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('empleados')->insert([
            'rol_id' => $rolId,
            'codigo' => 'ADM001',
            'nombres' => 'Administrador',
            'apellidos' => 'Sistema',
            'numero_documento' => '00000000-0',
            'cargo' => 'Gerente',
            'sucursal_id' => $suc,
            'email' => 'admin@viagest.com',
            'password' => Hash::make('1234'),
            'acceso' => 'activo',
            'estado' => 'activo',
            'fecha_ingreso' => date('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
