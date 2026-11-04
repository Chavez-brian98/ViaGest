<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    protected $table = 'bitacora_auditoria';

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $fillable = [
        'empleado_id',
        'accion',
        'tabla',
        'registro_id',
        'datos_anteriores',
        'datos_nuevos',
        'ip',
        'created_at',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
        'created_at' => 'datetime',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }
}
