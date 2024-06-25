<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\InfinitaService;
use App\Services\FarmaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Aws\Rekognition\RekognitionClient;

class ConsultasController extends Controller
{
    private $infinitaService;
    private $farmaService;

    public function __construct()
    {
        $this->infinitaService = new InfinitaService();
        $this->farmaService = new FarmaService();
    }


    public function verificarDocumento(Request $req){
        $validator = Validator::make($req->all(),trans('validation.verify.documento'),trans('validation.verify.documento.messages'));

        if($validator->fails()) return response()->json(['success'=>false,'messages'=>$validator->errors()->first() ], 400);

        $ip = $req->ip();
        $executed = RateLimiter::attempt($ip,5,function() {});
        if (!$executed) return response()->json(['success'=>false, 'message'=>'Demasiadas peticiones. Espere 1 minuto.' ],500);


        $cliente = Cliente::where('cedula',$req->cedula)->first();
        if($cliente) return response()->json(['success'=>true,'message'=>'El cliente ya existe.'],403);

        return response()->json(['success'=>false,'message'=>'El cliente no existe.'],404);
    }





    public function scanearDocumento(Request $req){
        $validator = Validator::make($req->all(),trans('validation.verify.scan'),trans('validation.verify.scan.messages'));

        if($validator->fails()) return response()->json(['success' => false, 'message' => $validator->errors()->first()],400);

        $amazon = new RekognitionClient([
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'version' => 'latest',
        ]);

    }





    public function ciudades(){

    }

    public function barrios(){

    }

}
