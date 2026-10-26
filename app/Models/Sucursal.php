<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sucursal extends Model
{
    protected $table = 'sucursales';

    protected $fillable = [
        'codigo', 'nombre', 'tipo', 'ciudad_id', 'direccion', 'telefono', 'estado',
    ];

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }
}
