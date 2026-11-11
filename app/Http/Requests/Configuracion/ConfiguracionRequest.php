<?php

namespace App\Http\Requests\Configuracion;

use App\Service\Configuracion\ConfiguracionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfiguracionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $campos = [
            'nombre_comercial', 'razon_social', 'documento_fiscal', 'email_contacto',
            'telefono', 'direccion', 'zona_horaria', 'moneda_base', 'terminos_condiciones',
            'color_primary', 'color_secondary', 'color_accent', 'color_background',
            'color_surface', 'color_ink',
        ];

        $datos = [];

        foreach ($campos as $campo) {
            if (! $this->has($campo)) {
                continue;
            }

            $valor = $this->input($campo);
            $datos[$campo] = is_string($valor) ? (trim($valor) !== '' ? trim($valor) : null) : $valor;
        }

        if (isset($datos['email_contacto']) && is_string($datos['email_contacto'])) {
            $datos['email_contacto'] = strtolower($datos['email_contacto']);
        }

        if (isset($datos['moneda_base']) && is_string($datos['moneda_base'])) {
            $datos['moneda_base'] = strtoupper($datos['moneda_base']);
        }

        $datos['eliminar_logo'] = $this->boolean('eliminar_logo');

        $this->merge($datos);
    }

    public function rules(): array
    {
        $monedas = array_keys(ConfiguracionService::MONEDAS);

        return [
            'nombre_comercial' => ['required', 'string', 'max:150'],
            'razon_social' => ['nullable', 'string', 'max:150'],
            'documento_fiscal' => ['nullable', 'string', 'max:30'],
            'email_contacto' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:25'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'zona_horaria' => ['required', 'timezone'],
            'moneda_base' => ['required', 'string', 'size:3', Rule::in($monedas)],
            'terminos_condiciones' => ['nullable', 'string', 'max:10000'],
            'color_primary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'color_secondary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'color_accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'color_background' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'color_surface' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'color_ink' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'eliminar_logo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_comercial.required' => 'El nombre comercial es obligatorio.',
            'email_contacto.email' => 'El correo de contacto debe ser una dirección válida.',
            'zona_horaria.timezone' => 'La zona horaria seleccionada no es válida.',
            'moneda_base.in' => 'La moneda seleccionada no es válida.',
            'color_primary.regex' => 'El color primario debe tener formato hexadecimal (#RRGGBB).',
            'color_secondary.regex' => 'El color secundario debe tener formato hexadecimal (#RRGGBB).',
            'color_accent.regex' => 'El color de acento debe tener formato hexadecimal (#RRGGBB).',
            'color_background.regex' => 'El color de fondo debe tener formato hexadecimal (#RRGGBB).',
            'color_surface.regex' => 'El color de superficie debe tener formato hexadecimal (#RRGGBB).',
            'color_ink.regex' => 'El color de texto debe tener formato hexadecimal (#RRGGBB).',
            'logo.image' => 'El logo debe ser una imagen válida.',
            'logo.mimes' => 'El logo debe ser JPG, PNG o WebP.',
            'logo.max' => 'El logo no puede superar los 2 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre_comercial' => 'nombre comercial',
            'razon_social' => 'razón social',
            'documento_fiscal' => 'documento fiscal',
            'email_contacto' => 'correo de contacto',
            'telefono' => 'teléfono',
            'direccion' => 'dirección',
            'zona_horaria' => 'zona horaria',
            'moneda_base' => 'moneda base',
            'terminos_condiciones' => 'términos y condiciones',
            'color_primary' => 'color primario',
            'color_secondary' => 'color secundario',
            'color_accent' => 'color de acento',
            'color_background' => 'color de fondo',
            'color_surface' => 'color de superficie',
            'color_ink' => 'color de texto',
            'logo' => 'logo',
        ];
    }
}
