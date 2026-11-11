<?php

namespace App\Http\Requests\Prospectos;

use Illuminate\Foundation\Http\FormRequest;

class ProspectoConvertirRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tipo_documento' => $this->filled('tipo_documento') ? $this->input('tipo_documento') : null,
            'numero_documento' => $this->filled('numero_documento') ? trim((string) $this->input('numero_documento')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'tipo_documento' => ['nullable', 'string', 'in:dui,nit,carnet_residente,otro'],
            'numero_documento' => ['nullable', 'string', 'max:30'],
        ];
    }
}
