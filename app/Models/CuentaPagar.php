<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuentaPagar extends Model
{
    protected $table = 'cuentas_por_pagar';

    protected $fillable = [
        'codigo', 'tipo', 'proveedor_id', 'categoria_gasto', 'venta_servicio_id', 'sucursal_id',
        'descripcion', 'numero_documento', 'fecha_emision', 'fecha_vencimiento',
        'fecha_programada_pago', 'monto', 'estado', 'fecha_pago', 'metodo_pago', 'referencia_pago',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_programada_pago' => 'date',
        'monto' => 'float',
        'fecha_pago' => 'date',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }
}
