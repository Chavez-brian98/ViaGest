<?php

namespace App\Http\Requests\Servicios;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServicioRequest extends FormRequest
{
    private const TIPOS = ['vuelo', 'hospedaje', 'traslado', 'actividad', 'guia', 'seguro', 'otro'];

    private const UNIDADES = ['por_persona', 'por_habitacion_noche', 'por_vehiculo', 'por_dia', 'por_grupo', 'por_unidad'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $textos = ['codigo', 'nombre', 'descripcion', 'vigente_desde', 'vigente_hasta'];
        $datos = [];

        foreach ($textos as $campo) {
            if (! $this->has($campo)) {
                continue;
            }

            $valor = $this->input($campo);
            $datos[$campo] = is_string($valor) ? (trim($valor) !== '' ? trim($valor) : null) : $valor;
        }

        foreach (['tipo', 'unidad_cobro', 'estado'] as $campo) {
            if ($this->filled($campo)) {
                $datos[$campo] = strtolower(trim((string) $this->input($campo)));
            }
        }

        if (isset($datos['codigo']) && is_string($datos['codigo'])) {
            $datos['codigo'] = strtoupper($datos['codigo']);
        }

        foreach (['proveedor_id', 'ciudad_origen_id', 'ciudad_destino_id'] as $campo) {
            if ($this->has($campo)) {
                $valor = $this->input($campo);
                $datos[$campo] = ($valor === '' || $valor === null) ? null : (int) $valor;
            }
        }

        if ($this->has('costo_neto')) {
            $valor = $this->input('costo_neto');
            $datos['costo_neto'] = ($valor === '' || $valor === null) ? null : $valor;
        }

        $this->merge($datos);
    }

    public function rules(): array
    {
        $servicioId = $this->route('servicio');

        return [
            'codigo' => [
                'required', 'string', 'max:30',
                Rule::unique('servicios', 'codigo')->ignore($servicioId),
            ],
            'tipo' => ['required', Rule::in(self::TIPOS)],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
            'ciudad_origen_id' => ['nullable', 'integer', 'exists:ciudades,id'],
            'ciudad_destino_id' => ['nullable', 'integer', 'exists:ciudades,id'],
            'unidad_cobro' => ['required', Rule::in(self::UNIDADES)],
            'costo_neto' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'vigente_desde' => ['nullable', 'date'],
            'vigente_hasta' => ['nullable', 'date', 'after_or_equal:vigente_desde'],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.unique' => 'Ya existe un servicio con este código.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'ciudad_origen_id.exists' => 'La ciudad de origen seleccionada no existe.',
            'ciudad_destino_id.exists' => 'La ciudad de destino seleccionada no existe.',
            'vigente_hasta.after_or_equal' => 'La fecha de vigencia final debe ser igual o posterior a la inicial.',
        ];
    }

    public function attributes(): array
    {
        return [
            'codigo' => 'código',
            'tipo' => 'tipo de servicio',
            'nombre' => 'nombre',
            'descripcion' => 'descripción',
            'proveedor_id' => 'proveedor',
            'ciudad_origen_id' => 'ciudad de origen',
            'ciudad_destino_id' => 'ciudad de destino',
            'unidad_cobro' => 'unidad de cobro',
            'costo_neto' => 'costo neto',
            'vigente_desde' => 'vigencia desde',
            'vigente_hasta' => 'vigencia hasta',
            'estado' => 'estado',
        ];
    }
}
