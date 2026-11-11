<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paquete extends Model
{
    protected $table = 'paquetes';

    protected $fillable = [
        'codigo', 'nombre', 'descripcion', 'tipo', 'ciudad_destino_id', 'duracion_dias',
        'duracion_noches', 'margen_tipo', 'margen_valor', 'estado',
    ];

    protected $casts = [
        'duracion_dias' => 'integer',
        'duracion_noches' => 'integer',
        'margen_valor' => 'float',
    ];

    public function ciudadDestino(): BelongsTo
    {
        return $this->belongsTo(Ciudad::class, 'ciudad_destino_id');
    }
}
