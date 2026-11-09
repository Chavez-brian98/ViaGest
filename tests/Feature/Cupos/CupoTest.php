<?php

namespace Tests\Feature\Cupos;

use App\Models\Empleado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CupoTest extends TestCase
{
    use RefreshDatabase;

    private Empleado $empleado;

    private int $servicioId;

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
            'nombres' => 'Admin',
            'apellidos' => 'User',
            'numero_documento' => '00000001-1',
            'cargo' => 'Gerente',
            'sucursal_id' => $sucursalId,
            'fecha_ingreso' => now()->toDateString(),
            'email' => 'admin@viagest.com',
            'password' => 'secret-password',
            'rol_id' => $rolId,
            'acceso' => 'activo',
        ]);

        $proveedorId = DB::table('proveedores')->insertGetId([
            'codigo' => 'PROV-001',
            'tipo' => 'aerolinea',
            'nombre' => 'Aerolínea Global',
            'pais_codigo' => 'SV',
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->servicioId = DB::table('servicios')->insertGetId([
            'codigo' => 'SRV-001',
            'tipo' => 'vuelo',
            'nombre' => 'Vuelo San Salvador - Miami',
            'proveedor_id' => $proveedorId,
            'costo_neto' => 350.00,
            'unidad_cobro' => 'por_persona',
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_lista_cupos(): void
    {
        DB::table('cupos')->insert([
            'servicio_id' => $this->servicioId,
            'tipo' => 'bloqueo',
            'fecha_inicio' => now()->addDays(5)->toDateString(),
            'fecha_fin' => now()->addDays(10)->toDateString(),
            'cantidad_total' => 20,
            'fecha_liberacion' => now()->addDays(2)->toDateString(),
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->empleado)
            ->get(route('cupos.index'));

        $response->assertOk();
        $response->assertSee('Vuelo San Salvador - Miami');
        $response->assertSee('20');
    }

    public function test_crear_cupo_exitoso(): void
    {
        $payload = [
            'servicio_id' => $this->servicioId,
            'tipo' => 'bloqueo',
            'fecha_inicio' => now()->addDays(5)->toDateString(),
            'fecha_fin' => now()->addDays(12)->toDateString(),
            'cantidad_total' => 15,
            'fecha_liberacion' => now()->addDays(1)->toDateString(),
            'estado' => 'activo',
        ];

        $response = $this->actingAs($this->empleado)
            ->post(route('cupos.store'), $payload);

        $response->assertRedirect(route('cupos.index'));
        $this->assertDatabaseHas('cupos', [
            'servicio_id' => $this->servicioId,
            'tipo' => 'bloqueo',
            'cantidad_total' => 15,
            'estado' => 'activo',
        ]);

        $this->assertDatabaseHas('bitacora_auditoria', [
            'tabla' => 'cupos',
            'accion' => 'crear',
            'empleado_id' => $this->empleado->id,
        ]);
    }

    public function test_validaciones_al_crear_cupo(): void
    {
        $response = $this->actingAs($this->empleado)
            ->post(route('cupos.store'), [
                'servicio_id' => null,
                'cantidad_total' => 0,
                'fecha_inicio' => 'invalida',
            ]);

        $response->assertSessionHasErrors(['servicio_id', 'cantidad_total', 'fecha_inicio', 'tipo', 'fecha_fin']);
    }

    public function test_actualizar_cupo(): void
    {
        $cupoId = DB::table('cupos')->insertGetId([
            'servicio_id' => $this->servicioId,
            'tipo' => 'bloqueo',
            'fecha_inicio' => now()->addDays(5)->toDateString(),
            'fecha_fin' => now()->addDays(10)->toDateString(),
            'cantidad_total' => 20,
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->empleado)
            ->put(route('cupos.update', $cupoId), [
                'servicio_id' => $this->servicioId,
                'tipo' => 'compra_adelantada',
                'fecha_inicio' => now()->addDays(5)->toDateString(),
                'fecha_fin' => now()->addDays(10)->toDateString(),
                'cantidad_total' => 25,
                'estado' => 'activo',
            ]);

        $response->assertRedirect(route('cupos.index'));
        $this->assertDatabaseHas('cupos', [
            'id' => $cupoId,
            'tipo' => 'compra_adelantada',
            'cantidad_total' => 25,
        ]);
    }

    public function test_cambiar_estado_cupo_via_json(): void
    {
        $cupoId = DB::table('cupos')->insertGetId([
            'servicio_id' => $this->servicioId,
            'tipo' => 'bloqueo',
            'fecha_inicio' => now()->addDays(5)->toDateString(),
            'fecha_fin' => now()->addDays(10)->toDateString(),
            'cantidad_total' => 20,
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->empleado)
            ->patchJson(route('cupos.estado', $cupoId), [
                'estado_destino' => 'liberado',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('cupos', [
            'id' => $cupoId,
            'estado' => 'liberado',
        ]);
    }

    public function test_eliminar_cupo_sin_ventas(): void
    {
        $cupoId = DB::table('cupos')->insertGetId([
            'servicio_id' => $this->servicioId,
            'tipo' => 'bloqueo',
            'fecha_inicio' => now()->addDays(5)->toDateString(),
            'fecha_fin' => now()->addDays(10)->toDateString(),
            'cantidad_total' => 20,
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->empleado)
            ->delete(route('cupos.destroy', $cupoId));

        $response->assertRedirect(route('cupos.index'));
        $this->assertDatabaseMissing('cupos', ['id' => $cupoId]);
    }
}
