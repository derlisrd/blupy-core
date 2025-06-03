<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comision extends Model
{
    protected $table = 'comisiones';
    protected $fillable = [
        'cedula',
        'nombre',
        'usuario',
        'tipo',
        'cliente_id',
        'vendedor_id',
    ];
}
