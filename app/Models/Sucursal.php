<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    use HasFactory;
    protected $table = 'sucursales';
    protected $fillable = [
        'encargado_id',
        'codigo',
        'punto',
        'descripcion',
        'departamento',
        'ciudad',
        'direccion',
        'telefono',
        'latitud',
        'longitud',
        'disponible'
    ];
}
