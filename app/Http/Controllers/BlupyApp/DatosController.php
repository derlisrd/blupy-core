<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\HistorialDato;
use App\Models\User;
use App\Models\Validacion;
use App\Services\InfinitaService;
use App\Services\TigoSmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class DatosController extends Controller
{
    public function cambiarEmail(Request $req){
        $validator = Validator::make($req->all(),trans('validation.cambio.email'), trans('validation.cambio.email.messages'));
        if($validator->fails())
            return response()->json(['success'=>false,'messages'=>$validator->errors()->first() ], 400);

        $ip = $req->ip();
        $executed = RateLimiter::attempt($ip,$perTwoMinutes = 2,function() {});
        if (!$executed)
            return response()->json(['success'=>false, 'message'=>'Demasiadas peticiones. Espere 1 minuto.' ],500);

        $user = $req->user();
        if (!Hash::check($req->password, $user->password))
            return response()->json(['success'=>false,'message'=>'Contraseña incorrecta.'],401);

        if(!$user)
            return response()->json(['success'=>false, 'message'=>'No existe.' ],404);

        $cliente = $user->cliente;

        $randomNumber = random_int(100000, 999999);
        $this->enviarEmail($req->email,$randomNumber);
        $validacion = Validacion::create(['codigo'=>$randomNumber,'forma'=>0,'email'=>$req->email,'cliente_id'=>$cliente->id]);

        return response()->json(['success' =>true,'results'=>['id'=>$validacion->id],'message'=>'Email enviado']);
    }

    public function confirmaCambiarEmail(Request $req){
        $validator = Validator::make($req->all(),trans('validation.verificaciones.confirmar'), trans('validation.verificaciones.confirmar.messages'));
        if($validator->fails())
                return response()->json(['success'=>false,'messages'=>$validator->errors()->first() ], 400);

        $ip = $req->ip();
        $executed = RateLimiter::attempt($ip,$perTwoMinutes = 3,function() {});
        if (!$executed)
            return response()->json(['success'=>false, 'message'=>'Demasiadas peticiones. Espere 1 minuto.' ],500);

        $validacion = Validacion::where('id',$req->id)->where('validado',0)->where('codigo',$req->codigo)->first();
        if(!$validacion)
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
        $this->cambiosEnInfinita($user->cliente->cliid,$req->email,null);
        return response()->json(['success'=>true,'message'=>'Email ha cambiado.']);
    }

    public function cambiarCelular(Request $req){

    }

    public function confirmaCambiarCelular(Request $req){

    }

    private function enviarEmail(String $email, int $code){
        $datos = [
            'email'=>$email,
            'code'=>$code
        ];
        try {
            Mail::send('email.validar', ['code'=>$code], function ($message) use($datos) {
                $message->subject('['.$datos['code'].'] Blupy confirmacion');
                $message->to($datos['email']);
            });
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    private function enviarMensajeDeTexto(String $celular, int $code){
        try {
            $hora = Carbon::now()->format('H:i');
            $mensaje = "$code es tu codigo de verificacion de BLUPY. ". $hora  ;
            $tigoService = new TigoSmsService();
            $tigoService->enviarSms($celular,$mensaje);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    private function cambiosEnInfinita($cliid, $email,$telefono){

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
         $modificarCliente = $webserviceInfinita->ModificarCliente($cliid,$clienteModificado);
         Log::info($modificarCliente);
    }

}
