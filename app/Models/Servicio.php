<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servicio extends Model
{
    protected $table = 'servicios';

    protected $fillable = [
        'codigo', 'tipo', 'nombre', 'descripcion', 'proveedor_id', 'ciudad_origen_id',
        'ciudad_destino_id', 'unidad_cobro', 'costo_neto', 'vigente_desde', 'vigente_hasta', 'estado',
    ];

    protected $casts = [
        'costo_neto' => 'float',
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function ciudadOrigen(): BelongsTo
    {
        return $this->belongsTo(Ciudad::class, 'ciudad_origen_id');
    }

    public function ciudadDestino(): BelongsTo
    {
        return $this->belongsTo(Ciudad::class, 'ciudad_destino_id');
    }

    public function cupos(): HasMany
    {
        return $this->hasMany(Cupo::class);
    }
}
