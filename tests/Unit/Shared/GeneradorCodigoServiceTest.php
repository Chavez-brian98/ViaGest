<?php

namespace Tests\Unit\Shared;

use App\Models\Proveedor;
use App\Service\Shared\GeneradorCodigoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GeneradorCodigoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_genera_primer_codigo_cuando_tabla_esta_vacia(): void
    {
        $codigo = GeneradorCodigoService::siguiente(Proveedor::class, 'PROV-');
        $this->assertEquals('PROV-0001', $codigo);
    }

    public function test_genera_siguiente_codigo_secuencial(): void
    {
        DB::table('proveedores')->insert([
            'codigo' => 'PROV-0001',
            'nombre' => 'Test 1',
            'tipo' => 'operador',
            'pais_codigo' => 'SV',
            'dias_credito' => 0,
            'estado' => 'activo',
        ]);

        DB::table('proveedores')->insert([
            'codigo' => 'PROV-0002',
            'nombre' => 'Test 2',
            'tipo' => 'operador',
            'pais_codigo' => 'SV',
            'dias_credito' => 0,
            'estado' => 'activo',
        ]);

        $codigo = GeneradorCodigoService::siguiente(Proveedor::class, 'PROV-');
        $this->assertEquals('PROV-0003', $codigo);
    }

    public function test_evita_colision_si_el_codigo_ya_existe(): void
    {
        DB::table('proveedores')->insert([
            'codigo' => 'PROV-0005',
            'nombre' => 'Test 5',
            'tipo' => 'operador',
            'pais_codigo' => 'SV',
            'dias_credito' => 0,
            'estado' => 'activo',
        ]);

        $codigo = GeneradorCodigoService::siguiente(Proveedor::class, 'PROV-');
        $this->assertEquals('PROV-0006', $codigo);
    }
}
