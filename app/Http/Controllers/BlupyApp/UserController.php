<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Validacion;
use App\Services\InfinitaService;
use App\Services\TigoSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class UserController extends Controller
{
    private $infinitaService;
    private $tigoService;

    public function __construct()
    {
        $this->infinitaService = new InfinitaService();
        $this->tigoService = new TigoSmsService();
    }

    public function cambiarContrasena(Request $req){
        $user = $req->user();
        $cliente = $user->cliente;
        $validator = Validator::make($req->all(), trans('validation.user.newpassword'),trans('validation.user.newpassword.messages'));

        if($validator->fails())
            return response()->json(['success'=>false,'messages'=>$validator->errors()->first() ], 400);


        if(!$cliente)
            return response()->json(['success'=>false,'message'=>'No existe cliente'],404);

        if (!Hash::check($req->old_password, $user->password))
            return response()->json(['success'=>false,'message'=>'Contraseña incorrecta.'],401);


        $user->update(['password' => Hash::make($req->password)]);

        return response()->json(['success' => true,'message' => 'Contraseña actualizada!']);
    }

    public function cambiarEmail(Request $req){

    }

    // cambiar celular o telefono
    public function cambiarCelular(){

    }


    private function enviarEmailRecuperacion(String $email, int $code){
        try {
            Mail::send('email.recuperarcontrasena', ['code'=>$code], function ($message) use($email) {
                $message->subject('Blupy: recuperar contraseña');
                $message->to($email);
            });
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    private function enviarMensajeDeTextoRecuperacion(String $celular, int $code){
        try {
            $hora = Carbon::now()->format('H:i');
            $mensaje = "$code es tu codigo de recuperacion de BLUPY. ". $hora  ;
            $this->tigoService->enviarSms($celular,$mensaje);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    private function cambiosEnInfinita(){

    }


}
