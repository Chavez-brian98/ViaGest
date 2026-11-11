<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuotaCobro extends Model
{
    protected $table = 'cuotas_cobro';

    protected $fillable = [
        'venta_id', 'numero', 'fecha_vencimiento', 'monto', 'estado',
    ];

    protected $casts = [
        'numero' => 'integer',
        'fecha_vencimiento' => 'date',
        'monto' => 'float',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }
}
