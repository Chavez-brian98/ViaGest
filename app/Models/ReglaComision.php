<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReglaComision extends Model
{
    protected $table = 'reglas_comision';

    protected $fillable = [
        'nombre', 'beneficiario', 'metodo', 'valor', 'base_calculo', 'venta_minima',
        'tipo_servicio', 'sucursal_id', 'vigente_desde', 'vigente_hasta', 'prioridad', 'estado',
    ];

    protected $casts = [
        'valor' => 'float',
        'venta_minima' => 'float',
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
        'prioridad' => 'integer',
    ];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }
}
