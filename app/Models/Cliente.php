<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'codigo', 'tipo', 'nombres', 'apellidos', 'razon_social', 'tipo_documento',
        'numero_documento', 'fecha_nacimiento', 'nacionalidad_pais', 'email', 'telefono',
        'direccion', 'pasaporte_numero', 'pasaporte_pais', 'pasaporte_vence',
        'preferencias_viaje', 'sucursal_id', 'estado',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'pasaporte_vence' => 'date',
    ];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function nombreMostrado(): string
    {
        $nombre = $this->tipo === 'empresa'
            ? (string) $this->razon_social
            : trim(($this->nombres ?? '').' '.($this->apellidos ?? ''));

        return $nombre !== '' ? $nombre : 'Cliente sin nombre';
    }
}
