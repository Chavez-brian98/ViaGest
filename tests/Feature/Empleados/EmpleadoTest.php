<?php

namespace Tests\Feature\Empleados;

use App\Models\Empleado;
use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmpleadoTest extends TestCase
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
        $this->get(route('empleados.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_employee_can_view_and_search_employees(): void
    {
        $this->crearEmpleado(['codigo' => 'EMP-0001', 'nombres' => 'Ana', 'apellidos' => 'Torres']);
        $this->crearEmpleado([
            'codigo' => 'EMP-0002',
            'nombres' => 'Bruno',
            'apellidos' => 'López',
            'numero_documento' => '07654321-0',
            'email' => 'bruno.lopez@example.com',
        ]);
        $this->crearEmpleado([
            'codigo' => 'EMP-0003',
            'nombres' => 'Carla',
            'apellidos' => 'Méndez',
            'cargo' => 'Vendedora',
            'email' => 'carla.mendez@example.com',
        ]);

        $this->actingAs($this->empleado)
            ->get(route('empleados.index'))
            ->assertOk()
            ->assertSee('Gestión del personal administrativo y operativo')
            ->assertSee('EMP-0001')
            ->assertSee('Ana Torres')
            ->assertSee('EMP-0002')
            ->assertSee('Carla Méndez');

        $this->actingAs($this->empleado)
            ->get(route('empleados.index', ['buscar' => 'Bruno']))
            ->assertOk()
            ->assertSee('Bruno López')
            ->assertDontSee('EMP-0001');
    }

    public function test_employee_list_is_paginated(): void
    {
        for ($i = 2; $i <= 14; $i++) {
            $this->crearEmpleado([
                'codigo' => sprintf('EMP-%04d', $i),
                'nombres' => 'Empleado '.$i,
                'numero_documento' => sprintf('%08d-%d', $i, $i % 10),
                'email' => "empleado{$i}@example.com",
            ]);
        }

        $this->actingAs($this->empleado)
            ->get(route('empleados.index'))
            ->assertOk()
            ->assertSee('Mostrando')
            ->assertViewHas('empleados', fn ($empleados) => $empleados->total() === 14 && $empleados->count() === 12);
    }

    public function test_employee_list_can_be_filtered_by_state_and_branch(): void
    {
        $otraSucursalId = DB::table('sucursales')->insertGetId([
            'codigo' => 'SUC-001',
            'nombre' => 'Sucursal Norte',
            'tipo' => 'filial',
            'ciudad_id' => DB::table('ciudades')->value('id'),
        ]);

        $this->crearEmpleado([
            'codigo' => 'EMP-0101',
            'numero_documento' => '00000011-1',
        ]);
        $this->crearEmpleado([
            'codigo' => 'EMP-0102',
            'nombres' => 'Bruno',
            'numero_documento' => '00000002-2',
            'estado' => 'inactivo',
        ]);
        $this->crearEmpleado([
            'codigo' => 'EMP-0103',
            'nombres' => 'Carla',
            'numero_documento' => '00000003-3',
            'sucursal_id' => $otraSucursalId,
        ]);

        $this->actingAs($this->empleado)
            ->get(route('empleados.index', ['estado' => 'inactivo']))
            ->assertOk()
            ->assertSee('EMP-0102')
            ->assertDontSee('EMP-0101')
            ->assertDontSee('EMP-0103');

        $this->actingAs($this->empleado)
            ->get(route('empleados.index', ['sucursal_id' => $otraSucursalId]))
            ->assertOk()
            ->assertSee('EMP-0103')
            ->assertDontSee('EMP-0101')
            ->assertDontSee('EMP-0102');
    }

    public function test_search_returns_only_the_table_partial_for_ajax_requests(): void
    {
        $this->crearEmpleado(['codigo' => 'EMP-0001', 'nombres' => 'Ana', 'apellidos' => 'Torres']);

        $this->actingAs($this->empleado)
            ->get(route('empleados.index', ['buscar' => 'Ana']), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('EMP-0001')
            ->assertDontSee('Gestión del personal administrativo y operativo');
    }

    public function test_create_and_edit_screens_can_be_rendered(): void
    {
        $empleado = $this->crearEmpleado();

        $this->actingAs($this->empleado)
            ->get(route('empleados.create'))
            ->assertOk()
            ->assertSee('Nuevo empleado')
            ->assertSee('Datos generales')
            ->assertSee('empleadoEstado', false);

        $this->actingAs($this->empleado)
            ->get(route('empleados.edit', $empleado))
            ->assertOk()
            ->assertSee('Editar empleado')
            ->assertSee($empleado->codigo);
    }

    public function test_employee_can_create_an_employee(): void
    {
        $this->actingAs($this->empleado)
            ->post(route('empleados.store'), [
                'codigo' => ' emp-0020 ',
                'nombres' => '  Carla  ',
                'apellidos' => 'Méndez',
                'tipo_documento' => 'dui',
                'numero_documento' => '01234567-8',
                'email' => 'CARLA@EXAMPLE.COM',
                'telefono' => '7777-7777',
                'cargo' => 'Vendedora',
                'fecha_ingreso' => now()->toDateString(),
                'sucursal_id' => $this->sucursalId,
            ])
            ->assertRedirect(route('empleados.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('empleados', [
            'codigo' => 'EMP-0020',
            'nombres' => 'Carla',
            'apellidos' => 'Méndez',
            'email' => 'carla@example.com',
            'estado' => 'activo',
        ]);
    }

    public function test_employee_can_update_an_employee(): void
    {
        $empleado = $this->crearEmpleado();

        $this->actingAs($this->empleado)
            ->put(route('empleados.update', $empleado), [
                'codigo' => $empleado->codigo,
                'nombres' => 'Ana María',
                'apellidos' => 'Torres',
                'email' => 'ana.maria@example.com',
                'sucursal_id' => $this->sucursalId,
                'estado' => 'inactivo',
            ])
            ->assertRedirect(route('empleados.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('empleados', [
            'id' => $empleado->id,
            'nombres' => 'Ana María',
            'email' => 'ana.maria@example.com',
            'estado' => 'inactivo',
        ]);
    }

    public function test_employee_can_toggle_employee_state_from_the_list(): void
    {
        $empleado = $this->crearEmpleado();

        $this->actingAs($this->empleado)
            ->patch(route('empleados.estado', $empleado), [
                'estado_destino' => 'inactivo',
                'buscar' => 'Ana',
                'estado' => 'activo',
            ])
            ->assertRedirect(route('empleados.index', ['buscar' => 'Ana', 'estado' => 'activo']))
            ->assertSessionHas('success');

        $this->assertSame('inactivo', $empleado->fresh()?->estado);

        $this->actingAs($this->empleado)
            ->patch(route('empleados.estado', $empleado), [
                'estado_destino' => 'activo',
            ])
            ->assertRedirect(route('empleados.index'))
            ->assertSessionHas('success');

        $this->assertSame('activo', $empleado->fresh()?->estado);
    }

    public function test_state_toggle_returns_json_for_ajax_requests(): void
    {
        $empleado = $this->crearEmpleado();

        $this->actingAs($this->empleado)
            ->patchJson(route('empleados.estado', $empleado), [
                'estado_destino' => 'inactivo',
            ])
            ->assertOk()
            ->assertJson([
                'estado' => 'inactivo',
            ]);

        $this->assertSame('inactivo', $empleado->fresh()?->estado);
    }

    public function test_employee_can_disable_an_active_employee(): void
    {
        $empleado = $this->crearEmpleado();

        $this->actingAs($this->empleado)
            ->patch(route('empleados.deshabilitar', $empleado))
            ->assertRedirect(route('empleados.index'))
            ->assertSessionHas('success');

        $this->assertSame('inactivo', $empleado->fresh()?->estado);
    }

    public function test_active_employee_cannot_be_deleted(): void
    {
        $empleado = $this->crearEmpleado();

        $this->actingAs($this->empleado)
            ->delete(route('empleados.destroy', $empleado))
            ->assertRedirect(route('empleados.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('empleados', ['id' => $empleado->id]);
    }

    public function test_inactive_employee_can_be_deleted(): void
    {
        $empleado = $this->crearEmpleado(['estado' => 'inactivo']);

        $this->actingAs($this->empleado)
            ->delete(route('empleados.destroy', $empleado))
            ->assertRedirect(route('empleados.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('empleados', ['id' => $empleado->id]);
    }

    private function crearEmpleado(array $overrides = []): Empleado
    {
        $faker = Factory::create();

        return Empleado::create(array_merge([
            'codigo' => 'EMP-0001',
            'nombres' => 'Ana',
            'apellidos' => 'Torres',
            'tipo_documento' => 'dui',
            'numero_documento' => $faker->unique()->numerify('#########-#'),
            'email' => $faker->unique()->safeEmail,
            'telefono' => '7000-0000',
            'cargo' => 'Empleado',
            'fecha_ingreso' => now()->toDateString(),
            'sucursal_id' => $this->sucursalId,
            'estado' => 'activo',
            'rol_id' => DB::table('roles')->value('id'),
            'acceso' => 'activo',
        ], $overrides));
    }
}
