<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Barrio;
use App\Models\Ciudad;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Version;
use App\Services\InfinitaService;
use App\Services\FarmaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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


    public function verificarExisteTelefono(Request $req){
        $validator = Validator::make($req->all(),trans('validation.telefono.documento'),trans('validation.verify.telefono.messages'));

        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

        $ip = $req->ip();
        $executed = RateLimiter::attempt($ip,$perTwoMinutes = 8,function() {});
        if (!$executed)
            return response()->json(['success'=>false, 'message'=>'Demasiadas peticiones. Espere 1 minuto.' ],500);


        $cliente = Cliente::where('celular',$req->celular)->first();
        if($cliente)
            return response()->json(['success'=>false,'message'=>'El número de teléfono ya ha sido tomado.'],403);

        return response()->json(['success'=>true,'message'=>'Número libre.']);
    }


    public function verificarExisteEmail(Request $req){
        $validator = Validator::make($req->all(),trans('validation.verify.email'),trans('validation.verify.email.messages'));

        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

        $ip = $req->ip();
        $executed = RateLimiter::attempt($ip,$perTwoMinutes = 8,function() {});
        if (!$executed)
            return response()->json(['success'=>false, 'message'=>'Demasiadas peticiones. Espere 1 minuto.' ],500);


        $cliente = User::where('email',$req->email)->first();
        if($cliente)
            return response()->json(['success'=>false,'message'=>'El email ya ha sido tomado.'],403);

        return response()->json(['success'=>true,'message'=>'Email libre.']);
    }


    public function verificarExisteDocumento(Request $req){
        $validator = Validator::make($req->all(),trans('validation.verify.documento'),trans('validation.verify.documento.messages'));

        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

        $ip = $req->ip();
        $executed = RateLimiter::attempt($ip,$perTwoMinutes = 3,function() {});
        if (!$executed)
            return response()->json(['success'=>false, 'message'=>'Demasiadas peticiones. Espere 1 minuto.' ],500);


        $cliente = Cliente::where('cedula',$req->cedula)->first();
        if($cliente)
            return response()->json(['success'=>false,'message'=>'El número de cédula de cliente ya existe.'],403);

        return response()->json(['success'=>true,'message'=>'El cliente no existe.']);
    }

    public function sucursalesCercanas(){

    }

    public function lugarDeTrabajo(){

    }

    public function profesiones(){
        try {
            $res = (object)$this->infinitaService->listarProfesiones();
            $infinita = (object) $res->data;
            if (property_exists($infinita, 'wDato')) {
                $results = [];
                foreach($infinita->wDato as $val){
                    $nuevo = $val;
                    $nuevo['id'] = $val['DatoId'];
                    $nuevo['descripcion'] = $val['DatoDesc'];
                    array_push($results,$nuevo);
                }
                return response()->json([
                    'success' => true,
                    'results' => $results,
                ]);
            }

            return response()->json([ 'success' => false,'message' => 'No se recuperaron registros'],404);
        } catch (\Throwable $th) {
            return response()->json(['success'=>false,'message'=>'Error de servidor.'],500);
        }
    }


    public function tiposLaboral(){
        try {
            $res = (object)$this->infinitaService->listarTiposLaboral();
            $infinita = (object) $res->data;
            if (property_exists($infinita, 'wDato')) {
                $results = [];
                foreach($infinita->wDato as $val){
                    $nuevo = $val;
                    $nuevo['id'] = $val['DatoId'];
                    $nuevo['descripcion'] = $val['DatoDesc'];
                    array_push($results,$nuevo);
                }
                return response()->json([
                    'success' => true,
                    'results' => $results,
                ]);
            }

            return response()->json([ 'success' => false,'message' => 'No se recuperaron registros'],404);
        } catch (\Throwable $th) {
            return response()->json(['success'=>false,'message'=>'Error de servidor.'],500);
        }
    }


    public function barriosPorCiudad(Request $req){
        $results = Barrio::where('ciudad_id',$req->idCiudad)->get();
        return response()->json(['success'=>true,'results'=>$results]);
    }

    public function ciudades(){
        return response()->json(['success'=>true,'results'=>Ciudad::all()]);
    }


    public function verificarVersion(Request $req){

        $results = Version::where('dispositivo',$req->dispositivo)->first();

        return response()->json([
            'success'=>true,
            'results'=>$results
        ]);

    }

}
