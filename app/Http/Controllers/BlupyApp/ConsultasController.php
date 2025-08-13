<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Barrio;
use App\Models\Ciudad;
use App\Models\Cliente;
use App\Models\Version;
use App\Services\InfinitaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        // Control de tasa de peticiones por IP
            $ip = $req->ip();
            $maxAttempts = 6;
            $decaySeconds = 120;

            if (!RateLimiter::attempt($ip, $maxAttempts, fn() => null, $decaySeconds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Demasiadas peticiones. Intente de nuevo en 1 minuto.'
                ], 429); // Too Many Requests
            }

            // Verificar si el cliente ya existe
            $clienteExiste = Cliente::join('users', 'users.cliente_id', '=', 'clientes.id')
            ->where('clientes.cedula', $req->cedula)
            ->where('users.rol', 0)
            ->exists();

        if($clienteExiste)
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

    public function barrios(){
        $barrios = Cache::remember('barrios', now()->addDays(30), function () {
            return Barrio::all();
        });
        
        return response()->json(['success' => true, 'results' => $barrios]);
    }
    
    public function ciudades(){
        $ciudades = Cache::remember('ciudades', now()->addDays(30), function () {
            return Ciudad::all();
        });
        
        return response()->json(['success' => true, 'results' => $ciudades]);
    }

    public function verificarVersion(Request $req){

        $results = Version::where('dispositivo',$req->dispositivo)->first();

        return response()->json([
            'success'=>true,
            'results'=>$results
        ]);

    }

}
