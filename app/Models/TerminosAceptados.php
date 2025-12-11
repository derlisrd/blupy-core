<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TerminosAceptados extends Model
{
    protected $table = 'terminos_aceptados';
    
    protected $fillable = [
        'cliente_id',
        'cedula',
        'telefono',
        'termino_tipo',
        'version',
        'enlace',
        'aceptado',
        'aceptado_fecha',
    ];
}
