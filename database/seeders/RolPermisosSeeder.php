<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolPermisosSeeder extends Seeder
{
    private const MODULOS = [
        'dashboard_resumen', 'dashboard_alertas', 'prospectos', 'clientes', 'ventas_reservas',
        'paquetes', 'inventario_cupos', 'vuelos', 'hospedajes', 'transporte', 'guias',
        'gestion_documental', 'cuentas_por_cobrar', 'cuentas_por_pagar', 'comisiones',
        'reportes', 'sucursales', 'empleados', 'roles_permisos', 'ajustes_generales',
        'notificaciones',
    ];

    private const ACCIONES = ['ver', 'crear', 'editar', 'eliminar'];

    /**
     * Permisos por defecto de cada rol.
     * El comodín '*' otorga todos los módulos y acciones.
     */
    private const PERMISOS_POR_ROL = [
        'administrador' => '*',
        'vendedor' => [
            'dashboard_resumen' => ['ver'],
            'dashboard_alertas' => ['ver'],
            'prospectos' => ['ver', 'crear', 'editar'],
            'clientes' => ['ver', 'crear', 'editar'],
            'ventas_reservas' => ['ver', 'crear', 'editar'],
            'paquetes' => ['ver'],
            'inventario_cupos' => ['ver'],
            'cuentas_por_cobrar' => ['ver'],
            'comisiones' => ['ver'],
            'reportes' => ['ver'],
            'notificaciones' => ['ver'],
        ],
        'cajero' => [
            'dashboard_resumen' => ['ver'],
            'dashboard_alertas' => ['ver'],
            'clientes' => ['ver'],
            'ventas_reservas' => ['ver'],
            'cuentas_por_cobrar' => ['ver', 'crear', 'editar'],
            'cuentas_por_pagar' => ['ver', 'crear', 'editar'],
            'comisiones' => ['ver'],
            'reportes' => ['ver'],
            'notificaciones' => ['ver'],
        ],
        'recursos_humanos' => [
            'dashboard_resumen' => ['ver'],
            'empleados' => ['ver', 'crear', 'editar', 'eliminar'],
            'sucursales' => ['ver', 'crear', 'editar', 'eliminar'],
            'roles_permisos' => ['ver'],
            'reportes' => ['ver'],
            'notificaciones' => ['ver'],
        ],
        'operaciones' => [
            'dashboard_resumen' => ['ver'],
            'dashboard_alertas' => ['ver'],
            'paquetes' => ['ver', 'crear', 'editar'],
            'inventario_cupos' => ['ver', 'crear', 'editar'],
            'vuelos' => ['ver', 'crear', 'editar'],
            'hospedajes' => ['ver', 'crear', 'editar'],
            'transporte' => ['ver', 'crear', 'editar'],
            'guias' => ['ver', 'crear', 'editar'],
            'gestion_documental' => ['ver', 'crear', 'editar', 'eliminar'],
            'reportes' => ['ver'],
            'notificaciones' => ['ver'],
        ],
    ];

    public function run(): void
    {
        foreach (self::PERMISOS_POR_ROL as $nombreRol => $permisos) {
            $rolId = DB::table('roles')->where('nombre', $nombreRol)->value('id');

            if ($rolId === null) {
                continue;
            }

            $rows = [];

            if ($permisos === '*') {
                foreach (self::MODULOS as $modulo) {
                    foreach (self::ACCIONES as $accion) {
                        $rows[] = ['rol_id' => $rolId, 'modulo' => $modulo, 'accion' => $accion];
                    }
                }
            } else {
                foreach ($permisos as $modulo => $acciones) {
                    if (! in_array($modulo, self::MODULOS, true)) {
                        continue;
                    }

                    foreach ($acciones as $accion) {
                        if (in_array($accion, self::ACCIONES, true)) {
                            $rows[] = ['rol_id' => $rolId, 'modulo' => $modulo, 'accion' => $accion];
                        }
                    }
                }
            }

            if (! empty($rows)) {
                DB::table('rol_permisos')->insertOrIgnore($rows);
            }
        }
    }
}
