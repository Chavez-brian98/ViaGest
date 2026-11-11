<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoCliente extends Model
{
    protected $table = 'pagos_cliente';

    protected $fillable = [
        'folio', 'venta_id', 'cuota_id', 'sucursal_id', 'fecha_pago', 'monto',
        'metodo_pago', 'referencia', 'registrado_por', 'estado',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto' => 'float',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }
}
