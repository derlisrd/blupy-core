<?php

namespace App\Http\Controllers\Rest;


use App\Http\Controllers\Controller;
use App\Jobs\NotificacionesJobs;
use App\Jobs\PushNativeJobs;
use App\Models\Cliente;
use App\Models\Device;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PushExpoService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificacionesController extends Controller
{
    public function individual(Request $req){
        $validator = Validator::make($req->all(),[
            'device_id'=>'required|exists:devices,id',
            'title'=>'required',
            'body'=>'required']);
        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

        try {
            $toDevice = Device::find($req->device_id);

            $notiService = new NotificationService();
            $res = $notiService->sendPush([
                'tokens' => [$toDevice->devicetoken],
                'title' => $req->title,
                'body' => $req->body,
                'type' => $toDevice->os,
            ]);
            SupabaseService::LOG('devitoken',$toDevice->devicetoken);
            $expoService = new PushExpoService();
            $expoService->send(
                [$toDevice->notitoken],
                $req->title,
                $req->body,
                [
                    'info'=>'notificaciones',
                    'title'=>$req->title,
                    'body'=>$req->body
                ]
            );

            return response()->json(['success'=>true,'message'=>'Notificacion enviada con exito', 'results'=>$res['data']],$res['status']);

        } catch (\Throwable $th) {
            throw $th;
            return response()->json(['success'=>false,'message'=>'Error de servidor. No se pudo enviar.'],500);
        }
    }

    public function difusion(Request $req){
        $validator = Validator::make($req->all(), [
            'title' => 'required',
            'text' => 'required'
        ]);
        if ($validator->fails())
            return response()->json(['success'=>false,'message'=> $validator->errors()->first()], 400);

        try {
            $datos = [
                'title' => $req->title,
                'text' => $req->text
            ];

            $expotokens = Device::whereNotNull('notitoken')->pluck('notitoken')->toArray();

            $androidDevices = Device::where('os', 'android')
                           ->whereNotNull('devicetoken')
                           ->pluck('devicetoken')
                           ->toArray();

            $iosDevices = Device::where('os', 'ios')
                        ->whereNotNull('devicetoken')
                        ->pluck('devicetoken')
                        ->toArray();
            NotificacionesJobs::dispatch($req->title,$req->text,$expotokens)->onConnection('database');
            PushNativeJobs::dispatch($req->title,$req->text,$androidDevices,'android')->onConnection('database');
            PushNativeJobs::dispatch($req->title,$req->text,$iosDevices,'ios')->onConnection('database');
            return response()->json(['success'=>true,'message'=>'Notificaciones enviadas en 2do plano']);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['success' => false, 'message' => 'Error al enviar notificaciones: ' . $th->getMessage()], 500);
        }
    }

    public function difusionSelectiva(Request $req){
        $validator = Validator::make($req->all(), [
            'title' => 'required',
            'text' => 'required'
        ]);
        if ($validator->fails())
            return response()->json(['success'=>false,'message'=> $validator->errors()->first()], 400);

        try {
            $datos = [
                'title' => $req->title,
                'text' => $req->text
            ];
            $expoDigital = Cliente::join('users','users.cliente_id','=','clientes.id')
            ->where('clientes.digital',1)
            ->join('devices','users.id','=','devices.user_id')
            //->select('devices.devicetoken','devices.notitoken')
            ->pluck('devices.notitoken')->toArray();
            //$expotokens = Device::whereNotNull('notitoken')->pluck('notitoken')->toArray();

            //NotificacionesJobs::dispatch($req->title,$req->text,$expoDigital)->onConnection('database');
            /*PushNativeJobs::dispatch($req->title,$req->text,$androidDevices,'android')->onConnection('database');
            PushNativeJobs::dispatch($req->title,$req->text,$iosDevices,'ios')->onConnection('database'); */
            return response()->json(['success'=>true,'message'=>'Notificaciones enviadas en 2do plano','results'=>$expoDigital]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['success' => false, 'message' => 'Error al enviar notificaciones: ' . $th->getMessage()], 500);
        }
    }



    public function ficha(Request $req){
        $validator = Validator::make($req->all(),['cedula'=>'required']);
        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

        $cliente = Cliente::where('cedula',$req->cedula)->first();

        if(!$cliente){
            return response()->json(['success'=>false,'message'=>'Cliente no existe'],404);
        }
        $user = $cliente->user;
        $devices = Device::where('user_id',$user->id)->get();
        $results = [
            'cliente_id'=>$cliente->id,
            'user_id'=>$user->id,
            'name'=>$user->name,
            'email'=>$user->email,
            'celular'=>$cliente->celular,
            'cedula'=>$cliente->cedula,
            'devices'=>$devices
        ];
        return response()->json(['success'=>true,'message'=>'Datos del cliente','results'=>$results]);

    }
}
