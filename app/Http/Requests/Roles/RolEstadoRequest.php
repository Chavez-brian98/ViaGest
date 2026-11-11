<?php

namespace App\Http\Requests\Roles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RolEstadoRequest extends FormRequest
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
            'estado_destino' => 'estado',
        ];
    }
}
