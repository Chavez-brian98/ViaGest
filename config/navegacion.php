<?php

return [

    'grupos' => [
        [
            'titulo' => 'Principal',
            'items' => [
                ['label' => 'Dashboard', 'icono' => 'home', 'ruta' => 'dashboard'],
                ['label' => 'Ventas', 'icono' => 'ticket', 'ruta' => null],
                ['label' => 'Cotizaciones', 'icono' => 'document', 'ruta' => null],
            ],
        ],
        [
            'titulo' => 'Catalogo',
            'items' => [
                ['label' => 'Paquetes', 'icono' => 'cube', 'ruta' => null],
                ['label' => 'Servicios', 'icono' => 'globe', 'ruta' => null],
                ['label' => 'Proveedores', 'icono' => 'building', 'ruta' => null],
                ['label' => 'Cupos', 'icono' => 'calendar', 'ruta' => null],
            ],
        ],
        [
            'titulo' => 'Clientes',
            'items' => [
                ['label' => 'Clientes', 'icono' => 'users', 'ruta' => null],
                ['label' => 'Prospectos', 'icono' => 'sparkles', 'ruta' => null],
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
                ['label' => 'Empleados', 'icono' => 'user-group', 'ruta' => null],
                ['label' => 'Sucursales', 'icono' => 'map', 'ruta' => null],
                ['label' => 'Configuracion', 'icono' => 'cog', 'ruta' => null],
            ],
        ],
    ],

];
