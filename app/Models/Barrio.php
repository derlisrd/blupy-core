<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barrio extends Model
{
    use HasFactory;
    protected $table = 'barrios';
    protected $fillable = [
        'departamento_id',
        'ciudad_id',
        'nombre',
        'codigo'
    ];
}
