<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermisosOtorgado extends Model
{
    protected $table = 'permisos_otorgados';

    protected $fillable = [
        'admin_id',
        'permiso_id',
    ];
}
