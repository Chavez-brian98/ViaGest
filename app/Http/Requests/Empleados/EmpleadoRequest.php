<?php

namespace App\Http\Requests\Empleados;

use App\Models\Empleado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmpleadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $campos = [
            'codigo', 'nombres', 'apellidos', 'tipo_documento', 'numero_documento',
            'telefono', 'email', 'estado', 'cargo', 'sucursal_id', 'tipo_contrato',
            'fecha_ingreso', 'fecha_salida', 'salario_base',
        ];

        $datos = [];

        foreach ($campos as $campo) {
            if (! $this->has($campo)) {
                continue;
            }

            $valor = $this->input($campo);
            $datos[$campo] = is_string($valor) ? (trim($valor) !== '' ? trim($valor) : null) : $valor;
        }

        foreach (['tipo_documento', 'estado'] as $campo) {
            if (isset($datos[$campo]) && is_string($datos[$campo])) {
                $datos[$campo] = strtolower($datos[$campo]);
            }
        }

        if (isset($datos['codigo']) && is_string($datos['codigo'])) {
            $datos['codigo'] = strtoupper($datos['codigo']);
        }

        if (isset($datos['email']) && is_string($datos['email'])) {
            $datos['email'] = strtolower($datos['email']);
        }

        $this->merge($datos);
    }

    public function rules(): array
    {
        $empleado = $this->route('empleado');
        $empleadoId = $empleado instanceof Empleado ? $empleado->getKey() : null;
        $tipoDocumento = $this->input('tipo_documento');
        $tipoDocumento = is_string($tipoDocumento) ? $tipoDocumento : null;

        return [
            'codigo' => [
                'required', 'string', 'max:20',
                Rule::unique('empleados', 'codigo')->ignore($empleadoId),
            ],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'tipo_documento' => [
                'nullable', Rule::in(['dui', 'nit', 'carnet_residente', 'otro']),
                'required_with:numero_documento',
            ],
            'numero_documento' => [
                'nullable', 'string', 'max:30', 'required_with:tipo_documento',
                Rule::unique('empleados', 'numero_documento')
                    ->where(fn ($query) => $query->where('tipo_documento', $tipoDocumento))
                    ->ignore($empleadoId),
            ],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('empleados', 'email')->ignore($empleadoId)],
            'telefono' => ['nullable', 'string', 'max:25'],
            'cargo' => ['nullable', 'string', 'max:100'],
            'sucursal_id' => ['required', 'integer', 'exists:sucursales,id'],
            'tipo_contrato' => ['nullable', 'string', 'max:50'],
            'fecha_ingreso' => ['nullable', 'date', 'before_or_equal:today'],
            'fecha_salida' => ['nullable', 'date', 'after:fecha_ingreso'],
            'salario_base' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['sometimes', 'required', Rule::in(['activo', 'inactivo'])],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.unique' => 'Ya existe un empleado con este código.',
            'numero_documento.unique' => 'Ya existe un empleado con este documento.',
            'email.unique' => 'Este correo electrónico ya está asignado a otro empleado.',
        ];
    }

    public function attributes(): array
    {
        return [
            'codigo' => 'código',
            'nombres' => 'nombres',
            'apellidos' => 'apellidos',
            'tipo_documento' => 'tipo de documento',
            'numero_documento' => 'número de documento',
            'email' => 'correo electrónico',
            'telefono' => 'teléfono',
            'cargo' => 'cargo',
            'sucursal_id' => 'sucursal',
            'tipo_contrato' => 'tipo de contrato',
            'fecha_ingreso' => 'fecha de ingreso',
            'fecha_salida' => 'fecha de salida',
            'salario_base' => 'salario base',
            'estado' => 'estado',
        ];
    }
}
