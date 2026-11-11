<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuiaAgenda extends Model
{
    protected $table = 'guia_agenda';

    protected $fillable = [
        'guia_id', 'fecha_inicio', 'fecha_fin', 'tipo', 'venta_servicio_id', 'notas',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function guia(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'guia_id');
    }

    public function ventaServicio(): BelongsTo
    {
        return $this->belongsTo(VentaServicio::class);
    }
}
