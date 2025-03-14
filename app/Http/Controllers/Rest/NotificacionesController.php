<?php

namespace App\Http\Controllers\Rest;


use App\Http\Controllers\Controller;
use App\Jobs\NotificacionesJobs;
use App\Models\Cliente;
use App\Models\Device;
use App\Models\User;
use App\Services\PushExpoService;
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
            $user = User::find($req->id);
            $toDevice = Device::where('id',$user->device_id)->first();
            return response()->json(['success'=>true,'message'=>'Notificacion enviada con exito', 'results'=>$toDevice]);

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
        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=> $validator->errors()->first()
            ], 400);
        }

        $datos = [
            'title' => $req->title,
            'text' => $req->text
        ];
        $tokens = Device::whereNotNull('notitoken')->pluck('notitoken')->toArray();

        NotificacionesJobs::dispatch($req->title,$req->text,$tokens)->onConnection('database');
        return response()->json(['success'=>true,'message'=>'Notificaciones enviadas en 2do plano']);
    }

    public function selectiva(Request $req){

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
