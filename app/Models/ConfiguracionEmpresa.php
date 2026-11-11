<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionEmpresa extends Model
{
    protected $table = 'configuracion_empresa';

    public $incrementing = false;

    protected $fillable = [
        'nombre_comercial', 'razon_social', 'documento_fiscal', 'email_contacto', 'telefono',
        'direccion', 'logo_ruta', 'imagenes_login', 'zona_horaria', 'moneda_base',
        'terminos_condiciones', 'color_primary', 'color_secondary', 'color_accent',
        'color_background', 'color_surface', 'color_ink',
    ];

    protected $casts = [
        'imagenes_login' => 'array',
    ];
}
