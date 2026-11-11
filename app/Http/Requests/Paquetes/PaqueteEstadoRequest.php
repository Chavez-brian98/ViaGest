<?php

namespace App\Http\Requests\Paquetes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaqueteEstadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado_destino' => ['required', Rule::in(['borrador', 'activo', 'inactivo'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'estado_destino' => 'estado',
        ];
    }
}
