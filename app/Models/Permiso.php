<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    protected $table = 'permisos';

    protected $fillable = [
        'modulo',
        'accion'
    ];

    public function admins()
    {
        return $this->belongsToMany(Admin::class, 'permisos_otorgados');
    }

}
