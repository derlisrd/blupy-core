<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\User;
use App\Models\Validacion;
use App\Services\EmailService;
use App\Services\SupabaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;

class DeviceController extends Controller
{
    public function codigoNuevoDispositivo(Request $req){
        try {
            $validator = Validator::make($req->all(),trans('validation.device.codigo'), trans('validation.device.codigo.messages'));
            if($validator->fails())
                return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);
            $ip = $req->ip();
            $executed = RateLimiter::attempt($ip,$perTwoMinutes = 2,function() {});
            if (!$executed)
                return response()->json(['success'=>false, 'message'=>'Demasiadas peticiones. Espere 1 minuto.' ],500);
            $id = null;
            $user = User::where('email',$req->email)->first();
            if($user){
                $randomNumber = random_int(100000, 999999);
                $emailService = new EmailService();
                $cliente = $user->cliente;
                $emailService->enviarEmail($req->email,"[".$randomNumber."]Blupy confirmar dispositivo",'email.validarDispositivo',['code'=>$randomNumber]);
                $validacion = Validacion::create(['codigo'=>$randomNumber,'forma'=>0,'email'=>$req->email,'cliente_id'=>$cliente->id,'origen'=>'dispositivo']);
                $id = $validacion->id;
            }

            return response()->json(['success'=>true,'results'=>['id'=>$id], 'message'=>'Te hemos enviado un correo electronico para verificar tu cuenta']);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function confirmarNuevoDispositivo(Request $req){
        try {
            $validator = Validator::make($req->all(),trans('validation.device.confirmar'), trans('validation.device.confirmar.messages'));
            if($validator->fails())
                return response()->json(['success'=>false,'message'=>$validator->errors()->first()], 400);

            $id = $req->id;
            $codigo = $req->codigo;
            $ip = $req->ip();
            $notitoken = $req->notitoken;
            $device = $req->device;
            $web = $req->web ? 1 : 0;
            $model = $req->model;
            $desktop = $req->desktop ? 1 : 0;
            $confianza = $req->confianza ? 1 : 0;

            $validacion = Validacion::where('id',$id)->where('codigo',$codigo)->where('validado',0)->latest('created_at')->first();
            if(!$validacion)
                return response()->json(['success'=>false,'message'=>'Código inválido.'],400);

            $fechaInicial = Carbon::parse($validacion->created_at);
            $fechaActual = Carbon::now();
            $diferenciaEnMinutos = $fechaInicial->diffInMinutes($fechaActual);

            if ($diferenciaEnMinutos >= 10)
                return response()->json(['success'=>false,'message'=>'Código ha expirado'],401);

            $validacion->validado = 1;
            $validacion->save();
            $user = User::where('cliente_id',$validacion->cliente_id)->first();
            Device::create([
                'device'=>$device,
                'notitoken'=>$notitoken,
                'user_id'=>$user->id,
                'web'=>$web,
                'model'=>$model,
                'ip'=>$ip,
                'desktop'=>$desktop,
                'confianza'=>$confianza
            ]);

            return response()->json(['success'=>true,'message'=>'Dispositivo registrado correctamente']);

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
