<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;
    protected $table = 'clientes';
    protected $fillable = [
        'cliid',
        'nombre_primero',
        'nombre_segundo',
        'apellido_primero',
        'apellido_segundo',
        'fecha_nacimiento',
        'cedula',
        'foto_ci_frente',
        'foto_ci_dorso',
        'selfie',
        'celular',
        'departamento',
        'departamento_id',
        'ciudad',
        'ciudad_id',
        'barrio',
        'barrio_id',
        'calle',
        'numero_casa',
        'referencia_direccion',
        'latitud_direccion',
        'longitud_direccion',
        'empresa',
        'lugar_empresa',
        'lugar_empresa_id',
        'tipo_empresa',
        'tipo_empresa_id',
        'empresa_direccion',
        'latitud_empresa',
        'longitud_empresa',
        'empresa_departamento',
        'empresa_departamento_id',
        'empresa_ciudad',
        'empresa_ciudad_id',
        'empresa_barrio',
        'foto_comprobante_ingreso',
        'foto_comprobante_ande',
        'empresa_barrio_id',
        'empresa_telefono',
        'empresa_celular',
        'profesion_id',
        'direccion_completado',
        'profesion',
        'salario',
        'antiguedad_laboral',
        'antiguedad_laboral_mes',
        'empresa_email',
        'asofarma',
        'funcionario',
        'extranjero',
        'solicitud_credito',
        'linea_farma',
        'importe_credito_farma',
        'codigo_farma',
        'extranjero'
    ];

    public function user(){
        return $this->hasOne(User::class, 'cliente_id');
    }
    public function adicionales(){
        return $this->hasMany(Adicional::class,'cliente_id');
    }
    public function ventas(){
        return $this->hasMany(Venta::class,'cliente_id');
    }
}
