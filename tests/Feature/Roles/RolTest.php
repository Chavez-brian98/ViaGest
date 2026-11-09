<?php

namespace Tests\Feature\Roles;

use App\Models\Empleado;
use App\Models\Rol;
use App\Service\Roles\RolService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RolTest extends TestCase
{
    use RefreshDatabase;

    private Empleado $admin;

    private Empleado $vendedor;

    private Rol $rolAdmin;

    private Rol $rolVendedor;

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

        $this->rolAdmin = Rol::create([
            'nombre' => 'administrador',
            'descripcion' => 'Administrador global',
            'alcance' => 'todas_sucursales',
            'estado' => 'activo',
        ]);

        $this->rolVendedor = Rol::create([
            'nombre' => 'vendedor',
            'descripcion' => 'Vendedor de sucursal',
            'alcance' => 'propia_sucursal',
            'estado' => 'activo',
        ]);

        $this->admin = Empleado::create([
            'codigo' => 'ADM-0001',
            'nombres' => 'Admin',
            'apellidos' => 'Supremo',
            'tipo_documento' => 'dui',
            'numero_documento' => '00000001-1',
            'cargo' => 'Gerente General',
            'sucursal_id' => $this->sucursalId,
            'fecha_ingreso' => now()->toDateString(),
            'email' => 'admin@viagest.com',
            'password' => 'secret-password',
            'rol_id' => $this->rolAdmin->id,
            'acceso' => 'activo',
            'estado' => 'activo',
        ]);

        $this->vendedor = Empleado::create([
            'codigo' => 'VEN-0001',
            'nombres' => 'Carlos',
            'apellidos' => 'Ventas',
            'tipo_documento' => 'dui',
            'numero_documento' => '00000002-2',
            'cargo' => 'Ejecutivo de Ventas',
            'sucursal_id' => $this->sucursalId,
            'fecha_ingreso' => now()->toDateString(),
            'email' => 'carlos@viagest.com',
            'password' => 'secret-password',
            'rol_id' => $this->rolVendedor->id,
            'acceso' => 'activo',
            'estado' => 'activo',
        ]);
    }

    public function test_guest_is_redirected_from_roles(): void
    {
        $this->get(route('roles.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_roles_permission_gets_403(): void
    {
        // El vendedor no tiene permiso sobre 'roles_permisos'
        $this->actingAs($this->vendedor)
            ->get(route('roles.index'))
            ->assertForbidden();
    }

    public function test_admin_can_access_roles_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('roles.index'))
            ->assertOk()
            ->assertSee('Roles y Permisos')
            ->assertSee('Administrador')
            ->assertSee('Vendedor');
    }

    public function test_user_with_explicit_permission_can_access(): void
    {
        // Otorgar permiso de ver 'roles_permisos' al rol vendedor
        DB::table('rol_permisos')->insert([
            'rol_id' => $this->rolVendedor->id,
            'modulo' => 'roles_permisos',
            'accion' => 'ver',
        ]);

        $this->actingAs($this->vendedor)
            ->get(route('roles.index'))
            ->assertOk();
    }

    public function test_roles_can_be_filtered_by_status(): void
    {
        Rol::create([
            'nombre' => 'inactivo-test',
            'alcance' => 'propia_sucursal',
            'estado' => 'inactivo',
        ]);

        $this->actingAs($this->admin)
            ->get(route('roles.index', ['estado' => 'inactivo']))
            ->assertOk()
            ->assertSee('Inactivo-test')
            ->assertDontSee('Vendedor');
    }

    public function test_can_create_role_with_permissions(): void
    {
        $response = $this->actingAs($this->admin)->post(route('roles.store'), [
            'nombre' => 'supervisor',
            'descripcion' => 'Supervisor de sucursal',
            'alcance' => 'propia_sucursal',
            'estado' => 'activo',
            'permisos' => [
                'clientes' => ['ver', 'crear', 'editar'],
                'reportes' => ['ver', 'exportar'],
            ],
        ]);

        $response->assertRedirect(route('roles.index'));

        $rol = Rol::where('nombre', 'supervisor')->first();
        $this->assertNotNull($rol);
        $this->assertEquals('Supervisor de sucursal', $rol->descripcion);

        $this->assertDatabaseHas('rol_permisos', [
            'rol_id' => $rol->id,
            'modulo' => 'clientes',
            'accion' => 'crear',
        ]);

        $this->assertDatabaseHas('rol_permisos', [
            'rol_id' => $rol->id,
            'modulo' => 'reportes',
            'accion' => 'exportar',
        ]);

        // Verifica que se registró en Auditoría
        $this->assertDatabaseHas('bitacora_auditoria', [
            'empleado_id' => $this->admin->id,
            'accion' => 'crear',
            'tabla' => 'roles',
            'registro_id' => $rol->id,
        ]);
    }

    public function test_can_update_role_permissions(): void
    {
        $rol = Rol::create([
            'nombre' => 'cajero',
            'descripcion' => 'Cajero de sucursal',
            'alcance' => 'propia_sucursal',
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($this->admin)->put(route('roles.update', $rol), [
            'nombre' => 'cajero',
            'descripcion' => 'Cajero principal',
            'alcance' => 'propia_sucursal',
            'permisos' => [
                'cuentas_por_cobrar' => ['ver', 'crear'],
            ],
        ]);

        $response->assertRedirect(route('roles.index'));

        $this->assertDatabaseHas('roles', [
            'id' => $rol->id,
            'descripcion' => 'Cajero principal',
        ]);

        $this->assertDatabaseHas('rol_permisos', [
            'rol_id' => $rol->id,
            'modulo' => 'cuentas_por_cobrar',
            'accion' => 'crear',
        ]);
    }

    public function test_admin_role_cannot_be_renamed_or_deactivated(): void
    {
        $service = app(RolService::class);

        // Actualizar datos del admin ignora cambio de nombre
        $service->actualizar($this->rolAdmin, [
            'nombre' => 'otro_nombre',
            'descripcion' => 'Nueva descripcion',
            'alcance' => 'todas_sucursales',
        ]);

        $this->rolAdmin->refresh();
        $this->assertEquals('administrador', $this->rolAdmin->nombre);
        $this->assertEquals('Nueva descripcion', $this->rolAdmin->descripcion);

        // Desactivar admin arroja excepción
        $this->expectException(\DomainException::class);
        $service->actualizarEstado($this->rolAdmin, 'inactivo');
    }

    public function test_admin_role_cannot_be_deleted(): void
    {
        $service = app(RolService::class);
        $this->expectException(\DomainException::class);
        $service->eliminar($this->rolAdmin);
    }

    public function test_role_cannot_be_deleted_if_active(): void
    {
        $rol = Rol::create([
            'nombre' => 'temporal',
            'alcance' => 'propia_sucursal',
            'estado' => 'activo',
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Primero deshabilita el rol');
        app(RolService::class)->eliminar($rol);
    }

    public function test_role_cannot_be_deleted_if_has_employees(): void
    {
        $rol = Rol::create([
            'nombre' => 'con_empleados',
            'alcance' => 'propia_sucursal',
            'estado' => 'inactivo',
        ]);

        $this->vendedor->update(['rol_id' => $rol->id]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('existen empleados asignados');
        app(RolService::class)->eliminar($rol);
    }

    public function test_inactive_role_without_employees_can_be_deleted(): void
    {
        $rol = Rol::create([
            'nombre' => 'para_borrar',
            'alcance' => 'propia_sucursal',
            'estado' => 'inactivo',
        ]);

        DB::table('rol_permisos')->insert([
            'rol_id' => $rol->id,
            'modulo' => 'clientes',
            'accion' => 'ver',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('roles.destroy', $rol));
        $response->assertRedirect(route('roles.index'));

        $this->assertDatabaseMissing('roles', ['id' => $rol->id]);
        $this->assertDatabaseMissing('rol_permisos', ['rol_id' => $rol->id]);

        $this->assertDatabaseHas('bitacora_auditoria', [
            'empleado_id' => $this->admin->id,
            'accion' => 'eliminar',
            'tabla' => 'roles',
            'registro_id' => $rol->id,
        ]);
    }

    public function test_role_status_toggle_via_ajax(): void
    {
        $rol = Rol::create([
            'nombre' => 'toggle_test',
            'alcance' => 'propia_sucursal',
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($this->admin)->patchJson(route('roles.estado', $rol), [
            'estado_destino' => 'inactivo',
        ]);

        $response->assertOk()
            ->assertJson([
                'estado' => 'inactivo',
            ]);

        $this->assertDatabaseHas('roles', [
            'id' => $rol->id,
            'estado' => 'inactivo',
        ]);
    }

    public function test_can_update_role_permissions_directly_via_modal_endpoint(): void
    {
        $rol = Rol::create([
            'nombre' => 'operador',
            'alcance' => 'propia_sucursal',
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($this->admin)->patchJson(route('roles.permisos.update', $rol), [
            'permisos' => [
                'paquetes' => ['ver', 'crear'],
                'guias' => ['ver'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('mensaje', 'Permisos del rol "operador" actualizados correctamente.');

        $this->assertDatabaseHas('rol_permisos', [
            'rol_id' => $rol->id,
            'modulo' => 'paquetes',
            'accion' => 'crear',
        ]);

        $this->assertDatabaseHas('rol_permisos', [
            'rol_id' => $rol->id,
            'modulo' => 'guias',
            'accion' => 'ver',
        ]);
    }

    public function test_employee_inherits_role_permissions_by_default(): void
    {
        DB::table('rol_permisos')->insert([
            'rol_id' => $this->rolVendedor->id,
            'modulo' => 'clientes',
            'accion' => 'ver',
        ]);

        $this->assertTrue($this->vendedor->tienePermiso('clientes', 'ver'));
        $this->assertFalse($this->vendedor->tienePermiso('clientes', 'eliminar'));
    }

    public function test_employee_permission_override_grants_access(): void
    {
        // Rol vendedor NO tiene permiso de ver empleados
        $this->assertFalse($this->vendedor->tienePermiso('empleados', 'ver'));

        // Conceder override específico al empleado
        $this->actingAs($this->admin)->patchJson(route('empleados.permisos.update', $this->vendedor), [
            'permisos' => [
                'empleados' => ['ver' => 'permitir'],
            ],
        ])->assertOk();

        // Ahora sí tiene permiso
        $this->assertTrue($this->vendedor->fresh()->tienePermiso('empleados', 'ver'));
    }

    public function test_employee_permission_override_denies_access_even_if_role_has_it(): void
    {
        // Rol vendedor sí tiene clientes:ver
        DB::table('rol_permisos')->insert([
            'rol_id' => $this->rolVendedor->id,
            'modulo' => 'clientes',
            'accion' => 'ver',
        ]);
        $this->assertTrue($this->vendedor->tienePermiso('clientes', 'ver'));

        // Denegar específicamente para este empleado
        $this->actingAs($this->admin)->patchJson(route('empleados.permisos.update', $this->vendedor), [
            'permisos' => [
                'clientes' => ['ver' => 'denegar'],
            ],
        ])->assertOk();

        // Ahora está bloqueado
        $this->assertFalse($this->vendedor->fresh()->tienePermiso('clientes', 'ver'));
    }

    public function test_administrator_permissions_cannot_be_overridden(): void
    {
        $this->actingAs($this->admin)
            ->patchJson(route('empleados.permisos.update', $this->admin), [
                'permisos' => [
                    'clientes' => ['ver' => 'denegar'],
                ],
            ])
            ->assertStatus(422);

        $this->assertDatabaseMissing('empleado_permisos', [
            'empleado_id' => $this->admin->id,
        ]);
    }

    public function test_employee_without_permission_cannot_manage_overrides(): void
    {
        $this->actingAs($this->vendedor)
            ->patchJson(route('empleados.permisos.update', $this->vendedor), [
                'permisos' => [
                    'empleados' => ['ver' => 'permitir'],
                ],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('empleado_permisos', [
            'empleado_id' => $this->vendedor->id,
        ]);
    }
}
