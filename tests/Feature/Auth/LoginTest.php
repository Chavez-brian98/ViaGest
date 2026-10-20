<?php

namespace Tests\Feature\Auth;

use App\Models\Empleado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Iniciar sesión')
            ->assertSee(route('login.store'), false);
    }

    public function test_active_employee_can_authenticate(): void
    {
        $empleado = $this->createEmpleado();

        $response = $this->from('/login')->post('/login', [
            'email' => $empleado->email,
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($empleado);
        $this->assertNotNull($empleado->fresh()?->ultimo_acceso_at);
    }

    public function test_employee_without_active_access_cannot_authenticate(): void
    {
        $empleado = $this->createEmpleado(['acceso' => 'bloqueado']);

        $response = $this->from('/login')->post('/login', [
            'email' => $empleado->email,
            'password' => 'secret-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    private function createEmpleado(array $overrides = []): Empleado
    {
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

        return Empleado::create(array_merge([
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
        ], $overrides));
    }
}
