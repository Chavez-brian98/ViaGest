<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaqueteServicio extends Model
{
    protected $table = 'paquete_servicios';

    public $timestamps = false;

    protected $fillable = [
        'paquete_id', 'servicio_id', 'dia_inicio', 'cantidad',
    ];

    protected $casts = [
        'dia_inicio' => 'integer',
        'cantidad' => 'float',
    ];

    public function paquete(): BelongsTo
    {
        return $this->belongsTo(Paquete::class);
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }
}
