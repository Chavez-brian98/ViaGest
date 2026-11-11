<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prospecto extends Model
{
    protected $table = 'prospectos';

    protected $fillable = [
        'codigo', 'nombres', 'apellidos', 'email', 'telefono', 'pais_destino',
        'fecha_viaje_estimada', 'numero_viajeros', 'origen', 'estado', 'motivo_perdida',
        'proximo_seguimiento', 'vendedor_id', 'sucursal_id', 'cliente_id', 'notas',
    ];

    protected $casts = [
        'fecha_viaje_estimada' => 'date',
        'numero_viajeros' => 'integer',
        'proximo_seguimiento' => 'date',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'vendedor_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }
}
