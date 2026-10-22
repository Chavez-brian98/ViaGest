<?php

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatosDemoDashboardSeeder extends Seeder
{
    public function run(): void
    {
        $now = CarbonImmutable::now();

        if (DB::table('ventas')->count() > 0) {
            return;
        }

        $sucursalId = DB::table('sucursales')->orderBy('id')->value('id');
        $vendedorId = DB::table('empleados')->where('acceso', 'activo')->orderBy('id')->value('id') ?? DB::table('empleados')->orderBy('id')->value('id');
        if (! $sucursalId || ! $vendedorId) {
            return;
        }

        $clienteId = DB::table('clientes')->orderBy('id')->value('id');
        if (! $clienteId) {
            $clienteId = DB::table('clientes')->insertGetId([
                'codigo' => 'CLI-DEMO-001',
                'tipo' => 'persona',
                'nombres' => 'Cliente',
                'apellidos' => 'Demo',
                'tipo_documento' => 'dui',
                'numero_documento' => '00000000-0',
                'email' => 'demo@viagest.test',
                'telefono' => '00000000',
                'direccion' => 'San Salvador',
                'sucursal_id' => $sucursalId,
                'estado' => 'activo',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $proveedorId = DB::table('proveedores')->orderBy('id')->value('id');
        if (! $proveedorId) {
            $proveedorId = DB::table('proveedores')->insertGetId([
                'codigo' => 'PROV-DEMO-001',
                'tipo' => 'operador',
                'nombre' => 'Proveedor Demo',
                'razon_social' => 'Proveedor Demo SA',
                'documento_fiscal' => '0000-000000-000-0',
                'pais_codigo' => 'SV',
                'contacto_nombre' => 'Contacto Demo',
                'email' => 'proveedor@viagest.test',
                'telefono' => '00000000',
                'dias_credito' => 30,
                'estado' => 'activo',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $servicioId = DB::table('servicios')->orderBy('id')->value('id');
        if (! $servicioId) {
            $servicioId = DB::table('servicios')->insertGetId([
                'codigo' => 'SRV-DEMO-001',
                'tipo' => 'hospedaje',
                'nombre' => 'Hotel Demo',
                'proveedor_id' => $proveedorId,
                'unidad_cobro' => 'por_persona',
                'costo_neto' => 800,
                'vigente_desde' => $now->toDateString(),
                'estado' => 'activo',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $cupoId = DB::table('cupos')->orderBy('id')->value('id');
        if (! $cupoId) {
            $cupoId = DB::table('cupos')->insertGetId([
                'servicio_id' => $servicioId,
                'tipo' => 'compra_adelantada',
                'fecha_inicio' => $now->copy()->addDays(15)->toDateString(),
                'fecha_fin' => $now->copy()->addDays(22)->toDateString(),
                'cantidad_total' => 50,
                'estado' => 'activo',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::transaction(function () use ($now, $sucursalId, $vendedorId, $clienteId, $servicioId, $cupoId, $proveedorId) {
            for ($i = 0; $i < 6; $i++) {
                $fechaVenta = $now->copy()->subMonths(rand(0, 11))->subDays(rand(0, 15));
                $codigo = 'VD' . str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT);

                $ventaId = DB::table('ventas')->insertGetId([
                    'codigo' => $codigo,
                    'cliente_id' => $clienteId,
                    'vendedor_id' => $vendedorId,
                    'sucursal_id' => $sucursalId,
                    'tipo' => 'paquete',
                    'fecha_venta' => $fechaVenta->toDateString(),
                    'fecha_viaje_inicio' => $fechaVenta->copy()->addDays(15)->toDateString(),
                    'fecha_viaje_fin' => $fechaVenta->copy()->addDays(22)->toDateString(),
                    'descuento_monto' => 0,
                    'estado' => ['confirmada', 'reservada', 'en_curso'][rand(0, 2)],
                    'created_at' => $fechaVenta,
                    'updated_at' => $fechaVenta,
                ]);

                DB::table('venta_servicios')->insert([
                    'venta_id' => $ventaId,
                    'servicio_id' => $servicioId,
                    'cupo_id' => $cupoId,
                    'cantidad' => 2,
                    'costo_neto_unitario' => 800,
                    'precio_unitario' => 850,
                    'fecha_inicio' => $fechaVenta->copy()->addDays(15)->toDateString(),
                    'fecha_fin' => $fechaVenta->copy()->addDays(22)->toDateString(),
                    'estado' => 'confirmado',
                ]);

                DB::table('cuotas_cobro')->insert([
                    'venta_id' => $ventaId,
                    'numero' => 1,
                    'fecha_vencimiento' => $fechaVenta->copy()->addDays(7)->toDateString(),
                    'monto' => 850,
                    'estado' => 'activa',
                    'created_at' => $fechaVenta,
                    'updated_at' => $fechaVenta,
                ]);

                DB::table('cuotas_cobro')->insert([
                    'venta_id' => $ventaId,
                    'numero' => 2,
                    'fecha_vencimiento' => $fechaVenta->copy()->addDays(30)->toDateString(),
                    'monto' => 850,
                    'estado' => 'activa',
                    'created_at' => $fechaVenta,
                    'updated_at' => $fechaVenta,
                ]);
            }

            if (! DB::table('cuentas_por_pagar')->where('numero_documento', 'FACT-DEMO-001')->exists()) {
                DB::table('cuentas_por_pagar')->insert([
                    'codigo' => 'CP-001',
                    'tipo' => 'proveedor',
                    'proveedor_id' => $proveedorId,
                    'categoria_gasto' => 'otro',
                    'sucursal_id' => $sucursalId,
                    'descripcion' => 'Factura demo dashboard',
                    'numero_documento' => 'FACT-DEMO-001',
                    'fecha_emision' => $now->copy()->subDays(10)->toDateString(),
                    'fecha_vencimiento' => $now->copy()->addDays(5)->toDateString(),
                    'fecha_programada_pago' => $now->copy()->addDays(5)->toDateString(),
                    'monto' => 1250,
                    'estado' => 'pendiente',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }
}
