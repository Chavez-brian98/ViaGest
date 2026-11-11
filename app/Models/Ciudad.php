<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ciudad extends Model
{
    protected $table = 'ciudades';

    protected $fillable = [
        'pais_codigo', 'nombre',
    ];

    public function sucursales(): HasMany
    {
        return $this->hasMany(Sucursal::class);
    }
}
