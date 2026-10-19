<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Empleado extends Authenticatable
{
    protected $table = 'empleados';

    protected $fillable = [
        'codigo', 'nombres', 'apellidos', 'tipo_documento', 'numero_documento', 'telefono',
        'cargo', 'sucursal_id', 'tipo_contrato', 'fecha_ingreso', 'fecha_salida', 'salario_base',
        'estado', 'email', 'password', 'rol_id', 'acceso', 'ultimo_acceso_at',
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
}
