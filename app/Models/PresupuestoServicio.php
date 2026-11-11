<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresupuestoServicio extends Model
{
    protected $table = 'presupuesto_servicios';

    public $timestamps = false;

    protected $fillable = [
        'presupuesto_id', 'servicio_id', 'cantidad', 'costo_neto_unitario',
        'precio_unitario', 'fecha_inicio', 'fecha_fin',
    ];

    protected $casts = [
        'cantidad' => 'float',
        'costo_neto_unitario' => 'float',
        'precio_unitario' => 'float',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function presupuesto(): BelongsTo
    {
        return $this->belongsTo(Presupuesto::class);
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }
}
