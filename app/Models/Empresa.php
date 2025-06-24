<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'empresa';
    
    protected $fillable = [
        'user_id',
        'ruc',
        'doc_autorizado',
        'razon_social',
        'email',
        'telefono'
];
}
