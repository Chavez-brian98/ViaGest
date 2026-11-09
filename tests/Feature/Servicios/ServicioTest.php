<?php

namespace Tests\Feature\Servicios;

use App\Models\Empleado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ServicioTest extends TestCase
{
    use RefreshDatabase;

    private Empleado $empleado;

    private int $ciudadOrigenId;

    private int $ciudadDestinoId;

    private int $proveedorSecuencia = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->ciudadOrigenId = DB::table('ciudades')->insertGetId([
            'pais_codigo' => 'SV',
            'nombre' => 'San Salvador',
        ]);

        $this->ciudadDestinoId = DB::table('ciudades')->insertGetId([
            'pais_codigo' => 'US',
            'nombre' => 'Miami',
        ]);

        $sucursalId = DB::table('sucursales')->insertGetId([
            'codigo' => 'MAT-001',
            'nombre' => 'Matriz',
            'tipo' => 'matriz',
            'ciudad_id' => $this->ciudadOrigenId,
        ]);

        $rolId = DB::table('roles')->insertGetId([
            'nombre' => 'administrador',
            'alcance' => 'todas_sucursales',
        ]);

        $this->empleado = Empleado::create([
            'codigo' => 'EMP-001',
            'nombres' => 'Ana',
            'apellidos' => 'Torres',
            'numero_documento' => '00000001-1',
            'cargo' => 'Gerente',
            'sucursal_id' => $sucursalId,
            'fecha_ingreso' => now()->toDateString(),
            'email' => 'ana.torres@example.com',
            'password' => 'secret-password',
            'rol_id' => $rolId,
            'acceso' => 'activo',
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('servicios.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_employee_can_view_and_search_services(): void
    {
        $this->crearServicio(['codigo' => 'SRV-0001', 'nombre' => 'Noche de hotel']);
        $this->crearServicio([
            'codigo' => 'SRV-0002',
            'tipo' => 'vuelo',
            'nombre' => 'Vuelo San Salvador - Miami',
        ]);

        $this->actingAs($this->empleado)
            ->get(route('servicios.index'))
            ->assertOk()
            ->assertSee('Servicios')
            ->assertSee('SRV-0001')
            ->assertSee('Noche de hotel')
            ->assertSee('SRV-0002')
            ->assertSee('Vuelo San Salvador - Miami');

        $this->actingAs($this->empleado)
            ->get(route('servicios.index', ['buscar' => 'Vuelo']))
            ->assertOk()
            ->assertSee('SRV-0002')
            ->assertDontSee('SRV-0001');
    }

    public function test_service_list_can_be_filtered_by_type_state_and_provider(): void
    {
        $proveedorA = $this->crearProveedor(['codigo' => 'PROV-0101', 'nombre' => 'Hotel Uno']);
        $proveedorB = $this->crearProveedor(['codigo' => 'PROV-0102', 'nombre' => 'Hotel Dos']);

        $this->crearServicio([
            'codigo' => 'SRV-0011',
            'tipo' => 'hospedaje',
            'estado' => 'activo',
            'proveedor_id' => $proveedorA,
        ]);
        $this->crearServicio([
            'codigo' => 'SRV-0012',
            'tipo' => 'hospedaje',
            'estado' => 'inactivo',
            'proveedor_id' => $proveedorA,
        ]);
        $this->crearServicio([
            'codigo' => 'SRV-0013',
            'tipo' => 'traslado',
            'estado' => 'activo',
            'proveedor_id' => $proveedorB,
        ]);

        $this->actingAs($this->empleado)
            ->get(route('servicios.index', [
                'tipo' => 'hospedaje',
                'estado' => 'inactivo',
                'proveedor_id' => $proveedorA,
            ]))
            ->assertOk()
            ->assertSee('SRV-0012')
            ->assertDontSee('SRV-0011')
            ->assertDontSee('SRV-0013');
    }

    public function test_search_returns_only_the_table_partial_for_ajax_requests(): void
    {
        $this->crearServicio(['codigo' => 'SRV-0021', 'nombre' => 'Tour Volcán']);

        $this->actingAs($this->empleado)
            ->get(route('servicios.index', ['buscar' => 'Tour']), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('SRV-0021')
            ->assertDontSee('Catálogo de vuelos, hospedajes, traslados y actividades');
    }

    public function test_create_and_edit_screens_can_be_rendered(): void
    {
        $servicio = $this->crearServicio();

        $this->actingAs($this->empleado)
            ->get(route('servicios.create'))
            ->assertOk()
            ->assertSee('Nuevo servicio')
            ->assertSee('Datos generales')
            ->assertSee('servicioEstado', false);

        $this->actingAs($this->empleado)
            ->get(route('servicios.edit', $servicio))
            ->assertOk()
            ->assertSee('Editar servicio')
            ->assertSee('SRV-0001');
    }

    public function test_employee_can_create_a_service_and_it_is_audited(): void
    {
        $proveedor = $this->crearProveedor();

        $this->actingAs($this->empleado)
            ->post(route('servicios.store'), [
                'codigo' => ' srv-0030 ',
                'tipo' => 'HOSPEDAJE',
                'nombre' => '  Noche de hotel  ',
                'descripcion' => '  Incluye desayuno  ',
                'proveedor_id' => $proveedor,
                'ciudad_origen_id' => $this->ciudadOrigenId,
                'ciudad_destino_id' => '',
                'unidad_cobro' => 'POR_HABITACION_NOCHE',
                'costo_neto' => '125.50',
                'vigente_desde' => '2026-01-01',
                'vigente_hasta' => '2026-12-31',
                'estado' => 'ACTIVO',
            ])
            ->assertRedirect(route('servicios.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('servicios', [
            'codigo' => 'SRV-0030',
            'tipo' => 'hospedaje',
            'nombre' => 'Noche de hotel',
            'descripcion' => 'Incluye desayuno',
            'proveedor_id' => $proveedor,
            'ciudad_origen_id' => $this->ciudadOrigenId,
            'ciudad_destino_id' => null,
            'unidad_cobro' => 'por_habitacion_noche',
            'costo_neto' => 125.50,
            'estado' => 'activo',
        ]);

        $this->assertDatabaseHas('bitacora_auditoria', [
            'empleado_id' => $this->empleado->id,
            'accion' => 'crear',
            'tabla' => 'servicios',
        ]);
    }

    public function test_service_creation_validates_vigencia_and_provider(): void
    {
        $this->actingAs($this->empleado)
            ->post(route('servicios.store'), [
                'codigo' => 'SRV-0040',
                'tipo' => 'actividad',
                'nombre' => 'Servicio inválido',
                'proveedor_id' => '',
                'unidad_cobro' => 'por_persona',
                'costo_neto' => 10,
                'vigente_desde' => '2026-05-10',
                'vigente_hasta' => '2026-05-01',
                'estado' => 'activo',
            ])
            ->assertSessionHasErrors(['proveedor_id', 'vigente_hasta']);

        $this->assertDatabaseCount('servicios', 0);
    }

    public function test_employee_can_update_a_service(): void
    {
        $proveedor = $this->crearProveedor();
        $servicio = $this->crearServicio(['proveedor_id' => $proveedor]);

        $this->actingAs($this->empleado)
            ->put(route('servicios.update', $servicio), [
                'codigo' => 'SRV-0001',
                'tipo' => 'traslado',
                'nombre' => 'Traslado aeropuerto',
                'proveedor_id' => $proveedor,
                'ciudad_origen_id' => $this->ciudadOrigenId,
                'ciudad_destino_id' => $this->ciudadDestinoId,
                'unidad_cobro' => 'por_vehiculo',
                'costo_neto' => 45,
                'estado' => 'inactivo',
            ])
            ->assertRedirect(route('servicios.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('servicios', [
            'id' => $servicio,
            'tipo' => 'traslado',
            'nombre' => 'Traslado aeropuerto',
            'ciudad_destino_id' => $this->ciudadDestinoId,
            'unidad_cobro' => 'por_vehiculo',
            'costo_neto' => 45,
            'estado' => 'inactivo',
        ]);

        $this->assertDatabaseHas('bitacora_auditoria', [
            'empleado_id' => $this->empleado->id,
            'accion' => 'actualizar',
            'tabla' => 'servicios',
            'registro_id' => $servicio,
        ]);
    }

    public function test_employee_can_toggle_service_state(): void
    {
        $servicio = $this->crearServicio();

        $this->actingAs($this->empleado)
            ->patch(route('servicios.estado', $servicio), [
                'estado_destino' => 'inactivo',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('servicios', ['id' => $servicio, 'estado' => 'inactivo']);

        $this->actingAs($this->empleado)
            ->patchJson(route('servicios.estado', $servicio), [
                'estado_destino' => 'activo',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('servicios', ['id' => $servicio, 'estado' => 'activo']);
    }

    public function test_active_service_cannot_be_deleted(): void
    {
        $servicio = $this->crearServicio(['estado' => 'activo']);

        $this->actingAs($this->empleado)
            ->delete(route('servicios.destroy', $servicio))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('servicios', ['id' => $servicio]);
    }

    public function test_inactive_service_can_be_deleted(): void
    {
        $servicio = $this->crearServicio(['estado' => 'inactivo']);

        $this->actingAs($this->empleado)
            ->delete(route('servicios.destroy', $servicio))
            ->assertRedirect(route('servicios.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('servicios', ['id' => $servicio]);
    }

    public function test_inactive_service_with_related_package_cannot_be_deleted(): void
    {
        $servicio = $this->crearServicio(['estado' => 'inactivo']);

        $paqueteId = DB::table('paquetes')->insertGetId([
            'codigo' => 'PAQ-0001',
            'nombre' => 'Paquete demo',
            'duracion_dias' => 3,
            'duracion_noches' => 2,
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('paquete_servicios')->insert([
            'paquete_id' => $paqueteId,
            'servicio_id' => $servicio,
        ]);

        $this->actingAs($this->empleado)
            ->delete(route('servicios.destroy', $servicio))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('servicios', ['id' => $servicio]);
    }

    private function crearProveedor(array $overrides = []): int
    {
        $this->proveedorSecuencia++;

        $datos = array_merge([
            'codigo' => 'PROV-'.str_pad((string) $this->proveedorSecuencia, 4, '0', STR_PAD_LEFT),
            'tipo' => 'operador',
            'nombre' => 'Proveedor Demo',
            'pais_codigo' => 'SV',
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return DB::table('proveedores')->insertGetId($datos);
    }

    private function crearServicio(array $overrides = []): int
    {
        $datos = array_merge([
            'codigo' => 'SRV-0001',
            'tipo' => 'hospedaje',
            'nombre' => 'Servicio Demo',
            'descripcion' => null,
            'proveedor_id' => $this->crearProveedor(),
            'ciudad_origen_id' => null,
            'ciudad_destino_id' => null,
            'unidad_cobro' => 'por_persona',
            'costo_neto' => 100.00,
            'vigente_desde' => null,
            'vigente_hasta' => null,
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return DB::table('servicios')->insertGetId($datos);
    }
}
