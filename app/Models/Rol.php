<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Rol extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'nombre', 'descripcion', 'alcance', 'estado',
    ];

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class);
    }

    public function esAdministrador(): bool
    {
        return strtolower((string) $this->nombre) === 'administrador';
    }

    /**
     * Devuelve los permisos asociados al rol en formato plano [modulo => [accion1, accion2]]
     */
    public function permisosAgrupados(): array
    {
        $filas = DB::table('rol_permisos')
            ->where('rol_id', $this->id)
            ->get();

        $agrupados = [];
        foreach ($filas as $fila) {
            $agrupados[$fila->modulo][] = $fila->accion;
        }

        return $agrupados;
    }
}
