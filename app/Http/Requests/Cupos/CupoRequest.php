<?php

namespace App\Http\Requests\Cupos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CupoRequest extends FormRequest
{
    private const TIPOS = ['bloqueo', 'compra_adelantada'];

    private const ESTADOS = ['activo', 'liberado', 'cerrado'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $datos = [];

        if ($this->has('servicio_id')) {
            $valor = $this->input('servicio_id');
            $datos['servicio_id'] = ($valor === '' || $valor === null) ? null : (int) $valor;
        }

        if ($this->has('cantidad_total')) {
            $valor = $this->input('cantidad_total');
            $datos['cantidad_total'] = ($valor === '' || $valor === null) ? null : (int) $valor;
        }

        foreach (['tipo', 'estado'] as $campo) {
            if ($this->filled($campo)) {
                $datos[$campo] = strtolower(trim((string) $this->input($campo)));
            }
        }

        foreach (['fecha_inicio', 'fecha_fin', 'fecha_liberacion'] as $campo) {
            if ($this->has($campo)) {
                $valor = $this->input($campo);
                $datos[$campo] = is_string($valor) ? (trim($valor) !== '' ? trim($valor) : null) : $valor;
            }
        }

        $this->merge($datos);
    }

    public function rules(): array
    {
        return [
            'servicio_id' => [
                'required',
                'integer',
                Rule::exists('servicios', 'id'),
            ],
            'tipo' => [
                'required',
                Rule::in(self::TIPOS),
            ],
            'fecha_inicio' => [
                'required',
                'date',
            ],
            'fecha_fin' => [
                'required',
                'date',
                'after_or_equal:fecha_inicio',
            ],
            'cantidad_total' => [
                'required',
                'integer',
                'min:1',
            ],
            'fecha_liberacion' => [
                'nullable',
                'date',
                'before_or_equal:fecha_inicio',
            ],
            'estado' => [
                'sometimes',
                Rule::in(self::ESTADOS),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'servicio_id.required' => 'El servicio asociado es obligatorio.',
            'servicio_id.exists' => 'El servicio seleccionado no es válido.',
            'tipo.required' => 'El tipo de cupo es obligatorio.',
            'tipo.in' => 'El tipo de cupo seleccionado no es válido.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'cantidad_total.required' => 'La cantidad total es obligatoria.',
            'cantidad_total.integer' => 'La cantidad total debe ser un número entero.',
            'cantidad_total.min' => 'La cantidad total debe ser al menos 1.',
            'fecha_liberacion.date' => 'La fecha de liberación debe ser una fecha válida.',
            'fecha_liberacion.before_or_equal' => 'La fecha de liberación no puede ser posterior a la fecha de inicio del cupo.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ];
    }
}
