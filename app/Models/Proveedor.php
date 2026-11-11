<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected $fillable = [
        'codigo', 'tipo', 'nombre', 'razon_social', 'documento_fiscal', 'pais_codigo',
        'contacto_nombre', 'email', 'telefono', 'dias_credito', 'estado', 'notas',
    ];

    protected $casts = [
        'dias_credito' => 'integer',
    ];

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class);
    }
}
