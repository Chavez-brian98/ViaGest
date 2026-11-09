<?php

namespace Tests\Feature\Proveedores;

use App\Models\Empleado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProveedorTest extends TestCase
{
    use RefreshDatabase;

    private Empleado $empleado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $ciudadId = DB::table('ciudades')->insertGetId([
            'pais_codigo' => 'SV',
            'nombre' => 'San Salvador',
        ]);

        $sucursalId = DB::table('sucursales')->insertGetId([
            'codigo' => 'MAT-001',
            'nombre' => 'Matriz',
            'tipo' => 'matriz',
            'ciudad_id' => $ciudadId,
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
        $this->get(route('proveedores.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_employee_can_view_and_search_providers(): void
    {
        $this->crearProveedor(['codigo' => 'PROV-0001', 'nombre' => 'Hotel Escalon']);
        $this->crearProveedor([
            'codigo' => 'PROV-0002',
            'tipo' => 'aerolinea',
            'nombre' => 'Aerolínea Nacional',
            'documento_fiscal' => '0614-123456-001-2',
            'email' => 'reservas@aerolinea.test',
        ]);

        $this->actingAs($this->empleado)
            ->get(route('proveedores.index'))
            ->assertOk()
            ->assertSee('Proveedores')
            ->assertSee('PROV-0001')
            ->assertSee('Hotel Escalon')
            ->assertSee('PROV-0002')
            ->assertSee('Aerolínea Nacional');

        $this->actingAs($this->empleado)
            ->get(route('proveedores.index', ['buscar' => 'Aerolínea']))
            ->assertOk()
            ->assertSee('PROV-0002')
            ->assertDontSee('PROV-0001');
    }

    public function test_provider_list_can_be_filtered_by_type_and_state(): void
    {
        $this->crearProveedor(['codigo' => 'PROV-0011', 'tipo' => 'hospedaje', 'estado' => 'activo']);
        $this->crearProveedor(['codigo' => 'PROV-0012', 'tipo' => 'hospedaje', 'estado' => 'inactivo']);
        $this->crearProveedor(['codigo' => 'PROV-0013', 'tipo' => 'transporte', 'estado' => 'activo']);

        $this->actingAs($this->empleado)
            ->get(route('proveedores.index', ['tipo' => 'hospedaje', 'estado' => 'inactivo']))
            ->assertOk()
            ->assertSee('PROV-0012')
            ->assertDontSee('PROV-0011')
            ->assertDontSee('PROV-0013');
    }

    public function test_search_returns_only_the_table_partial_for_ajax_requests(): void
    {
        $this->crearProveedor(['codigo' => 'PROV-0021', 'nombre' => 'Operador Local']);

        $this->actingAs($this->empleado)
            ->get(route('proveedores.index', ['buscar' => 'Operador']), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('PROV-0021')
            ->assertDontSee('Gestión de proveedores');
    }

    public function test_create_and_edit_screens_can_be_rendered(): void
    {
        $proveedor = $this->crearProveedor();

        $this->actingAs($this->empleado)
            ->get(route('proveedores.create'))
            ->assertOk()
            ->assertSee('Nuevo proveedor')
            ->assertSee('Datos generales')
            ->assertSee('proveedorEstado', false);

        $this->actingAs($this->empleado)
            ->get(route('proveedores.edit', $proveedor))
            ->assertOk()
            ->assertSee('Editar proveedor')
            ->assertSee('PROV-0001');
    }

    public function test_employee_can_create_a_provider_and_it_is_audited(): void
    {
        $this->actingAs($this->empleado)
            ->post(route('proveedores.store'), [
                'codigo' => ' prov-0030 ',
                'tipo' => 'hospedaje',
                'nombre' => '  Hotel Costa  ',
                'razon_social' => 'Hotel Costa SA',
                'documento_fiscal' => '0614-123456-001-2',
                'pais_codigo' => 'sv',
                'contacto_nombre' => 'Luis Pérez',
                'email' => 'RESERVAS@HOTEL.TEST',
                'telefono' => '2222-2222',
                'dias_credito' => 30,
                'notas' => 'Requiere confirmación anticipada.',
                'estado' => 'activo',
            ])
            ->assertRedirect(route('proveedores.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('proveedores', [
            'codigo' => 'PROV-0030',
            'tipo' => 'hospedaje',
            'nombre' => 'Hotel Costa',
            'pais_codigo' => 'SV',
            'email' => 'reservas@hotel.test',
            'dias_credito' => 30,
            'estado' => 'activo',
        ]);

        $this->assertDatabaseHas('bitacora_auditoria', [
            'empleado_id' => $this->empleado->id,
            'accion' => 'crear',
            'tabla' => 'proveedores',
        ]);
    }

    public function test_employee_can_update_a_provider(): void
    {
        $proveedor = $this->crearProveedor();

        $this->actingAs($this->empleado)
            ->put(route('proveedores.update', $proveedor), [
                'codigo' => 'PROV-0001',
                'tipo' => 'operador',
                'nombre' => 'Operador Actualizado',
                'pais_codigo' => 'GT',
                'dias_credito' => 15,
                'estado' => 'inactivo',
            ])
            ->assertRedirect(route('proveedores.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('proveedores', [
            'id' => $proveedor,
            'nombre' => 'Operador Actualizado',
            'pais_codigo' => 'GT',
            'dias_credito' => 15,
            'estado' => 'inactivo',
        ]);
    }

    public function test_employee_can_toggle_provider_state(): void
    {
        $proveedor = $this->crearProveedor();

        $this->actingAs($this->empleado)
            ->patch(route('proveedores.estado', $proveedor), [
                'estado_destino' => 'inactivo',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('proveedores', ['id' => $proveedor, 'estado' => 'inactivo']);

        $this->actingAs($this->empleado)
            ->patchJson(route('proveedores.estado', $proveedor), [
                'estado_destino' => 'activo',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('proveedores', ['id' => $proveedor, 'estado' => 'activo']);
    }

    public function test_active_provider_cannot_be_deleted(): void
    {
        $proveedor = $this->crearProveedor(['estado' => 'activo']);

        $this->actingAs($this->empleado)
            ->delete(route('proveedores.destroy', $proveedor))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('proveedores', ['id' => $proveedor]);
    }

    public function test_inactive_provider_can_be_deleted(): void
    {
        $proveedor = $this->crearProveedor(['estado' => 'inactivo']);

        $this->actingAs($this->empleado)
            ->delete(route('proveedores.destroy', $proveedor))
            ->assertRedirect(route('proveedores.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('proveedores', ['id' => $proveedor]);
    }

    public function test_inactive_provider_with_related_services_cannot_be_deleted(): void
    {
        $proveedor = $this->crearProveedor(['estado' => 'inactivo']);

        DB::table('servicios')->insert([
            'codigo' => 'SRV-0001',
            'tipo' => 'hospedaje',
            'nombre' => 'Noche de hotel',
            'proveedor_id' => $proveedor,
            'costo_neto' => 100,
        ]);

        $this->actingAs($this->empleado)
            ->delete(route('proveedores.destroy', $proveedor))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('proveedores', ['id' => $proveedor]);
    }

    private function crearProveedor(array $overrides = []): int
    {
        $datos = array_merge([
            'codigo' => 'PROV-0001',
            'tipo' => 'operador',
            'nombre' => 'Proveedor Demo',
            'razon_social' => 'Proveedor Demo SA',
            'documento_fiscal' => null,
            'pais_codigo' => 'SV',
            'contacto_nombre' => 'Contacto Demo',
            'email' => 'proveedor@example.com',
            'telefono' => '2222-0000',
            'dias_credito' => 0,
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        return DB::table('proveedores')->insertGetId($datos);
    }
}
