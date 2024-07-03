<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\InfinitaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class UserController extends Controller
{
    private $infinitaService;

    public function __construct()
    {
        $this->infinitaService = new InfinitaService();
    }

    // aqui recibe cedula y forma de recuperar
    public function olvideContrasena(Request $req){
        $validator = Validator::make($req->all(),trans('validation.verify.olvide'),trans('validation.verify.olvide.messages'));
        if($validator->fails())return response()->json(['success'=>false,'messages'=>$validator->errors()->first() ], 400);
        $ip = $req->ip();
        $executed = RateLimiter::attempt($ip,5,function() {});
        if (!$executed) return response()->json(['success'=>false, 'message'=>'Demasiadas peticiones. Espere 1 minuto.' ],500);
        $cliente = Cliente::where('cedula',$req->cedula)->firstOrFail();
        $user = $cliente->user;
        if(!$user || $user->active == 0){
            return response()->json(['success'=>false,'message'=>'No hay registro'],404);
        }
        $randomNumber = random_int(100000, 999999);
        if($req->forma == 0){
            $this->enviarEmailRecuperacion($user->email,$randomNumber);
        }
        return response()->json([
            'success'=>true,
            'results'=>$user
        ]);
    }

    public function recuperarContrasenaPorEmail(Request $req){

    }

    public function recuperarContrasenaPorCelularSms(Request $req){

    }

    public function restablecerContrasena(Request $req){

    }

    public function cambiarContrasena(Request $req){

    }
    //
    public function cambiarEmail(Request $req){

    }

    // cambiar celular o telefono
    public function cambiarNumeroCelular(){

    }


    public function eliminarCuenta(){

    }

    public function generarCodigoEliminarCuenta(){

    }

    public function confirmarEliminarCuenta(){

    }

    private function enviarEmailRecuperacion(String $email, int $code){
        try {
            Mail::send('email.recuperarcontrasena', ['code'=>$code], function ($message) use($email) {
                $message->subject('Blupy: recuperar contraseña');
                $message->to($email);
            });
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'success'=>false,
                'message'=>'Error al enviar el email mas tarde'
            ],500);
        }
    }

    private function cambiosEnInfinita(){

    }


}
