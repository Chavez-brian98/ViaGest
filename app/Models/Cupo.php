<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cupo extends Model
{
    protected $table = 'cupos';

    protected $fillable = [
        'servicio_id', 'tipo', 'fecha_inicio', 'fecha_fin', 'cantidad_total',
        'fecha_liberacion', 'estado',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'cantidad_total' => 'integer',
        'fecha_liberacion' => 'date',
    ];

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }
}
