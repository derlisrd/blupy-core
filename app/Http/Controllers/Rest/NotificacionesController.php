<?php

namespace App\Http\Controllers\Rest;


use App\Http\Controllers\Controller;
use App\Jobs\PushNativeJobs;
use App\Models\Cliente;
use App\Models\Device;
use App\Services\InfinitaService;
use App\Services\NotificationService;
use App\Services\PushExpoService;
use App\Services\SupabaseService;
use App\Services\TigoSmsService;
use App\Services\WaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificacionesController extends Controller
{

    public function wa(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'title' => 'required',
            'body' => 'required',
            'number' => 'required'
        ]);
        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        //$res = app(TigoSmsService::class)->enviarSms($req->number, $req->text);
        $text = $req->title . ' ' . $req->body;
        $res = app(WaService::class)->send($req->number, $text);
        return response()->json([
            'success' => true,
            'message' => 'Mensaje enviado',
            'results' => $res
        ], 200);
    }
    public function enviarSms(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'text' => 'required',
            'number' => 'required'
        ]);
        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        $res = app(TigoSmsService::class)->enviarSms($req->number, $req->text);
        return response()->json([
            'success' => true,
            'message' => 'Mensaje enviado',
            'results' => $res
        ], 200);
    }

    public function individual(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'device_id' => 'required|exists:devices,id',
            'title' => 'required',
            'body' => 'required'
        ]);
        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        try {
            $device = Device::find($req->device_id);

            if($device->os == 'android'){
                (new NotificationService())->sendPushAndroid([
                    'tokens' => [$device->devicetoken],
                    'title' => $req->title,
                    'body' => $req->body,
                    'type' => 'android',
                ]);
            }
            if($device->os == 'ios'){
                PushNativeJobs::dispatch($req->title, $req->body, [
                    $device->devicetoken
                ], 'ios')->onConnection('database');
            }
            return response()->json(['success' => true, 'message' => 'Notificaciones enviadas en 2do plano','results'=>[
                
                'device' => $device
            ] ]);
        } catch (\Throwable $th) {
            throw $th;
            return response()->json(['success' => false, 'message' => 'Error de servidor. No se pudo enviar.'], 500);
        }
    }

    public function difusion(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'title' => 'required',
            'text' => 'required'
        ]);
        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        try {

            //$expotokens = Device::whereNotNull('notitoken')->pluck('notitoken')->toArray();

            $androidDevices = Device::where('os', 'android')
                ->whereNotNull('devicetoken')
                ->pluck('devicetoken')
                ->toArray();

            $iosDevices = Device::where('os', 'ios')
                ->whereNotNull('devicetoken')
                ->pluck('devicetoken')
                ->toArray();
            //NotificacionesJobs::dispatch($req->title, $req->text, $expotokens)->onConnection('database');
            PushNativeJobs::dispatch($req->title, $req->text, $androidDevices, 'android')->onConnection('database');
            PushNativeJobs::dispatch($req->title, $req->text, $iosDevices, 'ios')->onConnection('database');
            return response()->json(['success' => true, 'message' => 'Notificaciones enviadas en 2do plano']);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['success' => false, 'message' => 'Error al enviar notificaciones: ' . $th->getMessage()], 500);
        }
    }

    public function difusionSelectiva(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'title' => 'required',
            'text' => 'required'
        ]);
        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        try {
            
            $devices = Cliente::join('users', 'users.cliente_id', '=', 'clientes.id')
            ->where('clientes.digital', 1)
            ->join('devices', 'users.id', '=', 'devices.user_id')
            ->whereNotNull('devices.devicetoken')
            ->select('devices.notitoken', 'devices.os', 'devices.devicetoken')
            ->get();
            $androidDevices = $devices->where('os', 'android')->pluck('devicetoken')->toArray();
            $iosDevices = $devices->where('os', 'ios')->pluck('devicetoken')->toArray();
            //$expo = $devices->pluck('notitoken')->toArray();
            //$expotokens = Device::whereNotNull('notitoken')->pluck('notitoken')->toArray();

            //NotificacionesJobs::dispatch($req->title, $req->text, $devices)->onConnection('database');
            PushNativeJobs::dispatch($req->title,$req->text,$androidDevices,'android')->onConnection('database');
            PushNativeJobs::dispatch($req->title,$req->text,$iosDevices,'ios')->onConnection('database'); 
            return response()->json(['success' => true, 'message' => 'Notificaciones enviadas en 2do plano','results'=>[
                
                'android' => $androidDevices,
                'ios' => $iosDevices
            ] ]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['success' => false, 'message' => 'Error al enviar notificaciones: ' . $th->getMessage()], 500);
        }
    }



    public function ficha(Request $req)
    {
        $validator = Validator::make($req->all(), ['cedula' => 'required']);
        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        $cliente = Cliente::where('cedula', $req->cedula)->first();

        if (!$cliente) {
            return response()->json(['success' => false, 'message' => 'Cliente no existe'], 404);
        }
        $user = $cliente->user;
        $devices = Device::where('user_id', $user->id)->get();
        $infinitaRes =  (new InfinitaService())->ListarTarjetasPorDoc($req->cedula);
        $micredidoData = (object)$infinitaRes['data'];
        $miCreditoResult = null;
        if (property_exists($micredidoData, 'Tarjetas')) {
            $miCreditoResult = $micredidoData->Tarjetas[0];
        }
        $results = [
            'cliente_id' => $cliente->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'celular' => $cliente->celular,
            'cedula' => $cliente->cedula,
            'devices' => $devices,
            'micredito' => $miCreditoResult,
        ];
        return response()->json(['success' => true, 'message' => 'Datos del cliente', 'results' => $results]);
    }
}
