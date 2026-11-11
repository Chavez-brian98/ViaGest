<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificacionRegla extends Model
{
    protected $table = 'notificacion_reglas';

    protected $fillable = [
        'nombre', 'evento', 'canal', 'dias_anticipacion', 'destinatario', 'rol_id', 'estado',
    ];

    protected $casts = [
        'dias_anticipacion' => 'integer',
    ];

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class);
    }
}
