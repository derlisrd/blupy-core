<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;
    protected $fillable = [
        'cliente_id',
        'codigo',
        'adicional',
        'factura_numero',
        'importe',
        'descuento',
        'importe_final',
        'forma_pago',
        'forma_codigo',
        'descripcion',
        'sucursal',
        'codigo_sucursal',
        'fecha',
        'forma_venta',
        'documento',
        'operacion'
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
        'forma_venta'
    ];
    public function cliente(){
        return $this->belongsTo(Cliente::class);
    }
}
