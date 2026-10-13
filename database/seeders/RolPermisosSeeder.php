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

    private const ACCIONES = ['ver', 'crear', 'editar', 'eliminar', 'exportar', 'aprobar'];

    public function run(): void
    {
        $rolId = DB::table('roles')->where('nombre', 'administrador')->value('id');

        if ($rolId === null) {
            return;
        }

        $rows = [];
        foreach (self::MODULOS as $modulo) {
            foreach (self::ACCIONES as $accion) {
                $rows[] = ['rol_id' => $rolId, 'modulo' => $modulo, 'accion' => $accion];
            }
        }

        DB::table('rol_permisos')->insertOrIgnore($rows);
    }
}
