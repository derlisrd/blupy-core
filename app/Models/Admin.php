<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    protected $table = 'admins';


    public function permisos()
    {
        return $this->belongsToMany(Permiso::class, 'permisos_otorgados');
    }

    public function tienePermiso(string $modulo, string $accion): bool
    {
        return $this->permisos()->where('modulo', $modulo)->where('accion', $accion)->exists();
    }
}
