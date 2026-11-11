<?php

namespace Tests\Unit\Shared;

use App\Service\Shared\GestorArchivosService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GestorArchivosServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_eliminar_archivo_existente(): void
    {
        Storage::disk('public')->put('test.png', 'contenido');
        $this->assertTrue(Storage::disk('public')->exists('test.png'));

        GestorArchivosService::eliminar('test.png');
        $this->assertFalse(Storage::disk('public')->exists('test.png'));
    }

    public function test_eliminar_con_ruta_nula_no_falla(): void
    {
        GestorArchivosService::eliminar(null);
        $this->assertTrue(true);
    }

    public function test_reemplazar_con_nuevo_archivo(): void
    {
        Storage::disk('public')->put('anterior.png', 'contenido anterior');
        $archivo = UploadedFile::fake()->image('nuevo.png');

        $nuevaRuta = GestorArchivosService::reemplazar($archivo, 'anterior.png', 'fotos');

        $this->assertNotNull($nuevaRuta);
        $this->assertTrue(Storage::disk('public')->exists($nuevaRuta));
        $this->assertFalse(Storage::disk('public')->exists('anterior.png'));
    }

    public function test_reemplazar_con_bandera_eliminar(): void
    {
        Storage::disk('public')->put('anterior.png', 'contenido anterior');

        $resultado = GestorArchivosService::reemplazar(null, 'anterior.png', 'fotos', true);

        $this->assertNull($resultado);
        $this->assertFalse(Storage::disk('public')->exists('anterior.png'));
    }
}
