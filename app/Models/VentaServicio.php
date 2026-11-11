<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaServicio extends Model
{
    protected $table = 'venta_servicios';

    public $timestamps = false;

    protected $fillable = [
        'venta_id', 'servicio_id', 'cupo_id', 'cantidad', 'costo_neto_unitario',
        'precio_unitario', 'fecha_inicio', 'fecha_fin', 'estado', 'localizador',
    ];

    protected $casts = [
        'cantidad' => 'float',
        'costo_neto_unitario' => 'float',
        'precio_unitario' => 'float',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    public function cupo(): BelongsTo
    {
        return $this->belongsTo(Cupo::class);
    }
}
