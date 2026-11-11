<?php

namespace App\Http\Requests\Cotizaciones;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CotizacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'codigo' => strtoupper(trim((string) $this->input('codigo'))),
            'prospecto_id' => $this->filled('prospecto_id') ? (int) $this->input('prospecto_id') : null,
            'cliente_id' => $this->filled('cliente_id') ? (int) $this->input('cliente_id') : null,
            'paquete_id' => $this->filled('paquete_id') ? (int) $this->input('paquete_id') : null,
            'descuento_monto' => $this->filled('descuento_monto') ? (float) $this->input('descuento_monto') : 0.00,
            'numero_viajeros' => $this->filled('numero_viajeros') ? (int) $this->input('numero_viajeros') : 1,
            'notas' => $this->filled('notas') ? trim((string) $this->input('notas')) : null,
        ]);
    }

    public function rules(): array
    {
        $cotizacionId = $this->route('cotizacione') ?? $this->route('cotizacion');
        if (is_object($cotizacionId)) {
            $cotizacionId = $cotizacionId->id ?? null;
        }

        return [
            'codigo' => [
                'required',
                'string',
                'max:20',
                Rule::unique('presupuestos', 'codigo')->ignore($cotizacionId),
            ],
            'prospecto_id' => ['nullable', 'integer', 'exists:prospectos,id'],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'vendedor_id' => ['required', 'integer', 'exists:empleados,id'],
            'sucursal_id' => ['required', 'integer', 'exists:sucursales,id'],
            'paquete_id' => ['nullable', 'integer', 'exists:paquetes,id'],
            'fecha_emision' => ['required', 'date'],
            'fecha_caducidad' => ['required', 'date', 'after_or_equal:fecha_emision'],
            'fecha_viaje_inicio' => ['nullable', 'date'],
            'fecha_viaje_fin' => ['nullable', 'date', 'after_or_equal:fecha_viaje_inicio'],
            'numero_viajeros' => ['required', 'integer', 'min:1', 'max:65535'],
            'descuento_monto' => ['nullable', 'numeric', 'min:0'],
            'estado' => [
                'required',
                Rule::in(['borrador', 'enviado', 'aprobado', 'rechazado', 'caducado', 'convertido']),
            ],
            'notas' => ['nullable', 'string'],

            // Validación de servicios cotizados
            'servicios' => ['nullable', 'array'],
            'servicios.*.servicio_id' => ['required_with:servicios', 'integer', 'exists:servicios,id'],
            'servicios.*.cantidad' => ['required_with:servicios', 'numeric', 'min:0.01'],
            'servicios.*.costo_neto_unitario' => ['required_with:servicios', 'numeric', 'min:0'],
            'servicios.*.precio_unitario' => ['required_with:servicios', 'numeric', 'min:0'],
            'servicios.*.fecha_inicio' => ['nullable', 'date'],
            'servicios.*.fecha_fin' => ['nullable', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Debe tener al menos prospecto_id o cliente_id
            $prospectoId = $this->input('prospecto_id');
            $clienteId = $this->input('cliente_id');

            if (empty($prospectoId) && empty($clienteId)) {
                $validator->errors()->add('cliente_id', 'Debes seleccionar un cliente o un prospecto para la cotización.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código de cotización es obligatorio.',
            'codigo.unique' => 'Este código ya ha sido asignado a otra cotización.',
            'fecha_emision.required' => 'La fecha de emisión es obligatoria.',
            'fecha_caducidad.required' => 'La fecha de caducidad es obligatoria.',
            'fecha_caducidad.after_or_equal' => 'La fecha de caducidad debe ser igual o posterior a la de emisión.',
            'fecha_viaje_fin.after_or_equal' => 'La fecha de fin de viaje debe ser posterior o igual a la de inicio.',
            'vendedor_id.required' => 'Debes asignar un vendedor.',
            'vendedor_id.exists' => 'El vendedor seleccionado no es válido.',
            'sucursal_id.required' => 'Debes seleccionar una sucursal.',
            'sucursal_id.exists' => 'La sucursal seleccionada no es válida.',
        ];
    }
}
