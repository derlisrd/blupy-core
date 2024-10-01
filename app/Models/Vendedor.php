<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendedor extends Model
{
    use HasFactory;
    protected $table = 'vendedores';
    protected $fillable = [
        'cedula',
        'nombre',
        'punto',
        'direccion',
        'organigrama',
        'qr_generado',
        'supervisor_id',
        'encargado_id'
    ];
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

}
