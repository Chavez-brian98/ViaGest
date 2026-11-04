<?php

namespace Tests\Feature\Auditoria;

use App\Models\Cliente;
use App\Models\Empleado;
use App\Service\Auditoria\AuditoriaService;
use App\Service\Auth\LoginService;
use App\Service\Clientes\ClienteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    private Empleado $admin;

    private Empleado $vendedor;

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
            'codigo' => 'SUC-001',
            'nombre' => 'Sucursal Central',
            'tipo' => 'matriz',
            'ciudad_id' => $ciudadId,
        ]);

        $rolAdminId = DB::table('roles')->insertGetId([
            'nombre' => 'administrador',
            'alcance' => 'todas_sucursales',
        ]);

        $rolVendedorId = DB::table('roles')->insertGetId([
            'nombre' => 'vendedor',
            'alcance' => 'propia_sucursal',
        ]);

        $this->admin = Empleado::create([
            'codigo' => 'ADM-0001',
            'nombres' => 'Admin',
            'apellidos' => 'General',
            'tipo_documento' => 'dui',
            'numero_documento' => '00000001-1',
            'cargo' => 'Gerente General',
            'sucursal_id' => $this->sucursalId,
            'fecha_ingreso' => now()->toDateString(),
            'email' => 'admin@viagest.com',
            'password' => 'secret-password',
            'rol_id' => $rolAdminId,
            'acceso' => 'activo',
            'estado' => 'activo',
        ]);

        $this->vendedor = Empleado::create([
            'codigo' => 'VEN-0001',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'tipo_documento' => 'dui',
            'numero_documento' => '00000002-2',
            'cargo' => 'Vendedor',
            'sucursal_id' => $this->sucursalId,
            'fecha_ingreso' => now()->toDateString(),
            'email' => 'juan@viagest.com',
            'password' => 'secret-password',
            'rol_id' => $rolVendedorId,
            'acceso' => 'activo',
            'estado' => 'activo',
        ]);
    }

    public function test_guest_is_redirected_from_auditoria(): void
    {
        $this->get(route('auditoria.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_gets_403(): void
    {
        $this->actingAs($this->vendedor)
            ->get(route('auditoria.index'))
            ->assertForbidden();
    }

    public function test_admin_can_access_auditoria(): void
    {
        $this->actingAs($this->admin)
            ->get(route('auditoria.index'))
            ->assertOk()
            ->assertSee('Auditoría')
            ->assertSee('Registro de todas las acciones realizadas en el sistema');
    }

    public function test_auditoria_shows_records(): void
    {
        AuditoriaService::registrar([
            'empleado_id' => $this->admin->id,
            'accion' => 'crear',
            'tabla' => 'clientes',
            'registro_id' => 10,
            'datos_nuevos' => ['nombres' => 'Carlos', 'apellidos' => 'Gómez'],
        ]);

        $this->actingAs($this->admin)
            ->get(route('auditoria.index'))
            ->assertOk()
            ->assertSee('Creación')
            ->assertSee('Clientes')
            ->assertSee('Admin General');
    }

    public function test_auditoria_ajax_returns_partial(): void
    {
        AuditoriaService::registrar([
            'empleado_id' => $this->admin->id,
            'accion' => 'crear',
            'tabla' => 'clientes',
            'registro_id' => 11,
            'datos_nuevos' => ['nombres' => 'Carlos'],
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('auditoria.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk()
            ->assertSee('Clientes')
            ->assertDontSee('<!DOCTYPE html>');
    }

    public function test_auditoria_filter_by_accion(): void
    {
        AuditoriaService::registrar([
            'empleado_id' => $this->admin->id,
            'accion' => 'crear',
            'tabla' => 'clientes',
            'registro_id' => 1,
            'datos_nuevos' => ['codigo' => 'CLI-0001'],
        ]);

        AuditoriaService::registrar([
            'empleado_id' => $this->admin->id,
            'accion' => 'eliminar',
            'tabla' => 'clientes',
            'registro_id' => 2,
            'datos_anteriores' => ['codigo' => 'CLI-0002'],
        ]);

        $this->actingAs($this->admin)
            ->get(route('auditoria.index', ['accion' => 'eliminar']))
            ->assertOk()
            ->assertSee('Eliminación')
            ->assertDontSee('CLI-0001');
    }

    public function test_auditoria_filter_by_tabla(): void
    {
        AuditoriaService::registrar([
            'empleado_id' => $this->admin->id,
            'accion' => 'crear',
            'tabla' => 'clientes',
            'registro_id' => 1,
            'datos_nuevos' => ['nombre' => 'Cliente A'],
        ]);

        AuditoriaService::registrar([
            'empleado_id' => $this->admin->id,
            'accion' => 'crear',
            'tabla' => 'empleados',
            'registro_id' => 2,
            'datos_nuevos' => ['nombre' => 'Empleado B'],
        ]);

        $this->actingAs($this->admin)
            ->get(route('auditoria.index', ['tabla' => 'empleados']))
            ->assertOk()
            ->assertSee('Empleados');
    }

    public function test_auditoria_filter_by_empleado(): void
    {
        AuditoriaService::registrar([
            'empleado_id' => $this->admin->id,
            'accion' => 'crear',
            'tabla' => 'clientes',
        ]);

        AuditoriaService::registrar([
            'empleado_id' => $this->vendedor->id,
            'accion' => 'actualizar',
            'tabla' => 'clientes',
        ]);

        $this->actingAs($this->admin)
            ->get(route('auditoria.index', ['empleado_id' => $this->vendedor->id]))
            ->assertOk()
            ->assertSee('Juan Pérez');
    }

    public function test_auditoria_paginated(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            AuditoriaService::registrar([
                'empleado_id' => $this->admin->id,
                'accion' => 'crear',
                'tabla' => 'clientes',
                'registro_id' => $i,
            ]);
        }

        $this->actingAs($this->admin)
            ->get(route('auditoria.index'))
            ->assertOk()
            ->assertViewHas('registros', fn ($r) => $r->total() === 25 && $r->count() === 20);
    }

    public function test_login_registers_audit(): void
    {
        $loginService = new LoginService;
        $ok = $loginService->authenticate([
            'email' => 'admin@viagest.com',
            'password' => 'secret-password',
        ]);

        $this->assertTrue($ok);
        $this->assertDatabaseHas('bitacora_auditoria', [
            'empleado_id' => $this->admin->id,
            'accion' => 'inicio_sesion',
        ]);
    }

    public function test_logout_registers_audit(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('bitacora_auditoria', [
            'empleado_id' => $this->admin->id,
            'accion' => 'cierre_sesion',
        ]);
    }

    public function test_cliente_creation_registers_audit(): void
    {
        $this->actingAs($this->admin);

        $service = app(ClienteService::class);
        $cliente = $service->crear([
            'codigo' => 'CLI-9999',
            'tipo' => 'persona',
            'nombres' => 'Mario',
            'apellidos' => 'Bros',
            'tipo_documento' => 'dui',
            'numero_documento' => '99999999-9',
            'sucursal_id' => $this->sucursalId,
        ]);

        $this->assertDatabaseHas('bitacora_auditoria', [
            'empleado_id' => $this->admin->id,
            'accion' => 'crear',
            'tabla' => 'clientes',
            'registro_id' => $cliente->id,
        ]);
    }

    public function test_cliente_update_registers_audit(): void
    {
        $this->actingAs($this->admin);

        $service = app(ClienteService::class);
        $cliente = Cliente::create([
            'codigo' => 'CLI-9998',
            'tipo' => 'persona',
            'nombres' => 'Luigi',
            'apellidos' => 'Bros',
            'tipo_documento' => 'dui',
            'numero_documento' => '88888888-8',
            'sucursal_id' => $this->sucursalId,
            'estado' => 'activo',
        ]);

        $service->actualizar($cliente, ['nombres' => 'Luigi Actualizado']);

        $this->assertDatabaseHas('bitacora_auditoria', [
            'empleado_id' => $this->admin->id,
            'accion' => 'actualizar',
            'tabla' => 'clientes',
            'registro_id' => $cliente->id,
        ]);
    }

    public function test_cliente_delete_registers_audit(): void
    {
        $this->actingAs($this->admin);

        $service = app(ClienteService::class);
        $cliente = Cliente::create([
            'codigo' => 'CLI-9997',
            'tipo' => 'persona',
            'nombres' => 'Toad',
            'apellidos' => 'Mushroom',
            'tipo_documento' => 'dui',
            'numero_documento' => '77777777-7',
            'sucursal_id' => $this->sucursalId,
            'estado' => 'inactivo',
        ]);

        $service->eliminar($cliente);

        $this->assertDatabaseHas('bitacora_auditoria', [
            'empleado_id' => $this->admin->id,
            'accion' => 'eliminar',
            'tabla' => 'clientes',
            'registro_id' => $cliente->id,
        ]);
    }

    public function test_cliente_estado_registers_audit(): void
    {
        $this->actingAs($this->admin);

        $service = app(ClienteService::class);
        $cliente = Cliente::create([
            'codigo' => 'CLI-9996',
            'tipo' => 'persona',
            'nombres' => 'Peach',
            'apellidos' => 'Princess',
            'tipo_documento' => 'dui',
            'numero_documento' => '66666666-6',
            'sucursal_id' => $this->sucursalId,
            'estado' => 'activo',
        ]);

        $service->actualizarEstado($cliente, 'inactivo');

        $this->assertDatabaseHas('bitacora_auditoria', [
            'empleado_id' => $this->admin->id,
            'accion' => 'actualizar',
            'tabla' => 'clientes',
            'registro_id' => $cliente->id,
        ]);
    }

    public function test_calcular_diff_actualizacion(): void
    {
        $anterior = ['nombres' => 'Juan', 'cargo' => 'Vendedor', 'salario_base' => 500];
        $nuevo = ['nombres' => 'Juan', 'cargo' => 'Supervisor', 'salario_base' => 650];

        $diff = AuditoriaService::calcularDiff($anterior, $nuevo);

        $this->assertArrayNotHasKey('nombres', $diff);
        $this->assertArrayHasKey('cargo', $diff);
        $this->assertArrayHasKey('salario_base', $diff);
        $this->assertSame('Vendedor', $diff['cargo']['antes']);
        $this->assertSame('Supervisor', $diff['cargo']['despues']);
    }

    public function test_calcular_diff_creacion(): void
    {
        $nuevo = ['nombres' => 'Carlos', 'codigo' => 'CLI-0001'];
        $diff = AuditoriaService::calcularDiff(null, $nuevo);

        $this->assertCount(2, $diff);
        $this->assertNull($diff['nombres']['antes']);
        $this->assertSame('Carlos', $diff['nombres']['despues']);
    }

    public function test_calcular_diff_eliminacion(): void
    {
        $anterior = ['nombres' => 'Carlos', 'codigo' => 'CLI-0001'];
        $diff = AuditoriaService::calcularDiff($anterior, null);

        $this->assertCount(2, $diff);
        $this->assertSame('Carlos', $diff['nombres']['antes']);
        $this->assertNull($diff['nombres']['despues']);
    }
}
