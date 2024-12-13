<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Adicional extends Model
{
    use HasFactory;
    protected $table = 'adicionales';
    protected $fillable = [
        'cedula',
        'nombres',
        'apellidos',
        'limite',
        'direccion',
        'celular',
        'cliente_id',
        'cuenta'
    ];
}
