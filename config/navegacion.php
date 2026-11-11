<?php

return [

    'grupos' => [
        [
            'titulo' => 'Principal',
            'items' => [
                ['label' => 'Dashboard', 'icono' => 'home', 'ruta' => 'dashboard'],
                ['label' => 'Ventas', 'icono' => 'ticket', 'ruta' => null],
                ['label' => 'Cotizaciones', 'icono' => 'document', 'ruta' => 'cotizaciones.index'],
            ],
        ],
        [
            'titulo' => 'Catalogo',
            'items' => [
                ['label' => 'Paquetes', 'icono' => 'cube', 'ruta' => 'paquetes.index'],
                ['label' => 'Servicios', 'icono' => 'globe', 'ruta' => 'servicios.index'],
                ['label' => 'Proveedores', 'icono' => 'building', 'ruta' => 'proveedores.index'],
                ['label' => 'Cupos', 'icono' => 'calendar', 'ruta' => 'cupos.index'],
            ],
        ],
        [
            'titulo' => 'Clientes',
            'items' => [
                ['label' => 'Clientes', 'icono' => 'users', 'ruta' => 'clientes.index'],
                ['label' => 'Prospectos', 'icono' => 'sparkles', 'ruta' => 'prospectos.index'],
            ],
        ],
        [
            'titulo' => 'Finanzas',
            'items' => [
                ['label' => 'Cobros', 'icono' => 'credit-card', 'ruta' => null],
                ['label' => 'Cuentas por pagar', 'icono' => 'receipt', 'ruta' => null],
                ['label' => 'Comisiones', 'icono' => 'wallet', 'ruta' => null],
            ],
        ],
        [
            'titulo' => 'Sistema',
            'items' => [
                ['label' => 'Reportes', 'icono' => 'chart', 'ruta' => null],
                ['label' => 'Empleados', 'icono' => 'user-group', 'ruta' => 'empleados.index'],
                ['label' => 'Roles y permisos', 'icono' => 'lock', 'ruta' => 'roles.index'],
                ['label' => 'Sucursales', 'icono' => 'map', 'ruta' => null],
                ['label' => 'Auditoría', 'icono' => 'shield', 'ruta' => 'auditoria.index'],
                ['label' => 'Configuración', 'icono' => 'cog', 'ruta' => 'configuracion.edit', 'permiso' => ['ajustes_generales', 'ver']],
            ],
        ],
    ],

];
