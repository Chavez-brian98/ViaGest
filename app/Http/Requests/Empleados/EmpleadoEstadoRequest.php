<?php

namespace App\Http\Requests\Empleados;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmpleadoEstadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado_destino' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'estado_destino' => 'estado destino',
        ];
    }
}
