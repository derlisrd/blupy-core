<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\HistorialDato;
use App\Models\User;
use App\Models\Validacion;
use App\Services\EmailService;
use App\Services\InfinitaService;
use App\Services\TigoSmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class DatosController extends Controller
{
    public function solicitarCambiarEmail(Request $req){
        try {
            $validator = Validator::make($req->all(),trans('validation.cambio.email'), trans('validation.cambio.email.messages'));
            if($validator->fails())
                return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

            $ip = $req->ip();
            $executed = RateLimiter::attempt($ip,$perTwoMinutes = 5,function() {});
            if (!$executed)
                return response()->json(['success'=>false, 'message'=>'Demasiadas peticiones. Espere 1 minuto.' ],500);

            $user = $req->user();
            if (!Hash::check($req->password, $user->password))
                return response()->json(['success'=>false,'message'=>'Contraseña incorrecta.'],401);

            if(!$user)
                return response()->json(['success'=>false, 'message'=>'No existe.' ],404);

            $cliente = $user->cliente;

            $randomNumber = random_int(100000, 999999);
            $emailService = new EmailService();
            $emailService->enviarEmail($req->email,"[".$randomNumber."]Blupy confirmar email",'email.validar',['code'=>$randomNumber]);
            $validacion = Validacion::create(['codigo'=>$randomNumber,'forma'=>0,'email'=>$req->email,'cliente_id'=>$cliente->id]);

            return response()->json(['success' =>true,'results'=>null,'message'=>'Hemos enviado un email con el codigo']);

        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['success'=>false,'message'=>'Error de servidor']);
        }
    }





    public function confirmarCambiarEmail(Request $req){
        try {
            $validator = Validator::make($req->all(),trans('validation.verificaciones.confirmar'), trans('validation.verificaciones.confirmar.messages'));
            if($validator->fails())
                return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

            $ip = $req->ip();
            $executed = RateLimiter::attempt($ip,$perTwoMinutes = 3,function() {});
            if (!$executed)
                return response()->json(['success'=>false, 'message'=>'Demasiadas peticiones. Espere 1 minuto.' ],500);

            $user = $req->user();
            $cliente = $user->cliente;
            $validacion = Validacion::where('cliente_id',$cliente->id)->where('validado',0)->latest('created_at')->first();
            if(!$validacion || $validacion->codigo != $req->codigo)
                return response()->json(['success'=>false,'message'=>'Codigo invalido'],403);



            $fechaCreado = Carbon::parse($validacion->created_at);
            $fechaActual = Carbon::now();
            $diferenciaEnMinutos = $fechaCreado->diffInMinutes($fechaActual);

            if ($diferenciaEnMinutos >= 10)
                return response()->json(['success'=>false,'message'=>'Código ha expirado'],401);

            $validacion->validado = 1;
            $validacion->save();

            $user = $req->user();
            HistorialDato::create([
                'user_id'=>$user->id,
                'email'=>$user->email
            ]);
            $user->email = $validacion->email;
            $user->save();
            $this->cambiosEnInfinita($user->cliente->cliid,$validacion->email,null);
            return response()->json(['success'=>true,'message'=>'El email se ha cambiado correctamente.']);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['success'=>false,'message'=>'Error de servidor']);
        }
    }




    public function solicitarCambiarCelular(Request $req){
        try {
            $validator = Validator::make($req->all(),trans('validation.cambio.celular'), trans('validation.cambio.celular.messages'));
            if($validator->fails())
                return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

            $ip = $req->ip();
            $executed = RateLimiter::attempt($ip,$perTwoMinutes = 4,function() {});
            if (!$executed)
                return response()->json(['success'=>false, 'message'=>'Demasiadas peticiones. Espere 1 minuto.' ],500);

            $user = $req->user();
            if (!Hash::check($req->password, $user->password))
                return response()->json(['success'=>false,'message'=>'Contraseña incorrecta.'],401);

            if(!$user)
                return response()->json(['success'=>false, 'message'=>'No existe.' ],404);

            $cliente = $user->cliente;

            $randomNumber = random_int(100000, 999999);
            $tigoService = new TigoSmsService();
            $hora = Carbon::now()->format('H:i');
            $mensaje = $randomNumber." es tu codigo de verificacion de BLUPY. ". $hora  ;
            $tigoService->enviarSms($req->celular,$mensaje);

            Validacion::create(['codigo'=>$randomNumber,'forma'=>1,'celular'=>$req->celular,'cliente_id'=>$cliente->id]);

            return response()->json(['success' =>true,'results'=>null,'message'=>'Mensaje enviado']);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['success'=>false,'message'=>'Error de servidor']);
        }
    }

    public function confirmarCambiarCelular(Request $req){
        $validator = Validator::make($req->all(),trans('validation.verificaciones.confirmar'), trans('validation.verificaciones.confirmar.messages'));
        if($validator->fails())
                return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

        $ip = $req->ip();
        $executed = RateLimiter::attempt($ip,$perTwoMinutes = 3,function() {});
        if (!$executed)
            return response()->json(['success'=>false, 'message'=>'Demasiadas peticiones. Espere 1 minuto.' ],500);

        $user = $req->user();
        $cliente = $user->cliente;
        $validacion = Validacion::where('cliente_id',$cliente->id)->where('validado',0)->latest('created_at')->first();
        if(!$validacion || $validacion->codigo != $req->codigo)
                return response()->json(['success'=>false,'message'=>'Codigo invalido'],403);

        $fechaCreado = Carbon::parse($validacion->created_at);
        $fechaActual = Carbon::now();
        $diferenciaEnMinutos = $fechaCreado->diffInMinutes($fechaActual);

        if ($diferenciaEnMinutos >= 10)
            return response()->json(['success'=>false,'message'=>'Código ha expirado'],401);

        $validacion->validado = 1;
        $validacion->save();

        $user = $req->user();
        $clienteId = $user->cliente->id;
        HistorialDato::create([
            'user_id'=>$user->id,
            'celular'=>$user->cliente->celular
        ]);
        Cliente::find($clienteId)->update([
            'celular'=>$validacion->celular
        ]);
        $this->cambiosEnInfinita($user->cliente->cliid,null,$validacion->celular);
        return response()->json(['success'=>true,'message'=>'Telefono celular ha cambiado.']);
    }



    private function cambiosEnInfinita($cliid, $email,$telefono) : void{

        $webserviceInfinita = new InfinitaService();

        $cliente = (object) $webserviceInfinita->TraerDatosCliente($cliid);
        $clienteDatos = (object) $cliente->data;

        $cliObj = (object)$clienteDatos->wCliente;

        $telefonoNuevo = [
            (object)[
                'CliTelId'=>$cliObj->Tel[0]['CliTelId'],
                'CliTelNot'=>$cliObj->Tel[0]['CliTelNot'],
                'CliTelUb'=>$cliObj->Tel[0]['CliTelUb'],
                'CliTelef'=> $telefono ?  $telefono : $cliObj->Tel[0]['CliTelef']
            ]
        ];

        $clienteModificado = [
            'ActComId'=>0,
            'CliApe'=>$cliObj->CliApe,
            'CliApe1'=>$cliObj->CliApe1,
            'CliApe2'=>$cliObj->CliApe2,
            'CliApe3'=>$cliObj->CliApe3,
            'CliCobId'=>$cliObj->CliCobId,
            'CliContrib'=>$cliObj->CliContrib,
            'CliCumple'=>$cliObj->CliCumple,
            'CliDocDv'=>$cliObj->CliDocDv,
            'CliDocu'=>$cliObj->CliDocu,
            'CliEdad'=>$cliObj->CliEdad,
            'CliEmail'=> $email ? $email :  $cliObj->CliEmail,
            'CliEsAso'=>$cliObj->CliEsAso,
            'CliEstCiv'=>$cliObj->CliEstCiv,
            'CliEstado'=>$cliObj->CliEstado,
            'CliFecNac'=>$cliObj->CliFecNac,
            'CliIVAEx'=>$cliObj->CliIVAEx,
            'CliId'=>$cliObj->CliId,
            'CliLabCar'=>$cliObj->CliLabCar,
            'CliLabFecIng'=>$cliObj->CliLabFecIng,
            'CliLabLug'=>$cliObj->CliLabLug,
            'CliLabSal'=>$cliObj->CliLabSal,
            'CliLabSec'=>$cliObj->CliLabSec,
            'CliLisPreId'=>$cliObj->CliLisPreId,
            'CliNacId'=>$cliObj->CliNacId,
            'CliNom'=>$cliObj->CliNom,
            'CliNom1'=>$cliObj->CliNom1,
            'CliNom2'=>$cliObj->CliNom2,
            'CliNomFan'=>$cliObj->CliNomFan,
            'CliNombre'=>$cliObj->CliNombre,
            'CliNro'=>$cliObj->CliNro,
            'CliObs'=>$cliObj->CliObs,
            'CliProfId'=>$cliObj->CliProfId,
            'CliRUC'=>$cliObj->CliRUC,
            'CliRazon'=>$cliObj->CliRazon,
            'CliSepBi'=>$cliObj->CliSepBi,
            'CliSexo'=>$cliObj->CliSexo,
            'CliTipEst'=>$cliObj->CliTipEst,
            'CliTipId'=>$cliObj->CliTipId,
            'CliTipImg'=>$cliObj->CliTipImg,
            'CliTipViv'=>$cliObj->CliTipViv,
            'CliTipo'=>$cliObj->CliTipo,
            'CliVenId'=>$cliObj->CliVenId,
            'Dir'=>$cliObj->Dir,
            'LugTrabDir'=>$cliObj->LugTrabDir,
            'LugTrabId'=>$cliObj->LugTrabId,
            'LugTrabTel'=>$cliObj->LugTrabTel,
            'Tel'=> $telefono ? $telefonoNuevo : $cliObj->Tel,
            'TipDocId'=>$cliObj->TipDocId
        ];
         $webserviceInfinita->ModificarCliente($cliid,$clienteModificado);

    }

}
