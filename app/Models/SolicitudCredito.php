<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudCredito extends Model
{
    use HasFactory;
    protected $table = 'solicitud_creditos';
    protected $fillable = [
        'cliente_id',
        'estado_id',
        'estado',
        'codigo',
        'tipo',
        'importe'
    ];
}
