<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialDato extends Model
{
    use HasFactory;
    protected $table = 'historial_datos';
    protected $fillable = [
        'user_id',
        'email',
        'celular'
    ];
}
