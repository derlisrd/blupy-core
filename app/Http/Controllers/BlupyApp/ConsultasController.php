<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Version;
use App\Services\InfinitaService;
use App\Services\FarmaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;


class ConsultasController extends Controller
{
    private $infinitaService;
    private $farmaService;

    public function __construct()
    {
        $this->infinitaService = new InfinitaService();
        $this->farmaService = new FarmaService();
    }


    public function verificarExisteDocumento(Request $req){
        $validator = Validator::make($req->all(),trans('validation.verify.documento'),trans('validation.verify.documento.messages'));

        if($validator->fails())
            return response()->json(['success'=>false,'messages'=>$validator->errors()->first() ], 400);

        $ip = $req->ip();
        $executed = RateLimiter::attempt($ip,$perTwoMinutes = 3,function() {});
        if (!$executed)
            return response()->json(['success'=>false, 'message'=>'Demasiadas peticiones. Espere 1 minuto.' ],500);


        $cliente = Cliente::where('cedula',$req->cedula)->first();
        if($cliente)
            return response()->json(['success'=>false,'message'=>'El cliente ya existe.'],403);

        return response()->json(['success'=>true,'message'=>'El cliente no existe.']);
    }

    public function sucursalesCercanas(){

    }

    public function lugarDeTrabajo(){

    }

    public function buscarCiudad(Request $req){

    }

    public function buscarBarrioPorCiudad(Request $req){

    }

    public function ciudades(){

    }

    public function barrios(){

    }

    public function verificarVersion(Request $req){

        $results = Version::where('dispositivo',$req->dispositivo)->first();

        return response()->json([
            'success'=>true,
            'results'=>$results
        ]);

    }

}
