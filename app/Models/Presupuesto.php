<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Presupuesto extends Model
{
    protected $table = 'presupuestos';

    protected $fillable = [
        'codigo', 'prospecto_id', 'cliente_id', 'vendedor_id', 'sucursal_id', 'paquete_id',
        'fecha_emision', 'fecha_caducidad', 'fecha_viaje_inicio', 'fecha_viaje_fin',
        'numero_viajeros', 'descuento_monto', 'estado', 'notas',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_caducidad' => 'date',
        'fecha_viaje_inicio' => 'date',
        'fecha_viaje_fin' => 'date',
        'numero_viajeros' => 'integer',
        'descuento_monto' => 'float',
    ];

    public function prospecto(): BelongsTo
    {
        return $this->belongsTo(Prospecto::class);
    }

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

    public function paquete(): BelongsTo
    {
        return $this->belongsTo(Paquete::class);
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(PresupuestoServicio::class);
    }
}
