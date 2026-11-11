<?php

namespace App\Http\Requests\Prospectos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProspectoEstadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado_destino' => [
                'required',
                Rule::in(['nuevo', 'contactado', 'cotizado', 'convertido', 'perdido']),
            ],
            'motivo_perdida' => [
                'nullable',
                'required_if:estado_destino,perdido',
                Rule::in(['precio', 'sin_respuesta', 'eligio_competencia', 'cambio_de_planes', 'otro']),
            ],
        ];
    }
}
