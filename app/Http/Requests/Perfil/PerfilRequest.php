<?php

namespace App\Http\Requests\Perfil;

use App\Models\Empleado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $campos = [
            'nombres', 'apellidos', 'tipo_documento', 'numero_documento', 'telefono',
            'email', 'cargo', 'tipo_contrato', 'fecha_ingreso', 'fecha_salida', 'salario_base',
        ];

        $datos = [];

        foreach ($campos as $campo) {
            if (! $this->has($campo)) {
                continue;
            }

            $valor = $this->input($campo);
            $datos[$campo] = is_string($valor) ? (trim($valor) !== '' ? trim($valor) : null) : $valor;
        }

        foreach (['tipo_documento', 'tipo_contrato'] as $campo) {
            if (isset($datos[$campo]) && is_string($datos[$campo])) {
                $datos[$campo] = strtolower($datos[$campo]);
            }
        }

        if (isset($datos['email']) && is_string($datos['email'])) {
            $datos['email'] = strtolower($datos['email']);
        }

        $datos['eliminar_foto'] = $this->boolean('eliminar_foto');

        $this->merge($datos);
    }

    public function rules(): array
    {
        $empleado = $this->user();
        $empleadoId = $empleado instanceof Empleado ? $empleado->getKey() : null;
        $tipoDocumento = $this->input('tipo_documento');
        $tipoDocumento = is_string($tipoDocumento) ? $tipoDocumento : null;

        return [
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'tipo_documento' => ['required', Rule::in(['dui', 'pasaporte', 'carnet_residente', 'otro'])],
            'numero_documento' => [
                'required', 'string', 'max:30',
                Rule::unique('empleados', 'numero_documento')
                    ->where(fn ($query) => $query->where('tipo_documento', $tipoDocumento))
                    ->ignore($empleadoId),
            ],
            'telefono' => ['nullable', 'string', 'max:25'],
            'email' => [
                'required', 'email', 'max:150',
                Rule::unique('empleados', 'email')->ignore($empleadoId),
            ],
            'cargo' => ['required', 'string', 'max:80'],
            'sucursal_id' => ['required', 'integer', 'exists:sucursales,id'],
            'tipo_contrato' => ['required', Rule::in(['indefinido', 'plazo_fijo', 'servicios_profesionales'])],
            'fecha_ingreso' => ['required', 'date'],
            'fecha_salida' => ['nullable', 'date', 'after_or_equal:fecha_ingreso'],
            'salario_base' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'eliminar_foto' => ['boolean'],
            'password_actual' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'max:255', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'numero_documento.unique' => 'Ya existe un empleado con este documento.',
            'email.unique' => 'Ya existe un empleado con este correo electrónico.',
            'fecha_salida.after_or_equal' => 'La fecha de salida no puede ser anterior a la fecha de ingreso.',
            'foto.image' => 'La foto debe ser una imagen válida.',
            'foto.mimes' => 'La foto debe ser JPG, PNG o WebP.',
            'foto.max' => 'La foto no puede superar los 2 MB.',
            'password_actual.current_password' => 'La contraseña actual no es correcta.',
            'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombres' => 'nombres',
            'apellidos' => 'apellidos',
            'tipo_documento' => 'tipo de documento',
            'numero_documento' => 'número de documento',
            'telefono' => 'teléfono',
            'email' => 'correo electrónico',
            'cargo' => 'cargo',
            'sucursal_id' => 'sucursal',
            'tipo_contrato' => 'tipo de contrato',
            'fecha_ingreso' => 'fecha de ingreso',
            'fecha_salida' => 'fecha de salida',
            'salario_base' => 'salario base',
            'foto' => 'foto de perfil',
            'password_actual' => 'contraseña actual',
            'password' => 'nueva contraseña',
        ];
    }
}
