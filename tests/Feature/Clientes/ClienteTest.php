<?php

namespace Tests\Feature\Clientes;

use App\Models\Cliente;
use App\Models\Empleado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClienteTest extends TestCase
{
    use RefreshDatabase;

    private Empleado $empleado;

    private int $sucursalId;

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
            'sucursal_id' => $this->sucursalId,
            'fecha_ingreso' => now()->toDateString(),
            'email' => 'ana.torres@example.com',
            'password' => 'secret-password',
            'rol_id' => $rolId,
            'acceso' => 'activo',
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('clientes.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_employee_can_view_and_search_clients(): void
    {
        $this->crearCliente(['codigo' => 'CLI-0001', 'nombres' => 'Ana', 'apellidos' => 'Torres']);
        $this->crearCliente([
            'codigo' => 'CLI-0002',
            'nombres' => 'Bruno',
            'apellidos' => 'López',
            'numero_documento' => '07654321-0',
            'email' => 'bruno.lopez@example.com',
        ]);

        $this->crearCliente([
            'codigo' => 'CLI-0003',
            'tipo' => 'empresa',
            'nombres' => null,
            'apellidos' => null,
            'razon_social' => 'Viajes del Sol',
            'tipo_documento' => 'nit',
            'numero_documento' => '0614-123456-001-2',
            'email' => 'contacto@viajesdelsol.com',
        ]);

        $this->actingAs($this->empleado)
            ->get(route('clientes.index'))
            ->assertOk()
            ->assertSee('Directorio de personas y empresas registradas')
            ->assertSee('CLI-0001')
            ->assertSee('Ana Torres')
            ->assertSee('CLI-0002')
            ->assertSee('Viajes del Sol')
            ->assertSee('Empresa');

        $this->actingAs($this->empleado)
            ->get(route('clientes.index', ['buscar' => 'Bruno']))
            ->assertOk()
            ->assertSee('Bruno López')
            ->assertDontSee('CLI-0001');
    }

    public function test_client_list_is_paginated(): void
    {
        for ($i = 1; $i <= 13; $i++) {
            $this->crearCliente([
                'codigo' => sprintf('CLI-%04d', $i),
                'nombres' => 'Cliente '.$i,
                'numero_documento' => sprintf('%08d-%d', $i, $i % 10),
                'email' => "cliente{$i}@example.com",
            ]);
        }

        $this->actingAs($this->empleado)
            ->get(route('clientes.index'))
            ->assertOk()
            ->assertSee('Mostrando')
            ->assertSee('Página 2', false)
            ->assertViewHas('clientes', fn ($clientes) => $clientes->total() === 13 && $clientes->count() === 12);
    }

    public function test_client_list_can_be_filtered_by_state_type_and_branch(): void
    {
        $otraSucursalId = DB::table('sucursales')->insertGetId([
            'codigo' => 'SUC-001',
            'nombre' => 'Sucursal Norte',
            'tipo' => 'filial',
            'ciudad_id' => DB::table('ciudades')->value('id'),
        ]);

        $this->crearCliente(['codigo' => 'CLI-0101', 'numero_documento' => null]);
        $this->crearCliente([
            'codigo' => 'CLI-0102',
            'nombres' => 'Bruno',
            'numero_documento' => null,
            'estado' => 'inactivo',
        ]);
        $this->crearCliente([
            'codigo' => 'CLI-0103',
            'tipo' => 'empresa',
            'nombres' => null,
            'apellidos' => null,
            'razon_social' => 'Viajes del Sol',
            'numero_documento' => null,
            'sucursal_id' => $otraSucursalId,
        ]);

        $this->actingAs($this->empleado)
            ->get(route('clientes.index', ['estado' => 'inactivo']))
            ->assertOk()
            ->assertSee('CLI-0102')
            ->assertDontSee('CLI-0101')
            ->assertDontSee('CLI-0103');

        $this->actingAs($this->empleado)
            ->get(route('clientes.index', ['tipo' => 'empresa']))
            ->assertOk()
            ->assertSee('CLI-0103')
            ->assertDontSee('CLI-0101')
            ->assertDontSee('CLI-0102');

        $this->actingAs($this->empleado)
            ->get(route('clientes.index', ['sucursal_id' => $otraSucursalId]))
            ->assertOk()
            ->assertSee('CLI-0103')
            ->assertDontSee('CLI-0101')
            ->assertDontSee('CLI-0102');
    }

    public function test_search_returns_only_the_table_partial_for_ajax_requests(): void
    {
        $this->crearCliente(['codigo' => 'CLI-0001', 'nombres' => 'Ana', 'apellidos' => 'Torres']);

        $this->actingAs($this->empleado)
            ->get(route('clientes.index', ['buscar' => 'Ana']), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('CLI-0001')
            ->assertDontSee('Directorio de personas y empresas registradas');
    }

    public function test_create_and_edit_screens_can_be_rendered(): void
    {
        $cliente = $this->crearCliente();

        $this->actingAs($this->empleado)
            ->get(route('clientes.create'))
            ->assertOk()
            ->assertSee('Nuevo cliente')
            ->assertSee('Datos generales')
            ->assertSee('clienteEstado', false);

        $this->actingAs($this->empleado)
            ->get(route('clientes.edit', $cliente))
            ->assertOk()
            ->assertSee('Editar cliente')
            ->assertSee($cliente->codigo);
    }

    public function test_employee_can_create_a_person_client(): void
    {
        $this->actingAs($this->empleado)
            ->post(route('clientes.store'), [
                'codigo' => ' cli-0020 ',
                'tipo' => 'persona',
                'nombres' => '  Carla  ',
                'apellidos' => 'Méndez',
                'tipo_documento' => 'dui',
                'numero_documento' => '01234567-8',
                'nacionalidad_pais' => 'sv',
                'email' => 'CARLA@EXAMPLE.COM',
                'telefono' => '7777-7777',
                'sucursal_id' => $this->sucursalId,
            ])
            ->assertRedirect(route('clientes.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('clientes', [
            'codigo' => 'CLI-0020',
            'tipo' => 'persona',
            'nombres' => 'Carla',
            'apellidos' => 'Méndez',
            'nacionalidad_pais' => 'SV',
            'email' => 'carla@example.com',
            'estado' => 'activo',
        ]);
    }

    public function test_employee_can_create_clients_without_documents(): void
    {
        foreach (['CLI-0040', 'CLI-0041'] as $codigo) {
            $this->actingAs($this->empleado)
                ->post(route('clientes.store'), [
                    'codigo' => $codigo,
                    'tipo' => 'persona',
                    'nombres' => 'Sin Documento',
                    'sucursal_id' => $this->sucursalId,
                ])
                ->assertRedirect(route('clientes.index'))
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('clientes', 2);
    }

    public function test_company_client_requires_a_razon_social(): void
    {
        $this->actingAs($this->empleado)
            ->post(route('clientes.store'), [
                'codigo' => 'CLI-0030',
                'tipo' => 'empresa',
                'sucursal_id' => $this->sucursalId,
            ])
            ->assertSessionHasErrors('razon_social');

        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_employee_can_update_a_client(): void
    {
        $cliente = $this->crearCliente();

        $this->actingAs($this->empleado)
            ->put(route('clientes.update', $cliente), [
                'codigo' => $cliente->codigo,
                'tipo' => 'persona',
                'nombres' => 'Ana María',
                'apellidos' => 'Torres',
                'email' => 'ana.maria@example.com',
                'sucursal_id' => $this->sucursalId,
                'estado' => 'inactivo',
            ])
            ->assertRedirect(route('clientes.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'nombres' => 'Ana María',
            'email' => 'ana.maria@example.com',
            'estado' => 'inactivo',
        ]);
    }

    public function test_employee_can_toggle_client_state_from_the_list(): void
    {
        $cliente = $this->crearCliente();

        $this->actingAs($this->empleado)
            ->patch(route('clientes.estado', $cliente), [
                'estado_destino' => 'inactivo',
                'buscar' => 'Ana',
                'estado' => 'activo',
            ])
            ->assertRedirect(route('clientes.index', ['buscar' => 'Ana', 'estado' => 'activo']))
            ->assertSessionHas('success');

        $this->assertSame('inactivo', $cliente->fresh()?->estado);

        $this->actingAs($this->empleado)
            ->patch(route('clientes.estado', $cliente), [
                'estado_destino' => 'activo',
            ])
            ->assertRedirect(route('clientes.index'))
            ->assertSessionHas('success');

        $this->assertSame('activo', $cliente->fresh()?->estado);
    }

    public function test_state_toggle_returns_json_for_ajax_requests(): void
    {
        $cliente = $this->crearCliente();

        $this->actingAs($this->empleado)
            ->patchJson(route('clientes.estado', $cliente), [
                'estado_destino' => 'inactivo',
            ])
            ->assertOk()
            ->assertJson([
                'estado' => 'inactivo',
            ]);

        $this->assertSame('inactivo', $cliente->fresh()?->estado);
    }

    public function test_employee_can_disable_an_active_client(): void
    {
        $cliente = $this->crearCliente();

        $this->actingAs($this->empleado)
            ->patch(route('clientes.deshabilitar', $cliente))
            ->assertRedirect(route('clientes.index'))
            ->assertSessionHas('success');

        $this->assertSame('inactivo', $cliente->fresh()?->estado);
    }

    public function test_active_client_cannot_be_deleted(): void
    {
        $cliente = $this->crearCliente();

        $this->actingAs($this->empleado)
            ->delete(route('clientes.destroy', $cliente))
            ->assertRedirect(route('clientes.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
    }

    public function test_inactive_client_can_be_deleted(): void
    {
        $cliente = $this->crearCliente(['estado' => 'inactivo']);

        $this->actingAs($this->empleado)
            ->delete(route('clientes.destroy', $cliente))
            ->assertRedirect(route('clientes.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('clientes', ['id' => $cliente->id]);
    }

    public function test_inactive_client_with_sales_cannot_be_deleted(): void
    {
        $cliente = $this->crearCliente(['estado' => 'inactivo']);

        DB::table('ventas')->insert([
            'codigo' => 'VEN-0001',
            'cliente_id' => $cliente->id,
            'vendedor_id' => $this->empleado->id,
            'sucursal_id' => $this->sucursalId,
            'fecha_venta' => now()->toDateString(),
        ]);

        $this->actingAs($this->empleado)
            ->delete(route('clientes.destroy', $cliente))
            ->assertRedirect(route('clientes.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
    }

    private function crearCliente(array $overrides = []): Cliente
    {
        return Cliente::create(array_merge([
            'codigo' => 'CLI-0001',
            'tipo' => 'persona',
            'nombres' => 'Ana',
            'apellidos' => 'Torres',
            'tipo_documento' => 'dui',
            'numero_documento' => '01234567-8',
            'nacionalidad_pais' => 'SV',
            'email' => 'ana.torres@example.com',
            'telefono' => '7000-0000',
            'sucursal_id' => $this->sucursalId,
            'estado' => 'activo',
        ], $overrides));
    }
}
