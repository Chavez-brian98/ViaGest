<?php

namespace App\Http\Requests\Paquetes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaqueteRequest extends FormRequest
{
    private const TIPOS = ['nacional', 'internacional'];

    private const MARGEN_TIPOS = ['porcentaje', 'monto_fijo'];

    private const ESTADOS = ['borrador', 'activo', 'inactivo'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $datos = [];

        foreach (['codigo', 'nombre', 'descripcion'] as $campo) {
            if ($this->has($campo)) {
                $valor = $this->input($campo);
                $datos[$campo] = is_string($valor) ? (trim($valor) !== '' ? trim($valor) : null) : $valor;
            }
        }

        if (isset($datos['codigo']) && is_string($datos['codigo'])) {
            $datos['codigo'] = strtoupper($datos['codigo']);
        }

        foreach (['tipo', 'margen_tipo', 'estado'] as $campo) {
            if ($this->filled($campo)) {
                $datos[$campo] = strtolower(trim((string) $this->input($campo)));
            }
        }

        foreach (['ciudad_destino_id', 'duracion_dias', 'duracion_noches'] as $campo) {
            if ($this->has($campo)) {
                $valor = $this->input($campo);
                $datos[$campo] = ($valor === '' || $valor === null) ? null : (int) $valor;
            }
        }

        if ($this->has('margen_valor')) {
            $valor = $this->input('margen_valor');
            $datos['margen_valor'] = ($valor === '' || $valor === null) ? 0 : (float) str_replace(',', '', (string) $valor);
        }

        // Servicios asociados
        if ($this->has('servicios') && is_array($this->input('servicios'))) {
            $serviciosNormalizados = [];
            foreach ($this->input('servicios') as $item) {
                if (empty($item['servicio_id'])) {
                    continue;
                }
                $serviciosNormalizados[] = [
                    'servicio_id' => (int) $item['servicio_id'],
                    'dia_inicio' => isset($item['dia_inicio']) ? max(1, (int) $item['dia_inicio']) : 1,
                    'cantidad' => isset($item['cantidad']) ? max(0.01, (float) $item['cantidad']) : 1,
                ];
            }
            $datos['servicios'] = $serviciosNormalizados;
        }

        $this->merge($datos);
    }

    public function rules(): array
    {
        $paqueteId = $this->route('paquete');
        if (is_object($paqueteId)) {
            $paqueteId = $paqueteId->id;
        }

        return [
            'codigo' => [
                'required',
                'string',
                'max:30',
                Rule::unique('paquetes', 'codigo')->ignore($paqueteId),
            ],
            'nombre' => [
                'required',
                'string',
                'max:150',
            ],
            'descripcion' => [
                'nullable',
                'string',
            ],
            'tipo' => [
                'required',
                Rule::in(self::TIPOS),
            ],
            'ciudad_destino_id' => [
                'nullable',
                'integer',
                Rule::exists('ciudades', 'id'),
            ],
            'duracion_dias' => [
                'required',
                'integer',
                'min:1',
                'max:365',
            ],
            'duracion_noches' => [
                'required',
                'integer',
                'min:0',
                'max:365',
            ],
            'margen_tipo' => [
                'required',
                Rule::in(self::MARGEN_TIPOS),
            ],
            'margen_valor' => [
                'required',
                'numeric',
                'min:0',
            ],
            'estado' => [
                'sometimes',
                Rule::in(self::ESTADOS),
            ],
            'servicios' => [
                'nullable',
                'array',
            ],
            'servicios.*.servicio_id' => [
                'required',
                'integer',
                Rule::exists('servicios', 'id'),
            ],
            'servicios.*.dia_inicio' => [
                'required',
                'integer',
                'min:1',
            ],
            'servicios.*.cantidad' => [
                'required',
                'numeric',
                'min:0.01',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código del paquete es obligatorio.',
            'codigo.unique' => 'Este código ya se encuentra en uso.',
            'nombre.required' => 'El nombre del paquete es obligatorio.',
            'tipo.required' => 'El tipo de paquete es obligatorio.',
            'duracion_dias.required' => 'La duración en días es obligatoria.',
            'duracion_noches.required' => 'La duración en noches es obligatoria.',
            'margen_tipo.required' => 'El tipo de margen es obligatorio.',
            'margen_valor.required' => 'El valor de margen es obligatorio.',
            'ciudad_destino_id.exists' => 'La ciudad de destino seleccionada no es válida.',
        ];
    }
}
