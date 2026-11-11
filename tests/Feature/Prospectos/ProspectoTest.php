<?php

namespace Tests\Feature\Prospectos;

use App\Models\Cliente;
use App\Models\Empleado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProspectoTest extends TestCase
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
            'nombre' => 'Sucursal Central',
            'tipo' => 'matriz',
            'ciudad_id' => $ciudadId,
        ]);

        $rolId = DB::table('roles')->insertGetId([
            'nombre' => 'administrador',
            'alcance' => 'todas_sucursales',
        ]);

        $this->empleado = Empleado::create([
            'codigo' => 'EMP-001',
            'nombres' => 'Carlos',
            'apellidos' => 'Vendedor',
            'numero_documento' => '00000001-1',
            'cargo' => 'Asesor de Viajes',
            'sucursal_id' => $this->sucursalId,
            'fecha_ingreso' => now()->toDateString(),
            'email' => 'carlos.vendedor@example.com',
            'password' => 'secret-password',
            'rol_id' => $rolId,
            'acceso' => 'activo',
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('prospectos.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_list_prospects(): void
    {
        $this->crearProspecto(['codigo' => 'LEAD-0001', 'nombres' => 'Juan', 'apellidos' => 'Pérez']);
        $this->crearProspecto(['codigo' => 'LEAD-0002', 'nombres' => 'María', 'apellidos' => 'Gómez']);

        $response = $this->actingAs($this->empleado)->get(route('prospectos.index'));

        $response->assertOk();
        $response->assertSee('LEAD-0001');
        $response->assertSee('Juan');
        $response->assertSee('LEAD-0002');
        $response->assertSee('María');
    }

    public function test_prospects_can_be_filtered_by_search_and_state(): void
    {
        $this->crearProspecto(['codigo' => 'LEAD-0001', 'nombres' => 'Pedro', 'estado' => 'nuevo']);
        $this->crearProspecto(['codigo' => 'LEAD-0002', 'nombres' => 'Lucía', 'estado' => 'cotizado']);

        $response = $this->actingAs($this->empleado)->get(route('prospectos.index', ['buscar' => 'Pedro']));
        $response->assertSee('Pedro');
        $response->assertDontSee('Lucía');

        $responseFiltro = $this->actingAs($this->empleado)->get(route('prospectos.index', ['estado' => 'cotizado']));
        $responseFiltro->assertSee('Lucía');
        $responseFiltro->assertDontSee('Pedro');
    }

    public function test_can_view_create_prospect_form(): void
    {
        $response = $this->actingAs($this->empleado)->get(route('prospectos.create'));

        $response->assertOk();
        $response->assertSee('Nuevo Prospecto');
    }

    public function test_can_create_prospect_successfully(): void
    {
        $datos = [
            'codigo' => 'LEAD-9999',
            'nombres' => 'Roberto',
            'apellidos' => 'Alvarado',
            'email' => 'roberto@example.com',
            'telefono' => '7111-2222',
            'pais_destino' => 'ES',
            'numero_viajeros' => 2,
            'origen' => 'redes_sociales',
            'estado' => 'nuevo',
            'proximo_seguimiento' => now()->addDays(3)->format('Y-m-d'),
            'vendedor_id' => $this->empleado->id,
            'sucursal_id' => $this->sucursalId,
            'notas' => 'Interesado en tour por Madrid y Barcelona',
        ];

        $response = $this->actingAs($this->empleado)->post(route('prospectos.store'), $datos);

        $response->assertRedirect(route('prospectos.index'));
        $this->assertDatabaseHas('prospectos', [
            'codigo' => 'LEAD-9999',
            'nombres' => 'Roberto',
            'pais_destino' => 'ES',
        ]);
        $this->assertDatabaseHas('bitacora_auditoria', [
            'tabla' => 'prospectos',
            'accion' => 'crear',
        ]);
    }

    public function test_validation_fails_when_creating_prospect_with_invalid_data(): void
    {
        $response = $this->actingAs($this->empleado)->post(route('prospectos.store'), [
            'codigo' => '',
            'nombres' => '',
            'vendedor_id' => 9999,
        ]);

        $response->assertSessionHasErrors(['codigo', 'nombres', 'vendedor_id', 'sucursal_id', 'origen', 'estado']);
    }

    public function test_can_view_edit_prospect_form(): void
    {
        $id = $this->crearProspecto(['codigo' => 'LEAD-0010', 'nombres' => 'Camila']);

        $response = $this->actingAs($this->empleado)->get(route('prospectos.edit', $id));

        $response->assertOk();
        $response->assertSee('Camila');
        $response->assertSee('LEAD-0010');
    }

    public function test_can_update_prospect(): void
    {
        $id = $this->crearProspecto(['codigo' => 'LEAD-0020', 'nombres' => 'Andrea', 'estado' => 'nuevo']);

        $datosActualizados = [
            'codigo' => 'LEAD-0020',
            'nombres' => 'Andrea Nicole',
            'apellidos' => 'Rivas',
            'email' => 'andrea.rivas@example.com',
            'telefono' => '7222-3333',
            'pais_destino' => 'FR',
            'numero_viajeros' => 3,
            'origen' => 'sitio_web',
            'estado' => 'contactado',
            'vendedor_id' => $this->empleado->id,
            'sucursal_id' => $this->sucursalId,
            'notas' => 'Actualizado: paquete París romántico',
        ];

        $response = $this->actingAs($this->empleado)->put(route('prospectos.update', $id), $datosActualizados);

        $response->assertRedirect(route('prospectos.index'));
        $this->assertDatabaseHas('prospectos', [
            'id' => $id,
            'nombres' => 'Andrea Nicole',
            'pais_destino' => 'FR',
            'estado' => 'contactado',
        ]);
        $this->assertDatabaseHas('bitacora_auditoria', [
            'tabla' => 'prospectos',
            'registro_id' => $id,
            'accion' => 'actualizar',
        ]);
    }

    public function test_can_update_prospect_state_via_patch(): void
    {
        $id = $this->crearProspecto(['codigo' => 'LEAD-0030', 'estado' => 'nuevo']);

        $response = $this->actingAs($this->empleado)->patchJson(route('prospectos.estado', $id), [
            'estado_destino' => 'cotizado',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'estado' => 'cotizado',
        ]);

        $this->assertDatabaseHas('prospectos', [
            'id' => $id,
            'estado' => 'cotizado',
        ]);
    }

    public function test_marking_as_lost_requires_lost_reason(): void
    {
        $id = $this->crearProspecto(['codigo' => 'LEAD-0031', 'estado' => 'nuevo']);

        $response = $this->actingAs($this->empleado)->patchJson(route('prospectos.estado', $id), [
            'estado_destino' => 'perdido',
        ]);

        $response->assertStatus(422);

        $responseOk = $this->actingAs($this->empleado)->patchJson(route('prospectos.estado', $id), [
            'estado_destino' => 'perdido',
            'motivo_perdida' => 'precio',
        ]);

        $responseOk->assertOk();
        $this->assertDatabaseHas('prospectos', [
            'id' => $id,
            'estado' => 'perdido',
            'motivo_perdida' => 'precio',
        ]);
    }

    public function test_can_convert_prospect_to_client(): void
    {
        $id = $this->crearProspecto([
            'codigo' => 'LEAD-0040',
            'nombres' => 'Sofía',
            'apellidos' => 'Martínez',
            'email' => 'sofia@example.com',
            'telefono' => '7333-4444',
            'estado' => 'cotizado',
        ]);

        $response = $this->actingAs($this->empleado)->postJson(route('prospectos.convertir', $id), [
            'tipo_documento' => 'dui',
            'numero_documento' => '05432109-8',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // Verificar que el cliente existe con los datos del prospecto
        $this->assertDatabaseHas('clientes', [
            'nombres' => 'Sofía',
            'apellidos' => 'Martínez',
            'email' => 'sofia@example.com',
            'tipo_documento' => 'dui',
            'numero_documento' => '05432109-8',
        ]);

        $cliente = Cliente::query()->where('email', 'sofia@example.com')->first();
        $this->assertNotNull($cliente);

        // Verificar que el prospecto quedó como convertido y enlazado al cliente
        $this->assertDatabaseHas('prospectos', [
            'id' => $id,
            'estado' => 'convertido',
            'cliente_id' => $cliente->id,
        ]);
    }

    public function test_cannot_convert_already_converted_prospect(): void
    {
        $id = $this->crearProspecto(['codigo' => 'LEAD-0050', 'estado' => 'nuevo']);

        // Primera conversión
        $this->actingAs($this->empleado)->postJson(route('prospectos.convertir', $id))->assertOk();

        // Segunda conversión debe fallar
        $segunda = $this->actingAs($this->empleado)->postJson(route('prospectos.convertir', $id));
        $segunda->assertStatus(422);
    }

    public function test_active_or_new_prospect_cannot_be_deleted_directly(): void
    {
        $id = $this->crearProspecto(['codigo' => 'LEAD-0060', 'estado' => 'nuevo']);

        $response = $this->actingAs($this->empleado)->delete(route('prospectos.destroy', $id));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('prospectos', ['id' => $id]);
    }

    public function test_lost_prospect_can_be_deleted(): void
    {
        $id = $this->crearProspecto(['codigo' => 'LEAD-0070', 'estado' => 'perdido', 'motivo_perdida' => 'sin_respuesta']);

        $response = $this->actingAs($this->empleado)->delete(route('prospectos.destroy', $id));
        $response->assertRedirect(route('prospectos.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('prospectos', ['id' => $id]);
        $this->assertDatabaseHas('bitacora_auditoria', [
            'tabla' => 'prospectos',
            'registro_id' => $id,
            'accion' => 'eliminar',
        ]);
    }

    private function crearProspecto(array $overrides = []): int
    {
        return DB::table('prospectos')->insertGetId(array_merge([
            'codigo' => 'LEAD-0001',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'email' => 'juan.perez@example.com',
            'telefono' => '7000-1111',
            'pais_destino' => 'ES',
            'fecha_viaje_estimada' => now()->addMonth()->toDateString(),
            'numero_viajeros' => 2,
            'origen' => 'redes_sociales',
            'estado' => 'nuevo',
            'motivo_perdida' => null,
            'proximo_seguimiento' => now()->addDays(2)->toDateString(),
            'vendedor_id' => $this->empleado->id,
            'sucursal_id' => $this->sucursalId,
            'cliente_id' => null,
            'notas' => 'Nota de prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
