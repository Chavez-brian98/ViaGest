<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmpleadosDemoSeeder extends Seeder
{
    /**
     * Crea al menos un empleado por cada rol del sistema.
     * Si AdminUserSeeder ya creó al administrador, ese rol se omite.
     */
    public function run(): void
    {
        $sucursalId = $this->obtenerSucursalId();

        $empleados = [
            'administrador' => [
                'codigo' => 'ADM001',
                'nombres' => 'Administrador',
                'apellidos' => 'Sistema',
                'tipo_documento' => 'dui',
                'numero_documento' => '00000000-0',
                'telefono' => '2222-0000',
                'cargo' => 'Gerente general',
                'email' => 'admin@viagest.com',
                'salario_base' => 1500.00,
                'fecha_ingreso' => now()->subYears(3)->toDateString(),
            ],
            'vendedor' => [
                'codigo' => 'EMP-0001',
                'nombres' => 'Ana',
                'apellidos' => 'Martínez',
                'tipo_documento' => 'dui',
                'numero_documento' => '10000001-1',
                'telefono' => '7000-0001',
                'cargo' => 'Asesora de ventas',
                'email' => 'vendedor@viagest.com',
                'salario_base' => 650.00,
                'fecha_ingreso' => now()->subMonths(18)->toDateString(),
            ],
            'cajero' => [
                'codigo' => 'EMP-0002',
                'nombres' => 'Carlos',
                'apellidos' => 'López',
                'tipo_documento' => 'dui',
                'numero_documento' => '10000002-2',
                'telefono' => '7000-0002',
                'cargo' => 'Cajero',
                'email' => 'cajero@viagest.com',
                'salario_base' => 550.00,
                'fecha_ingreso' => now()->subMonths(12)->toDateString(),
            ],
            'recursos_humanos' => [
                'codigo' => 'EMP-0003',
                'nombres' => 'María',
                'apellidos' => 'Hernández',
                'tipo_documento' => 'dui',
                'numero_documento' => '10000003-3',
                'telefono' => '7000-0003',
                'cargo' => 'Analista de recursos humanos',
                'email' => 'rrhh@viagest.com',
                'salario_base' => 900.00,
                'fecha_ingreso' => now()->subMonths(24)->toDateString(),
            ],
            'operaciones' => [
                'codigo' => 'EMP-0004',
                'nombres' => 'José',
                'apellidos' => 'Ramírez',
                'tipo_documento' => 'dui',
                'numero_documento' => '10000004-4',
                'telefono' => '7000-0004',
                'cargo' => 'Coordinador de operaciones',
                'email' => 'operaciones@viagest.com',
                'salario_base' => 750.00,
                'fecha_ingreso' => now()->subMonths(9)->toDateString(),
            ],
        ];

        foreach ($empleados as $nombreRol => $datos) {
            $rolId = DB::table('roles')->where('nombre', $nombreRol)->value('id');

            if ($rolId === null) {
                continue;
            }

            if (DB::table('empleados')->where('rol_id', $rolId)->exists()) {
                continue;
            }

            if (DB::table('empleados')->where('email', $datos['email'])->exists()) {
                continue;
            }

            DB::table('empleados')->insert([
                'rol_id' => $rolId,
                'sucursal_id' => $sucursalId,
                'codigo' => $datos['codigo'],
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'],
                'tipo_documento' => $datos['tipo_documento'],
                'numero_documento' => $datos['numero_documento'],
                'telefono' => $datos['telefono'],
                'cargo' => $datos['cargo'],
                'tipo_contrato' => 'indefinido',
                'fecha_ingreso' => $datos['fecha_ingreso'],
                'salario_base' => $datos['salario_base'],
                'estado' => 'activo',
                'email' => $datos['email'],
                'password' => Hash::make('1234'),
                'acceso' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function obtenerSucursalId(): int
    {
        $sucursalId = DB::table('sucursales')->orderBy('id')->value('id');

        if ($sucursalId !== null) {
            return (int) $sucursalId;
        }

        $ciudadId = DB::table('ciudades')->orderBy('id')->value('id');

        if ($ciudadId === null) {
            $ciudadId = DB::table('ciudades')->insertGetId([
                'pais_codigo' => 'SV',
                'nombre' => 'San Salvador',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return (int) DB::table('sucursales')->insertGetId([
            'codigo' => 'SUC-01',
            'nombre' => 'Sucursal Central',
            'tipo' => 'matriz',
            'ciudad_id' => $ciudadId,
            'direccion' => 'San Salvador',
            'telefono' => '2222-0000',
            'estado' => 'activa',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
