<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comision extends Model
{
    protected $table = 'comisiones';

    protected $fillable = [
        'regla_id', 'venta_id', 'venta_servicio_id', 'empleado_id', 'proveedor_id',
        'base_monto', 'valor_aplicado', 'monto', 'estado', 'fecha_calculo', 'fecha_pago',
    ];

    protected $casts = [
        'base_monto' => 'float',
        'valor_aplicado' => 'float',
        'monto' => 'float',
        'fecha_calculo' => 'date',
        'fecha_pago' => 'date',
    ];

    public function regla(): BelongsTo
    {
        return $this->belongsTo(ReglaComision::class);
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }
}
