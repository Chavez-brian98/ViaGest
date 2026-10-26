<?php

namespace App\Http\Requests\Clientes;

use App\Models\Cliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $campos = [
            'codigo', 'nombres', 'apellidos', 'razon_social', 'tipo_documento',
            'numero_documento', 'nacionalidad_pais', 'email', 'telefono',             'direccion', 'estado',
            'pasaporte_numero', 'pasaporte_pais', 'preferencias_viaje',
        ];

        $datos = [];

        foreach ($campos as $campo) {
            if (! $this->has($campo)) {
                continue;
            }

            $valor = $this->input($campo);
            $datos[$campo] = is_string($valor) ? (trim($valor) !== '' ? trim($valor) : null) : $valor;
        }

        foreach (['tipo', 'tipo_documento', 'estado'] as $campo) {
            if (isset($datos[$campo]) && is_string($datos[$campo])) {
                $datos[$campo] = strtolower($datos[$campo]);
            }
        }

        if (isset($datos['codigo']) && is_string($datos['codigo'])) {
            $datos['codigo'] = strtoupper($datos['codigo']);
        }

        foreach (['nacionalidad_pais', 'pasaporte_pais'] as $campo) {
            if (isset($datos[$campo]) && is_string($datos[$campo])) {
                $datos[$campo] = strtoupper($datos[$campo]);
            }
        }

        if (isset($datos['email']) && is_string($datos['email'])) {
            $datos['email'] = strtolower($datos['email']);
        }

        $this->merge($datos);
    }

    public function rules(): array
    {
        $cliente = $this->route('cliente');
        $clienteId = $cliente instanceof Cliente ? $cliente->getKey() : null;
        $tipoDocumento = $this->input('tipo_documento');
        $tipoDocumento = is_string($tipoDocumento) ? $tipoDocumento : null;
        $pasaportePais = $this->input('pasaporte_pais');
        $pasaportePais = is_string($pasaportePais) ? $pasaportePais : null;

        return [
            'codigo' => [
                'required', 'string', 'max:20',
                Rule::unique('clientes', 'codigo')->ignore($clienteId),
            ],
            'tipo' => ['required', Rule::in(['persona', 'empresa'])],
            'nombres' => ['nullable', 'string', 'max:100', 'required_if:tipo,persona'],
            'apellidos' => ['nullable', 'string', 'max:100'],
            'razon_social' => ['nullable', 'string', 'max:150', 'required_if:tipo,empresa'],
            'tipo_documento' => [
                'nullable', Rule::in(['dui', 'nit', 'carnet_residente', 'otro']),
                'required_with:numero_documento',
            ],
            'numero_documento' => [
                'nullable', 'string', 'max:30', 'required_with:tipo_documento',
                Rule::unique('clientes', 'numero_documento')
                    ->where(fn ($query) => $query->where('tipo_documento', $tipoDocumento))
                    ->ignore($clienteId),
            ],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'nacionalidad_pais' => ['nullable', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:25'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'pasaporte_numero' => [
                'nullable', 'string', 'max:25', 'required_with:pasaporte_pais,pasaporte_vence',
                Rule::unique('clientes', 'pasaporte_numero')
                    ->where(fn ($query) => $query->where('pasaporte_pais', $pasaportePais))
                    ->ignore($clienteId),
            ],
            'pasaporte_pais' => [
                'nullable', 'string', 'size:2', 'regex:/^[A-Z]{2}$/',
                'required_with:pasaporte_numero,pasaporte_vence',
            ],
            'pasaporte_vence' => ['nullable', 'date'],
            'preferencias_viaje' => ['nullable', 'string', 'max:2000'],
            'sucursal_id' => ['required', 'integer', 'exists:sucursales,id'],
            'estado' => ['sometimes', 'required', Rule::in(['activo', 'inactivo'])],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.unique' => 'Ya existe un cliente con este código.',
            'numero_documento.unique' => 'Ya existe un cliente con este documento.',
            'pasaporte_numero.unique' => 'Ya existe un cliente con este pasaporte.',
            'nacionalidad_pais.regex' => 'La nacionalidad debe ser un código ISO de dos letras.',
            'pasaporte_pais.regex' => 'El país del pasaporte debe ser un código ISO de dos letras.',
        ];
    }

    public function attributes(): array
    {
        return [
            'codigo' => 'código',
            'tipo' => 'tipo de cliente',
            'nombres' => 'nombres',
            'apellidos' => 'apellidos',
            'razon_social' => 'razón social',
            'tipo_documento' => 'tipo de documento',
            'numero_documento' => 'número de documento',
            'fecha_nacimiento' => 'fecha de nacimiento',
            'nacionalidad_pais' => 'nacionalidad',
            'email' => 'correo electrónico',
            'telefono' => 'teléfono',
            'direccion' => 'dirección',
            'pasaporte_numero' => 'número de pasaporte',
            'pasaporte_pais' => 'país del pasaporte',
            'pasaporte_vence' => 'vencimiento del pasaporte',
            'preferencias_viaje' => 'preferencias de viaje',
            'sucursal_id' => 'sucursal',
            'estado' => 'estado',
        ];
    }
}
