<?php

namespace App\Http\Controllers\Rest;

use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NotificacionesController extends Controller
{
    public function enviarNotificacion(Request $req){
        $validator = Validator::make($req->all(),['id'=>'required|exists:users,id','title'=>'required','body'=>'required']);
        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

        try {
            $user = User::find($req->id);
            $devices = $user->devices;
            $to = [];
            foreach($devices as $value){
                array_push($to, $value['notitoken']);
            }
            $url = env('PUSH_SERVICE');
            Http::post($url, [
                'to' => $to,
                'title' =>$req->title,
                'body'=>$req->body
            ],['Content-Type' => 'application/json']);
            return response()->json(['success'=>true,'message'=>'Notificacion enviada con exito']);

        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['success'=>false,'message'=>'Error de servidor'],500);
        }
    }

    public function notificacionesMasivas(Request $req){

    }
}
