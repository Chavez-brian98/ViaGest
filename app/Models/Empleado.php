<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;

class Empleado extends Authenticatable
{
    protected $table = 'empleados';

    protected $fillable = [
        'codigo', 'nombres', 'apellidos', 'tipo_documento', 'numero_documento', 'telefono',
        'cargo', 'sucursal_id', 'tipo_contrato', 'fecha_ingreso', 'fecha_salida', 'salario_base',
        'estado', 'email', 'password', 'rol_id', 'acceso', 'ultimo_acceso_at', 'foto_perfil',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'fecha_salida' => 'date',
        'salario_base' => 'decimal:2',
        'ultimo_acceso_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class);
    }

    public function nombreCompleto(): string
    {
        $nombre = trim(($this->nombres ?? '').' '.($this->apellidos ?? ''));

        return $nombre !== '' ? $nombre : 'Usuario';
    }

    public function iniciales(): string
    {
        $iniciales = mb_substr(trim((string) $this->nombres), 0, 1)
            .mb_substr(trim((string) $this->apellidos), 0, 1);

        return mb_strtoupper($iniciales !== '' ? $iniciales : 'U');
    }

    public function fotoPerfilUrl(): ?string
    {
        return $this->foto_perfil
            ? Storage::disk('public')->url($this->foto_perfil)
            : null;
    }
}
