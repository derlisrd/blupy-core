<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Sucursal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ClientesController extends Controller
{
    protected $campos;

    public function __construct()
    {
        $this->campos = ['clientes.id','u.name','u.id as user_id','cedula','celular','u.email','cliid as id_micredito','asofarma','funcionario','solicitud_credito','foto_ci_frente',
        'clientes.created_at','u.active','u.vendedor_id'];
    }

    public function index(){

        $desde = Carbon::now()->startOfMonth()->format('Y-m-d H:i:s');
        $hasta = Carbon::now()->format('Y-m-d H:i:s');
        $results = Cliente::join('users as u','u.cliente_id','=','clientes.id')
        ->whereBetween('clientes.created_at',[$desde,$hasta])
        ->orderBy('clientes.created_at','DESC')
        ->select($this->campos)
        ->get();

        return response()->json([
            'success'=>true,
            'results'=>$results
        ]);
    }

    public function filtrar(){

    }
}
