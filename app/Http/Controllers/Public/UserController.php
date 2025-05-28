<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Validacion;
use App\Services\EmailService;
use App\Services\SupabaseService;
use App\Services\TigoSmsService;
use App\Services\WaService;
use App\Traits\Helpers;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;

class UserController extends Controller
{

    use Helpers;


    // aqui recibe cedula y forma de recuperar
    public function olvideContrasena(Request $req){

        $validator = Validator::make($req->all(),trans('validation.verify.olvide'),trans('validation.verify.olvide.messages'));
        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

        $ip = $req->ip();
        $executed = RateLimiter::attempt($ip,5,function() {});
        if (!$executed)
            return response()->json(['success'=>false, 'message'=>'Demasiadas peticiones. Espere 1 minuto.' ],500);

        try {
            $cliente = Cliente::where('cedula',$req->cedula)->first();
            if(!$cliente)
                return response()->json(['success'=>false,'message'=>'No existe registro en nuestras bases de datos.'],404);

            $user = $cliente->user;
            if(!$user || $user->active == 0)
                return response()->json(['success'=>false,'message'=>'No existe registro en nuestras bases de datos.'],404);

            $randomNumber = random_int(1000, 9999);
            $forma = '';
            if($req->forma == 0){
                $forma = $this->ocultarParcialmenteEmail($user->email);
                $emailService = new EmailService();
                $emailService->enviarEmail($user->email,'Blupy: recupera tu contraseña','email.recuperarcontrasena',['code'=>$randomNumber]);
                $validacion = Validacion::create(['codigo'=>$randomNumber,'forma'=>0,'email'=>$user->email,'cliente_id'=>$cliente->id,'origen'=>'rec. por email']);
            }
            if($req->forma == 1){
                $forma = $this->ocultarParcialmenteTelefono($user->cliente->celular);
                $this->enviarMensajeDeTextoRecuperacion($user->cliente->celular,$randomNumber);
                $mensaje = "Blupy te ha enviado el código $randomNumber para restablecer tu contraseña";
                $numeroTelefonoWa = '595' . substr($user->cliente->celular, 1);
                (new WaService())->send($numeroTelefonoWa, $mensaje);
                $validacion = Validacion::create(['codigo'=>$randomNumber,'forma'=>1,'celular'=>$cliente->celular,'cliente_id'=>$cliente->id,'origen'=>'rec. por celular']);
            }



            return response()->json([
                'success'=>true,
                'results'=>[
                    'id'=>$validacion->id
                ],
                'message'=>'Código enviado correctamente al ' . $forma
            ]);
        } catch (\Throwable $th) {
            SupabaseService::LOG('olvido_password',$th);
            return response()->json(['success'=>false,'message'=>'Error de servidor. Intente en unos minutos.'],500);
        }
    }




    public function validarCodigoRecuperacion(Request $req){
        $validator = Validator::make($req->all(),trans('validation.verify.codigo'),trans('validation.verify.codigo.messages'));
        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

        $validacion = Validacion::where('id',$req->id)->where('codigo',$req->codigo)->where('validado',0)->first();
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

        $token = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => $token,
                'created_at' => Carbon::now()
            ]
        );

        return response()->json(['success'=>true,'results'=>[
            'token'=>$token
        ]]);
    }


    public function restablecerContrasena(Request $req){
        $validator = Validator::make($req->all(),trans('validation.user.resetpassword'),trans('validation.user.resetpassword.messages'));
        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

        $reset = DB::table('password_reset_tokens')->where([
            ['token', $req->token]
        ])->first();

        if (!$reset)
            return response()->json(['success'=>false,'message' => 'El código de verificación no es válido.'], 400);

        $currentTime = Carbon::now();
        $tokenTime = Carbon::parse($reset->created_at);

        if ($currentTime->diffInMinutes($tokenTime) > 15){
            DB::table('password_reset_tokens')->where('email', $reset->email)->delete();
            return response()->json(['success'=>false,'message' => 'El código de verificación ha expirado.'], 400);
        }

        $user = User::where('email', $reset->email)->first();
        $user->password = Hash::make($req->password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $reset->email)->delete();

        return response()->json(['success'=>true,'message' => 'La contraseña se ha restablecido con éxito.']);


    }


    private function enviarMensajeDeTextoRecuperacion(String $celular, int $code){
        try {
            //$hora = Carbon::now()->format('H:i');
            $mensaje = "Blupy te ha enviado el código $code para restablecer tu contraseña";
            $numero = str_replace('+595', '0', $celular);
            $tigoService = new TigoSmsService();
            $tigoService->enviarSms($numero,$mensaje);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
