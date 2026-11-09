<?php

namespace Tests\Feature\Paquetes;

use App\Models\Empleado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaqueteTest extends TestCase
{
    use RefreshDatabase;

    private Empleado $empleado;

    private int $servicioId1;

    private int $servicioId2;

    private int $ciudadId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->ciudadId = DB::table('ciudades')->insertGetId([
            'pais_codigo' => 'HN',
            'nombre' => 'Roatán',
        ]);

        $sucursalId = DB::table('sucursales')->insertGetId([
            'codigo' => 'MAT-001',
            'nombre' => 'Matriz',
            'tipo' => 'matriz',
            'ciudad_id' => $this->ciudadId,
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
            'tipo' => 'hospedaje',
            'nombre' => 'Resort Caribe',
            'pais_codigo' => 'HN',
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->servicioId1 = DB::table('servicios')->insertGetId([
            'codigo' => 'SRV-001',
            'tipo' => 'hospedaje',
            'nombre' => 'Estadía Todo Incluido 3N',
            'proveedor_id' => $proveedorId,
            'costo_neto' => 300.00,
            'unidad_cobro' => 'por_persona',
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->servicioId2 = DB::table('servicios')->insertGetId([
            'codigo' => 'SRV-002',
            'tipo' => 'actividad',
            'nombre' => 'Snorkeling en Arrecife',
            'proveedor_id' => $proveedorId,
            'costo_neto' => 50.00,
            'unidad_cobro' => 'por_persona',
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_lista_paquetes(): void
    {
        DB::table('paquetes')->insert([
            'codigo' => 'PAQ-0001',
            'nombre' => 'Escapada a Roatán',
            'tipo' => 'internacional',
            'ciudad_destino_id' => $this->ciudadId,
            'duracion_dias' => 4,
            'duracion_noches' => 3,
            'margen_tipo' => 'porcentaje',
            'margen_valor' => 20,
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->empleado)
            ->get(route('paquetes.index'));

        $response->assertOk();
        $response->assertSee('Escapada a Roatán');
        $response->assertSee('PAQ-0001');
    }

    public function test_crear_paquete_con_servicios(): void
    {
        $payload = [
            'codigo' => 'PAQ-0002',
            'nombre' => 'Paquete Sol y Playa',
            'descripcion' => 'Incluye hotel y actividad acuática',
            'tipo' => 'internacional',
            'ciudad_destino_id' => $this->ciudadId,
            'duracion_dias' => 4,
            'duracion_noches' => 3,
            'margen_tipo' => 'porcentaje',
            'margen_valor' => 25,
            'estado' => 'activo',
            'servicios' => [
                [
                    'servicio_id' => $this->servicioId1,
                    'dia_inicio' => 1,
                    'cantidad' => 1,
                ],
                [
                    'servicio_id' => $this->servicioId2,
                    'dia_inicio' => 2,
                    'cantidad' => 2,
                ],
            ],
        ];

        $response = $this->actingAs($this->empleado)
            ->post(route('paquetes.store'), $payload);

        $response->assertRedirect(route('paquetes.index'));

        $this->assertDatabaseHas('paquetes', [
            'codigo' => 'PAQ-0002',
            'nombre' => 'Paquete Sol y Playa',
            'estado' => 'activo',
        ]);

        $paqueteId = DB::table('paquetes')->where('codigo', 'PAQ-0002')->value('id');

        $this->assertDatabaseHas('paquete_servicios', [
            'paquete_id' => $paqueteId,
            'servicio_id' => $this->servicioId1,
            'dia_inicio' => 1,
        ]);

        $this->assertDatabaseHas('paquete_servicios', [
            'paquete_id' => $paqueteId,
            'servicio_id' => $this->servicioId2,
            'dia_inicio' => 2,
        ]);

        $this->assertDatabaseHas('bitacora_auditoria', [
            'tabla' => 'paquetes',
            'accion' => 'crear',
            'empleado_id' => $this->empleado->id,
        ]);
    }

    public function test_validaciones_al_crear_paquete(): void
    {
        $response = $this->actingAs($this->empleado)
            ->post(route('paquetes.store'), [
                'codigo' => '',
                'nombre' => '',
                'duracion_dias' => 0,
            ]);

        $response->assertSessionHasErrors(['codigo', 'nombre', 'tipo', 'duracion_dias', 'duracion_noches', 'margen_tipo', 'margen_valor']);
    }

    public function test_actualizar_paquete_y_sus_servicios(): void
    {
        $paqueteId = DB::table('paquetes')->insertGetId([
            'codigo' => 'PAQ-0003',
            'nombre' => 'Paquete Inicial',
            'tipo' => 'nacional',
            'duracion_dias' => 2,
            'duracion_noches' => 1,
            'margen_tipo' => 'monto_fijo',
            'margen_valor' => 50,
            'estado' => 'borrador',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->empleado)
            ->put(route('paquetes.update', $paqueteId), [
                'codigo' => 'PAQ-0003',
                'nombre' => 'Paquete Modificado',
                'tipo' => 'internacional',
                'duracion_dias' => 3,
                'duracion_noches' => 2,
                'margen_tipo' => 'porcentaje',
                'margen_valor' => 15,
                'estado' => 'activo',
                'servicios' => [
                    [
                        'servicio_id' => $this->servicioId1,
                        'dia_inicio' => 1,
                        'cantidad' => 1,
                    ],
                ],
            ]);

        $response->assertRedirect(route('paquetes.index'));

        $this->assertDatabaseHas('paquetes', [
            'id' => $paqueteId,
            'nombre' => 'Paquete Modificado',
            'tipo' => 'internacional',
        ]);

        $this->assertDatabaseHas('paquete_servicios', [
            'paquete_id' => $paqueteId,
            'servicio_id' => $this->servicioId1,
        ]);
    }

    public function test_cambiar_estado_paquete(): void
    {
        $paqueteId = DB::table('paquetes')->insertGetId([
            'codigo' => 'PAQ-0004',
            'nombre' => 'Paquete Con Servicio',
            'tipo' => 'nacional',
            'duracion_dias' => 2,
            'duracion_noches' => 1,
            'margen_tipo' => 'porcentaje',
            'margen_valor' => 10,
            'estado' => 'borrador',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('paquete_servicios')->insert([
            'paquete_id' => $paqueteId,
            'servicio_id' => $this->servicioId1,
            'dia_inicio' => 1,
            'cantidad' => 1,
        ]);

        $response = $this->actingAs($this->empleado)
            ->patchJson(route('paquetes.estado', $paqueteId), [
                'estado_destino' => 'activo',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('paquetes', [
            'id' => $paqueteId,
            'estado' => 'activo',
        ]);
    }

    public function test_eliminar_paquete_sin_ventas(): void
    {
        $paqueteId = DB::table('paquetes')->insertGetId([
            'codigo' => 'PAQ-0005',
            'nombre' => 'Paquete a Eliminar',
            'tipo' => 'nacional',
            'duracion_dias' => 1,
            'duracion_noches' => 0,
            'margen_tipo' => 'porcentaje',
            'margen_valor' => 10,
            'estado' => 'borrador',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->empleado)
            ->delete(route('paquetes.destroy', $paqueteId));

        $response->assertRedirect(route('paquetes.index'));
        $this->assertDatabaseMissing('paquetes', ['id' => $paqueteId]);
    }
}
