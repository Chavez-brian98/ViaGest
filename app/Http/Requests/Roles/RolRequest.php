<?php

namespace App\Http\Requests\Roles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rolId = $this->route('role')?->id ?? $this->route('rol')?->id;

        return [
            'nombre' => [
                'required',
                'string',
                'max:50',
                Rule::unique('roles', 'nombre')->ignore($rolId),
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'alcance' => ['required', Rule::in(['propia_sucursal', 'todas_sucursales'])],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['array'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del rol es obligatorio.',
            'nombre.unique' => 'Ya existe un rol registrado con este nombre.',
            'alcance.required' => 'Debes seleccionar el alcance del rol.',
            'alcance.in' => 'El alcance seleccionado no es válido.',
        ];
    }
}
