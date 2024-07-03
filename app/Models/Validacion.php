<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Validacion extends Model
{
    use HasFactory;
    protected $table = 'validaciones';
    protected $fillable = [
        'cliente_id',
        'email',
        'celular',
        'codigo',
        'forma',
        'valido'
    ];
}
