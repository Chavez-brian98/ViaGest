<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    const UPDATED_AT = null;

    protected $fillable = [
        'empleado_id', 'regla_id', 'evento', 'canal', 'titulo', 'mensaje',
        'referencia_tipo', 'referencia_id', 'leida_at', 'enviada_at',
    ];

    protected $casts = [
        'referencia_id' => 'integer',
        'leida_at' => 'datetime',
        'enviada_at' => 'datetime',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function regla(): BelongsTo
    {
        return $this->belongsTo(NotificacionRegla::class);
    }
}
