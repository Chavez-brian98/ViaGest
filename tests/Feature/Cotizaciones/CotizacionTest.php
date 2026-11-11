<?php

namespace Tests\Feature\Cotizaciones;

use App\Models\Empleado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CotizacionTest extends TestCase
{
    use RefreshDatabase;

    private Empleado $empleado;

    private int $sucursalId;

    private int $clienteId;

    private int $servicioId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $ciudadId = DB::table('ciudades')->insertGetId([
            'pais_codigo' => 'SV',
            'nombre' => 'San Salvador',
        ]);

        $this->sucursalId = DB::table('sucursales')->insertGetId([
            'codigo' => 'MAT-001',
            'nombre' => 'Sucursal Central',
            'tipo' => 'matriz',
            'ciudad_id' => $ciudadId,
        ]);

        $rolId = DB::table('roles')->insertGetId([
            'nombre' => 'administrador',
            'alcance' => 'todas_sucursales',
        ]);

        $this->empleado = Empleado::create([
            'codigo' => 'EMP-001',
            'nombres' => 'Carlos',
            'apellidos' => 'Vendedor',
            'numero_documento' => '00000001-1',
            'cargo' => 'Asesor de Viajes',
            'sucursal_id' => $this->sucursalId,
            'fecha_ingreso' => now()->toDateString(),
            'email' => 'carlos.vendedor@example.com',
            'password' => 'secret-password',
            'rol_id' => $rolId,
            'acceso' => 'activo',
        ]);

        $this->clienteId = DB::table('clientes')->insertGetId([
            'codigo' => 'CLI-0001',
            'tipo' => 'persona',
            'nombres' => 'Laura',
            'apellidos' => 'Gómez',
            'email' => 'laura@example.com',
            'sucursal_id' => $this->sucursalId,
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $provId = DB::table('proveedores')->insertGetId([
            'codigo' => 'PROV-0001',
            'tipo' => 'hospedaje',
            'nombre' => 'Hotel Paraíso',
            'pais_codigo' => 'SV',
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->servicioId = DB::table('servicios')->insertGetId([
            'codigo' => 'SRV-0001',
            'proveedor_id' => $provId,
            'tipo' => 'hospedaje',
            'nombre' => 'Noche en Suite',
            'costo_neto' => 50.00,
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('cotizaciones.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_list_quotations(): void
    {
        $this->crearCotizacion(['codigo' => 'COT-0001']);
        $this->crearCotizacion(['codigo' => 'COT-0002']);

        $response = $this->actingAs($this->empleado)->get(route('cotizaciones.index'));

        $response->assertOk();
        $response->assertSee('COT-0001');
        $response->assertSee('COT-0002');
    }

    public function test_can_view_create_quotation_form(): void
    {
        $response = $this->actingAs($this->empleado)->get(route('cotizaciones.create'));

        $response->assertOk();
        $response->assertSee('Nueva Cotización');
    }

    public function test_can_create_quotation_with_services(): void
    {
        $datos = [
            'codigo' => 'COT-9999',
            'cliente_id' => $this->clienteId,
            'vendedor_id' => $this->empleado->id,
            'sucursal_id' => $this->sucursalId,
            'fecha_emision' => now()->toDateString(),
            'fecha_caducidad' => now()->addDays(15)->toDateString(),
            'fecha_viaje_inicio' => now()->addMonth()->toDateString(),
            'fecha_viaje_fin' => now()->addMonth()->addDays(5)->toDateString(),
            'numero_viajeros' => 2,
            'descuento_monto' => 10.00,
            'estado' => 'borrador',
            'notas' => 'Propuesta inicial para vacaciones',
            'servicios' => [
                [
                    'servicio_id' => $this->servicioId,
                    'cantidad' => 2,
                    'costo_neto_unitario' => 50.00,
                    'precio_unitario' => 80.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->empleado)->post(route('cotizaciones.store'), $datos);

        $response->assertRedirect(route('cotizaciones.index'));

        $this->assertDatabaseHas('presupuestos', [
            'codigo' => 'COT-9999',
            'cliente_id' => $this->clienteId,
            'descuento_monto' => 10.00,
        ]);

        $cotizacionId = DB::table('presupuestos')->where('codigo', 'COT-9999')->value('id');
        $this->assertDatabaseHas('presupuesto_servicios', [
            'presupuesto_id' => $cotizacionId,
            'servicio_id' => $this->servicioId,
            'cantidad' => 2,
            'precio_unitario' => 80.00,
        ]);

        $this->assertDatabaseHas('bitacora_auditoria', [
            'tabla' => 'presupuestos',
            'accion' => 'crear',
            'registro_id' => $cotizacionId,
        ]);
    }

    public function test_cannot_create_quotation_without_client_or_prospect(): void
    {
        $datos = [
            'codigo' => 'COT-8888',
            'cliente_id' => null,
            'prospecto_id' => null,
            'vendedor_id' => $this->empleado->id,
            'sucursal_id' => $this->sucursalId,
            'fecha_emision' => now()->toDateString(),
            'fecha_caducidad' => now()->addDays(10)->toDateString(),
            'numero_viajeros' => 1,
            'estado' => 'borrador',
        ];

        $response = $this->actingAs($this->empleado)->post(route('cotizaciones.store'), $datos);

        $response->assertSessionHasErrors(['cliente_id']);
    }

    public function test_can_update_quotation(): void
    {
        $id = $this->crearCotizacion(['codigo' => 'COT-0010']);

        $datosActualizados = [
            'codigo' => 'COT-0010',
            'cliente_id' => $this->clienteId,
            'vendedor_id' => $this->empleado->id,
            'sucursal_id' => $this->sucursalId,
            'fecha_emision' => now()->toDateString(),
            'fecha_caducidad' => now()->addDays(20)->toDateString(),
            'numero_viajeros' => 4,
            'descuento_monto' => 25.00,
            'estado' => 'enviado',
            'notas' => 'Enviada al cliente por WhatsApp',
            'servicios' => [
                [
                    'servicio_id' => $this->servicioId,
                    'cantidad' => 4,
                    'costo_neto_unitario' => 50.00,
                    'precio_unitario' => 75.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->empleado)->put(route('cotizaciones.update', $id), $datosActualizados);

        $response->assertRedirect(route('cotizaciones.index'));
        $this->assertDatabaseHas('presupuestos', [
            'id' => $id,
            'numero_viajeros' => 4,
            'estado' => 'enviado',
            'descuento_monto' => 25.00,
        ]);
        $this->assertDatabaseHas('presupuesto_servicios', [
            'presupuesto_id' => $id,
            'cantidad' => 4,
            'precio_unitario' => 75.00,
        ]);
    }

    public function test_can_update_quotation_state_via_patch(): void
    {
        $id = $this->crearCotizacion(['codigo' => 'COT-0020', 'estado' => 'borrador']);

        $response = $this->actingAs($this->empleado)->patchJson(route('cotizaciones.estado', $id), [
            'estado_destino' => 'aprobado',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'estado' => 'aprobado',
        ]);

        $this->assertDatabaseHas('presupuestos', [
            'id' => $id,
            'estado' => 'aprobado',
        ]);
    }

    public function test_approved_or_sent_quotation_cannot_be_deleted_directly(): void
    {
        $id = $this->crearCotizacion(['codigo' => 'COT-0030', 'estado' => 'enviado']);

        $response = $this->actingAs($this->empleado)->delete(route('cotizaciones.destroy', $id));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('presupuestos', ['id' => $id]);
    }

    public function test_draft_or_rejected_quotation_can_be_deleted(): void
    {
        $id = $this->crearCotizacion(['codigo' => 'COT-0040', 'estado' => 'rechazado']);

        $response = $this->actingAs($this->empleado)->delete(route('cotizaciones.destroy', $id));
        $response->assertRedirect(route('cotizaciones.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('presupuestos', ['id' => $id]);
        $this->assertDatabaseMissing('presupuesto_servicios', ['presupuesto_id' => $id]);
    }

    private function crearCotizacion(array $overrides = []): int
    {
        $cotId = DB::table('presupuestos')->insertGetId(array_merge([
            'codigo' => 'COT-0001',
            'cliente_id' => $this->clienteId,
            'prospecto_id' => null,
            'vendedor_id' => $this->empleado->id,
            'sucursal_id' => $this->sucursalId,
            'paquete_id' => null,
            'fecha_emision' => now()->toDateString(),
            'fecha_caducidad' => now()->addDays(15)->toDateString(),
            'fecha_viaje_inicio' => now()->addMonth()->toDateString(),
            'fecha_viaje_fin' => now()->addMonth()->addDays(5)->toDateString(),
            'numero_viajeros' => 2,
            'descuento_monto' => 0.00,
            'estado' => 'borrador',
            'notas' => 'Nota de cotización de prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        DB::table('presupuesto_servicios')->insert([
            'presupuesto_id' => $cotId,
            'servicio_id' => $this->servicioId,
            'cantidad' => 2,
            'costo_neto_unitario' => 50.00,
            'precio_unitario' => 80.00,
        ]);

        return $cotId;
    }
}
