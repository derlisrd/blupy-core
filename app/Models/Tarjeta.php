<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tarjeta extends Model
{
    use HasFactory;
    protected $fillable = [
        'cliente_id',
        'cuenta',
        'tipo',
        'numero',
        'linea',
        'bloqueo',
        'motivo_bloqueo'
    ];

    public function cliente(){

    }
}
