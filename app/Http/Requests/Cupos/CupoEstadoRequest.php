<?php

namespace App\Http\Requests\Cupos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CupoEstadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado_destino' => ['required', Rule::in(['activo', 'liberado', 'cerrado'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'estado_destino' => 'estado',
        ];
    }
}
