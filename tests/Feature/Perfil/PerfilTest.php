<?php

namespace Tests\Feature\Perfil;

use App\Models\Empleado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PerfilTest extends TestCase
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

        $this->empleado = $this->crearEmpleado([
            'rol_id' => $rolId,
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('perfil.edit'))->assertRedirect(route('login'));
        $this->put(route('perfil.update'), $this->datos())->assertRedirect(route('login'));
    }

    public function test_authenticated_employee_can_view_their_profile(): void
    {
        $this->actingAs($this->empleado)
            ->get(route('perfil.edit'))
            ->assertOk()
            ->assertSee('Mi perfil')
            ->assertSee('Ana Torres')
            ->assertSee('ana.torres@example.com')
            ->assertSee(route('perfil.update'), false)
            ->assertSee(route('perfil.edit'), false);
    }

    public function test_employee_can_update_their_profile_information(): void
    {
        $response = $this->actingAs($this->empleado)
            ->put(route('perfil.update'), $this->datos([
                'nombres' => 'Ana María',
                'apellidos' => 'Torres López',
                'telefono' => '7777-8888',
                'email' => 'ana.nueva@example.com',
                'cargo' => 'Supervisora',
                'tipo_contrato' => 'plazo_fijo',
                'fecha_ingreso' => '2024-01-15',
                'fecha_salida' => '',
                'salario_base' => '1875.50',
            ]));

        $response
            ->assertRedirect(route('perfil.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('empleados', [
            'id' => $this->empleado->id,
            'nombres' => 'Ana María',
            'apellidos' => 'Torres López',
            'telefono' => '7777-8888',
            'email' => 'ana.nueva@example.com',
            'cargo' => 'Supervisora',
            'tipo_contrato' => 'plazo_fijo',
            'fecha_salida' => null,
            'salario_base' => 1875.50,
        ]);
    }

    public function test_email_must_be_unique(): void
    {
        $this->crearEmpleado([
            'codigo' => 'EMP-002',
            'numero_documento' => '00000002-2',
            'email' => 'otro@example.com',
        ]);

        $this->actingAs($this->empleado)
            ->put(route('perfil.update'), $this->datos([
                'email' => 'otro@example.com',
            ]))
            ->assertSessionHasErrors('email');

        $this->assertSame('ana.torres@example.com', $this->empleado->fresh()->email);
    }

    public function test_employee_can_change_password_with_current_password(): void
    {
        $this->actingAs($this->empleado)
            ->put(route('perfil.update'), $this->datos([
                'password_actual' => 'secret-password',
                'password' => 'nueva-clave-segura',
                'password_confirmation' => 'nueva-clave-segura',
            ]))
            ->assertRedirect(route('perfil.edit'))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('nueva-clave-segura', $this->empleado->fresh()->password));
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $this->actingAs($this->empleado)
            ->put(route('perfil.update'), $this->datos([
                'password_actual' => 'incorrecta',
                'password' => 'nueva-clave-segura',
                'password_confirmation' => 'nueva-clave-segura',
            ]))
            ->assertSessionHasErrors('password_actual');

        $this->assertTrue(Hash::check('secret-password', $this->empleado->fresh()->password));
    }

    public function test_profile_photo_can_be_uploaded_replaced_and_removed(): void
    {
        Storage::fake('public');

        $this->actingAs($this->empleado)
            ->put(route('perfil.update'), $this->datos([
                'foto' => UploadedFile::fake()->image('perfil.jpg', 400, 400),
            ]))
            ->assertSessionHasNoErrors();

        $primeraRuta = $this->empleado->fresh()->foto_perfil;

        $this->assertNotNull($primeraRuta);
        Storage::disk('public')->assertExists($primeraRuta);

        $this->actingAs($this->empleado)
            ->put(route('perfil.update'), $this->datos([
                'foto' => UploadedFile::fake()->image('perfil-2.png', 400, 400),
            ]))
            ->assertSessionHasNoErrors();

        $segundaRuta = $this->empleado->fresh()->foto_perfil;

        $this->assertNotSame($primeraRuta, $segundaRuta);
        Storage::disk('public')->assertMissing($primeraRuta);
        Storage::disk('public')->assertExists($segundaRuta);

        $this->actingAs($this->empleado)
            ->put(route('perfil.update'), $this->datos([
                'eliminar_foto' => '1',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertNull($this->empleado->fresh()->foto_perfil);
        Storage::disk('public')->assertMissing($segundaRuta);
    }

    private function datos(array $overrides = []): array
    {
        return array_merge([
            'nombres' => 'Ana',
            'apellidos' => 'Torres',
            'tipo_documento' => 'dui',
            'numero_documento' => '00000001-1',
            'telefono' => '2222-3333',
            'email' => 'ana.torres@example.com',
            'cargo' => 'Gerente',
            'sucursal_id' => $this->sucursalId,
            'tipo_contrato' => 'indefinido',
            'fecha_ingreso' => '2024-01-15',
            'fecha_salida' => '',
            'salario_base' => '1500.50',
        ], $overrides);
    }

    private function crearEmpleado(array $overrides = []): Empleado
    {
        return Empleado::create(array_merge([
            'codigo' => 'EMP-001',
            'nombres' => 'Ana',
            'apellidos' => 'Torres',
            'numero_documento' => '00000001-1',
            'cargo' => 'Gerente',
            'sucursal_id' => $this->sucursalId,
            'fecha_ingreso' => '2024-01-15',
            'email' => 'ana.torres@example.com',
            'password' => 'secret-password',
            'acceso' => 'activo',
        ], $overrides));
    }
}
