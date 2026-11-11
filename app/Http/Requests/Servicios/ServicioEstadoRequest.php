<?php

namespace App\Http\Requests\Servicios;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServicioEstadoRequest extends FormRequest
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
