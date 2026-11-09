<?php

namespace App\Service\Configuracion;

use App\Service\Auditoria\AuditoriaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ConfiguracionService
{
    private const DISCO = 'public';

    private const DEFAULTS = [
        'nombre_comercial' => 'ViaGest',
        'razon_social' => null,
        'documento_fiscal' => null,
        'email_contacto' => null,
        'telefono' => null,
        'direccion' => null,
        'logo_ruta' => null,
        'zona_horaria' => 'America/El_Salvador',
        'moneda_base' => 'USD',
        'terminos_condiciones' => null,
        'color_primary' => '#0b4f6c',
        'color_secondary' => '#1ca3aa',
        'color_accent' => '#f28f3b',
        'color_background' => '#f5f8fa',
        'color_surface' => '#ffffff',
        'color_ink' => '#1f2933',
    ];

    private static ?object $cache = null;

    public static function actual(): object
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        if (! Schema::hasTable('configuracion_empresa')) {
            return self::$cache = (object) self::DEFAULTS;
        }

        $registro = DB::table('configuracion_empresa')->where('id', 1)->first();
        $datos = array_merge(
            self::DEFAULTS,
            array_filter((array) ($registro ?? []), fn ($valor) => $valor !== null),
        );

        return self::$cache = (object) $datos;
    }

    public static function limpiarCache(): void
    {
        self::$cache = null;
    }

    public static function colores(): array
    {
        $configuracion = self::actual();

        return [
            'primary' => $configuracion->color_primary ?: self::DEFAULTS['color_primary'],
            'secondary' => $configuracion->color_secondary ?: self::DEFAULTS['color_secondary'],
            'accent' => $configuracion->color_accent ?: self::DEFAULTS['color_accent'],
            'background' => $configuracion->color_background ?: self::DEFAULTS['color_background'],
            'surface' => $configuracion->color_surface ?: self::DEFAULTS['color_surface'],
            'ink' => $configuracion->color_ink ?: self::DEFAULTS['color_ink'],
        ];
    }

    public static function logoUrl(?string $ruta = null): ?string
    {
        $ruta ??= self::actual()->logo_ruta ?? null;

        return $ruta ? Storage::disk(self::DISCO)->url($ruta) : null;
    }

    public static function iniciales(?string $nombre = null): string
    {
        $nombre = trim((string) ($nombre ?? (self::actual()->nombre_comercial ?? 'ViaGest')));
        $partes = array_filter(preg_split('/\s+/', $nombre) ?: []);
        $iniciales = '';

        foreach (array_slice($partes, 0, 2) as $parte) {
            $iniciales .= mb_substr($parte, 0, 1);
        }

        return mb_strtoupper($iniciales !== '' ? $iniciales : 'VG');
    }

    public function actualizar(array $datos, ?UploadedFile $logo = null): object
    {
        $anterior = (array) self::actual();
        $eliminarLogo = (bool) ($datos['eliminar_logo'] ?? false);

        unset($datos['logo'], $datos['eliminar_logo']);

        if ($logo) {
            $ruta = $logo->store('configuracion', self::DISCO);

            if (! is_string($ruta)) {
                throw new RuntimeException('No se pudo guardar el logo de la empresa.');
            }

            $this->eliminarArchivo($anterior['logo_ruta'] ?? null);
            $datos['logo_ruta'] = $ruta;
        } elseif ($eliminarLogo) {
            $this->eliminarArchivo($anterior['logo_ruta'] ?? null);
            $datos['logo_ruta'] = null;
        }

        $datos['updated_at'] = now();

        $existe = DB::table('configuracion_empresa')->where('id', 1)->exists();

        if (! $existe) {
            $datos['created_at'] = now();
        }

        DB::table('configuracion_empresa')->updateOrInsert(['id' => 1], $datos);

        self::limpiarCache();

        $nuevo = (array) self::actual();

        AuditoriaService::registrar([
            'empleado_id' => Auth::id(),
            'accion' => 'actualizar',
            'tabla' => 'configuracion_empresa',
            'registro_id' => 1,
            'datos_anteriores' => $anterior,
            'datos_nuevos' => $nuevo,
        ]);

        return self::actual();
    }

    private function eliminarArchivo(?string $ruta): void
    {
        if ($ruta && Storage::disk(self::DISCO)->exists($ruta)) {
            Storage::disk(self::DISCO)->delete($ruta);
        }
    }
}
