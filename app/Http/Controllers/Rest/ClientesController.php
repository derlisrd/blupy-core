<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientesController extends Controller
{
    protected $campos;

    public function __construct()
    {
        $this->campos = ['clientes.id','u.name','u.id as user_id','cedula','celular','u.email','cliid as id_micredito','asofarma','funcionario','solicitud_credito','foto_ci_frente',
        'clientes.created_at','u.active','u.vendedor_id'];
    }

    /*
    ==============================================================================================================
    CLIENTES NUEVOS DEL MES
    ==============================================================================================================
    */

    public function index(){
        try {
            $desde = Carbon::now()->startOfMonth()->format('Y-m-d H:i:s');
            $hasta = Carbon::now()->format('Y-m-d H:i:s');
            $results = Cliente::join('users as u','u.cliente_id','=','clientes.id')
            ->orderBy('clientes.created_at','DESC')
            ->whereBetween('clientes.created_at',[$desde,$hasta])
            ->select($this->campos)
            ->get();
            /* Cliente::join('users as u','u.cliente_id','=','clientes.id')
            ->whereBetween('clientes.created_at',[$desde,$hasta])
            ->orderBy('clientes.created_at','DESC')
            ->select($this->campos)
            ->get(); */

            return response()->json([
                'success'=>true,
                'results'=>$results
            ]);
        } catch (\Throwable $th) {
            throw $th;
            return response()->json([
                'success'=>false,
                'results'=>null,
                'message'=>'Error de servidor'
            ]);
        }
    }

    /*
    ==============================================================================================================
    FILTROS
    ==============================================================================================================
    */
    public function filtros(Request $request){
        $asofarma = $request->asofarma ?? 0;
        $funcionario = $request->funcionario ?? 0;
        $todos = $request->todos ?? false;
        $desde = $request->desde ?? Carbon::now()->startOfMonth()->format('Y-m-d H:i:s');
        $hasta = $request->hasta ?? Carbon::now()->format('Y-m-d H:i:s');

        if($todos){
            $clientes = Cliente::join('users as u','u.cliente_id','=','clientes.id')
                ->orderBy('created_at','DESC')
                ->where('asofarma',$asofarma)
                ->where('funcionario',$funcionario)
                ->select($this->campos);
        }

        if(!$todos){
            $clientes = Cliente::join('users as u','u.cliente_id','=','clientes.id')
                ->orderBy('created_at','DESC')
                ->where('asofarma',$asofarma)
                ->where('funcionario',$funcionario)
                ->whereBetween('clientes.created_at',[$desde,$hasta])
                ->select($this->campos);
        }

        return response()->json([
            'success'=>true,
            'total'=>$clientes->count(),
            'results'=>$clientes->get()
        ]);
    }

    /*
    ==============================================================================================================
    BUSCAR
    ==============================================================================================================
    */

    public function buscar(Request $req){

        $buscar = $req->buscar;
        $clientes = Cliente::join('users as u','u.cliente_id','=','clientes.id')
        ->orderBy('created_at','DESC')
        ->where('clientes.cedula','like','%'.$buscar.'%')
        ->orWhere('u.name','like','%'.$buscar.'%')
        ->select($this->campos);


        return response()->json([
            'success'=>true,
            'total'=>$clientes->count(),
            'results'=>$clientes->get()
        ]);
    }


    /*
    ==============================================================================================================
    REINICIAR CONTRA
    ==============================================================================================================
    */


    public function restablecerContrasena(Request $request){
        $validator = Validator::make($request->all(), ['id' => 'required|exists:users,id','password'=>'required|string|min:8']);
        if ($validator->fails())
            return response()->json(['success'=>false,'message'=> $validator->errors()->first()], 400);

        $user = User::find($request->id);
        if(!$user)
            return response()->json(['success'=>false,'message'=>'Usuario no existe'],404);

        $user->password = Hash::make($request->password);
        $user->changepass = true;
        $user->save();

        return response()->json([
            'success'=>true,
            'message'=>'Cambiado !'
        ]);
    }
    /*
    ==============================================================================================================
    ACTIVAR USUARIO
    ==============================================================================================================
    */
    public function activar(Request $request){
        $validator = Validator::make($request->all(), ['id' => 'required|exists:users,id']);
        if ($validator->fails())
            return response()->json(['success'=>false,'message'=> $validator->errors()->first()], 400);

        $user = User::find($request->id);
        if(!$user)
            return response()->json(['success'=>false,'message'=>'Usuario no existe'],404);

        $user->active = 1;
        $user->save();

        return response()->json([
            'success'=>true,
            'message'=>'Cambiado !'
        ]);
    }


    public function ficha(Request $request){

        $clients = Cliente::find($request->id);

        return response()->json([
            'success'=>true,
            'results'=>$clients
        ]);
    }

}
