<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    protected $table = 'ventas';

    protected $fillable = [
        'codigo', 'cliente_id', 'vendedor_id', 'sucursal_id', 'presupuesto_id', 'paquete_id',
        'tipo', 'fecha_venta', 'fecha_viaje_inicio', 'fecha_viaje_fin', 'descuento_monto',
        'estado', 'fecha_cancelacion', 'motivo_cancelacion', 'notas',
    ];

    protected $casts = [
        'fecha_venta' => 'date',
        'fecha_viaje_inicio' => 'date',
        'fecha_viaje_fin' => 'date',
        'descuento_monto' => 'float',
        'fecha_cancelacion' => 'date',
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

    public function presupuesto(): BelongsTo
    {
        return $this->belongsTo(Presupuesto::class);
    }

    public function paquete(): BelongsTo
    {
        return $this->belongsTo(Paquete::class);
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(VentaServicio::class);
    }
}
