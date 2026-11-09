<?php

namespace Tests\Feature\Configuracion;

use App\Models\Empleado;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConfiguracionTest extends TestCase
{
    use RefreshDatabase;

    private Empleado $admin;

    private Empleado $vendedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $ciudadId = DB::table('ciudades')->insertGetId([
            'pais_codigo' => 'SV',
            'nombre' => 'San Salvador',
        ]);

        $sucursalId = DB::table('sucursales')->insertGetId([
            'codigo' => 'SUC-001',
            'nombre' => 'Sucursal Central',
            'tipo' => 'matriz',
            'ciudad_id' => $ciudadId,
        ]);

        $rolAdmin = Rol::create([
            'nombre' => 'administrador',
            'descripcion' => 'Administrador global',
            'alcance' => 'todas_sucursales',
            'estado' => 'activo',
        ]);

        $rolVendedor = Rol::create([
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
            'sucursal_id' => $sucursalId,
            'fecha_ingreso' => now()->toDateString(),
            'email' => 'admin@viagest.com',
            'password' => 'secret-password',
            'rol_id' => $rolAdmin->id,
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
            'sucursal_id' => $sucursalId,
            'fecha_ingreso' => now()->toDateString(),
            'email' => 'vendedor@viagest.com',
            'password' => 'secret-password',
            'rol_id' => $rolVendedor->id,
            'acceso' => 'activo',
            'estado' => 'activo',
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('configuracion.edit'))->assertRedirect(route('login'));
    }

    public function test_admin_can_view_configuration(): void
    {
        $this->actingAs($this->admin)
            ->get(route('configuracion.edit'))
            ->assertOk()
            ->assertSee('Configuración del sistema')
            ->assertSee('Nombre comercial');
    }

    public function test_user_without_permission_cannot_access_configuration(): void
    {
        $this->actingAs($this->vendedor)
            ->get(route('configuracion.edit'))
            ->assertForbidden();
    }

    public function test_admin_can_update_configuration_and_audit_is_recorded(): void
    {
        $this->actingAs($this->admin)
            ->put(route('configuracion.update'), $this->datos())
            ->assertRedirect(route('configuracion.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('configuracion_empresa', [
            'id' => 1,
            'nombre_comercial' => 'Agencia Demo',
            'color_primary' => '#112233',
            'color_secondary' => '#445566',
            'color_accent' => '#778899',
            'color_background' => '#f0f0f0',
            'color_surface' => '#ffffff',
            'color_ink' => '#101010',
        ]);

        $this->assertDatabaseHas('bitacora_auditoria', [
            'accion' => 'actualizar',
            'tabla' => 'configuracion_empresa',
            'registro_id' => 1,
        ]);
    }

    public function test_configuration_validates_color_format(): void
    {
        $this->actingAs($this->admin)
            ->put(route('configuracion.update'), $this->datos(['color_primary' => 'azul']))
            ->assertSessionHasErrors('color_primary');
    }

    public function test_logo_can_be_uploaded_and_removed(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->put(route('configuracion.update'), $this->datos([
                'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
            ]))
            ->assertSessionHasNoErrors();

        $ruta = DB::table('configuracion_empresa')->where('id', 1)->value('logo_ruta');

        $this->assertNotNull($ruta);
        Storage::disk('public')->assertExists($ruta);

        $this->actingAs($this->admin)
            ->put(route('configuracion.update'), $this->datos(['eliminar_logo' => '1']))
            ->assertSessionHasNoErrors();

        $this->assertNull(DB::table('configuracion_empresa')->where('id', 1)->value('logo_ruta'));
        Storage::disk('public')->assertMissing($ruta);
    }

    private function datos(array $overrides = []): array
    {
        return array_merge([
            'nombre_comercial' => 'Agencia Demo',
            'razon_social' => 'Agencia Demo S.A. de C.V.',
            'documento_fiscal' => '0614-123456-001-1',
            'email_contacto' => 'contacto@agencia.test',
            'telefono' => '2222-3333',
            'direccion' => 'Calle Principal #123',
            'zona_horaria' => 'America/El_Salvador',
            'moneda_base' => 'USD',
            'terminos_condiciones' => 'Términos de prueba.',
            'color_primary' => '#112233',
            'color_secondary' => '#445566',
            'color_accent' => '#778899',
            'color_background' => '#f0f0f0',
            'color_surface' => '#ffffff',
            'color_ink' => '#101010',
        ], $overrides);
    }
}
