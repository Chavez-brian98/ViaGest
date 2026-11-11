<?php

namespace App\Http\Requests\Prospectos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProspectoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'codigo' => strtoupper(trim((string) $this->input('codigo'))),
            'nombres' => trim((string) $this->input('nombres')),
            'apellidos' => $this->filled('apellidos') ? trim((string) $this->input('apellidos')) : null,
            'email' => $this->filled('email') ? strtolower(trim((string) $this->input('email'))) : null,
            'telefono' => $this->filled('telefono') ? trim((string) $this->input('telefono')) : null,
            'pais_destino' => $this->filled('pais_destino') ? strtoupper(trim((string) $this->input('pais_destino'))) : null,
            'notas' => $this->filled('notas') ? trim((string) $this->input('notas')) : null,
        ]);
    }

    public function rules(): array
    {
        $prospectoId = $this->route('prospecto');
        if (is_object($prospectoId)) {
            $prospectoId = $prospectoId->id ?? null;
        }

        return [
            'codigo' => [
                'required',
                'string',
                'max:20',
                Rule::unique('prospectos', 'codigo')->ignore($prospectoId),
            ],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:25'],
            'pais_destino' => ['nullable', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'fecha_viaje_estimada' => ['nullable', 'date'],
            'numero_viajeros' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'origen' => [
                'required',
                Rule::in(['referido', 'redes_sociales', 'sitio_web', 'visita_local', 'telefono', 'evento', 'otro']),
            ],
            'estado' => [
                'required',
                Rule::in(['nuevo', 'contactado', 'cotizado', 'convertido', 'perdido']),
            ],
            'motivo_perdida' => [
                'nullable',
                'required_if:estado,perdido',
                Rule::in(['precio', 'sin_respuesta', 'eligio_competencia', 'cambio_de_planes', 'otro']),
            ],
            'proximo_seguimiento' => ['nullable', 'date'],
            'vendedor_id' => ['required', 'integer', 'exists:empleados,id'],
            'sucursal_id' => ['required', 'integer', 'exists:sucursales,id'],
            'notas' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código del prospecto es obligatorio.',
            'codigo.unique' => 'Este código ya ha sido asignado a otro prospecto.',
            'nombres.required' => 'El nombre es obligatorio.',
            'pais_destino.regex' => 'El país de destino debe ser un código ISO de 2 letras (ej. ES, MX, US).',
            'pais_destino.size' => 'El país de destino debe tener exactamente 2 letras.',
            'motivo_perdida.required_if' => 'Debes indicar el motivo de pérdida si el estado es perdido.',
            'vendedor_id.required' => 'Debes asignar un vendedor.',
            'vendedor_id.exists' => 'El vendedor seleccionado no es válido.',
            'sucursal_id.required' => 'Debes seleccionar una sucursal.',
            'sucursal_id.exists' => 'La sucursal seleccionada no es válida.',
        ];
    }
}
