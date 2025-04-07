<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Barrio;
use App\Models\Ciudad;
use App\Models\Cliente;
use App\Models\Version;
use App\Services\InfinitaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;


class ConsultasController extends Controller
{
    private $infinitaService;


    public function __construct()
    {
        $this->infinitaService = new InfinitaService();
    }





    public function verificarExisteDocumento(Request $req){
        $validator = Validator::make($req->all(),trans('validation.verify.documento'),trans('validation.verify.documento.messages'));

        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

        $ip = $req->ip();
        $executed = RateLimiter::attempt($ip,$perTwoMinutes = 6,function() {});
        if (!$executed)
            return response()->json(['success'=>false, 'message'=>'Demasiadas peticiones. Espere 1 minuto.' ],500);


        $cliente = Cliente::where('cedula',$req->cedula)->first();
        if($cliente)
            return response()->json(['success'=>false,'message'=>'El número de cédula de cliente ya existe.'],403);

        return response()->json(['success'=>true,'message'=>'El cliente no existe.']);
    }



    public function lugarDeTrabajo(){

    }

    public function profesiones(){
        try {
            $res = $this->infinitaService->listarProfesiones();
            $infinita = (object) $res['data'];
            if (property_exists($infinita, 'wDato')) {
                $results = [];
                foreach($infinita->wDato as $val){
                    $nuevo = $val;
                    $nuevo['id'] = (int) $val['DatoId'];
                    $nuevo['descripcion'] = $val['DatoDesc'];
                    array_push($results,$nuevo);
                }
                return response()->json([
                    'success' => true,
                    'results' => $results,
                    'message' => ''
                ]);
            }

            return response()->json([ 'success' => false,'message' => 'No se recuperaron registros'],404);
        } catch (\Throwable $th) {
            return response()->json(['success'=>false,'message'=>'Error de servidor.'],500);
        }
    }


    public function tiposLaboral(){
        try {
            $res = $this->infinitaService->listarTiposLaboral();
            $infinita = (object) $res['data'];
            $results = [];
            $success = false;
            $message = 'No se recuperaron registros';
            $status = 404;
            if (property_exists($infinita, 'wDato')) {
                foreach($infinita->wDato as $val){
                    $nuevo = $val;
                    $nuevo['id'] = (int) $val['DatoId'];
                    $nuevo['descripcion'] = $val['DatoDesc'];
                    array_push($results,$nuevo);
                }
                $success = true;
                $message = '';
                $status = 200;
            }

            return response()->json([ 'success' => $success,'message' =>$message,'results'=>$results ],$status);
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
