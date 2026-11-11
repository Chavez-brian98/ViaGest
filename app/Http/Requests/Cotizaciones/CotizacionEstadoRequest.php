<?php

namespace App\Http\Requests\Cotizaciones;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CotizacionEstadoRequest extends FormRequest
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
                Rule::in(['borrador', 'enviado', 'aprobado', 'rechazado', 'caducado', 'convertido']),
            ],
        ];
    }
}
