<?php

namespace App\Http\Requests\Proveedores;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProveedorRequest extends FormRequest
{
    private const TIPOS = ['aerolinea', 'hospedaje', 'transporte', 'guia', 'operador', 'otro'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $campos = [
            'codigo', 'nombre', 'razon_social', 'documento_fiscal',
            'contacto_nombre', 'email', 'telefono', 'notas',
        ];

        $datos = [];

        foreach ($campos as $campo) {
            if (! $this->has($campo)) {
                continue;
            }

            $valor = $this->input($campo);
            $datos[$campo] = is_string($valor) ? (trim($valor) !== '' ? trim($valor) : null) : $valor;
        }

        foreach (['tipo', 'estado'] as $campo) {
            if ($this->filled($campo)) {
                $datos[$campo] = strtolower(trim((string) $this->input($campo)));
            }
        }

        foreach (['codigo', 'pais_codigo'] as $campo) {
            if ($this->filled($campo)) {
                $datos[$campo] = strtoupper(trim((string) $this->input($campo)));
            }
        }

        if (isset($datos['email']) && is_string($datos['email'])) {
            $datos['email'] = strtolower($datos['email']);
        }

        $datos['dias_credito'] = $this->filled('dias_credito')
            ? (int) $this->input('dias_credito')
            : 0;

        $this->merge($datos);
    }

    public function rules(): array
    {
        $proveedorId = $this->route('proveedor');

        return [
            'codigo' => [
                'required', 'string', 'max:20',
                Rule::unique('proveedores', 'codigo')->ignore($proveedorId),
            ],
            'tipo' => ['required', Rule::in(self::TIPOS)],
            'nombre' => ['required', 'string', 'max:150'],
            'razon_social' => ['nullable', 'string', 'max:150'],
            'documento_fiscal' => [
                'nullable', 'string', 'max:30',
                Rule::unique('proveedores', 'documento_fiscal')->ignore($proveedorId),
            ],
            'pais_codigo' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'contacto_nombre' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:25'],
            'dias_credito' => ['required', 'integer', 'min:0', 'max:365'],
            'notas' => ['nullable', 'string', 'max:2000'],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.unique' => 'Ya existe un proveedor con este código.',
            'documento_fiscal.unique' => 'Ya existe un proveedor con este documento fiscal.',
            'pais_codigo.regex' => 'El país debe ser un código ISO de dos letras.',
        ];
    }

    public function attributes(): array
    {
        return [
            'codigo' => 'código',
            'tipo' => 'tipo de proveedor',
            'nombre' => 'nombre',
            'razon_social' => 'razón social',
            'documento_fiscal' => 'documento fiscal',
            'pais_codigo' => 'país',
            'contacto_nombre' => 'persona de contacto',
            'email' => 'correo electrónico',
            'telefono' => 'teléfono',
            'dias_credito' => 'días de crédito',
            'notas' => 'notas',
            'estado' => 'estado',
        ];
    }
}
