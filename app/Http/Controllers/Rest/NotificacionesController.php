<?php

namespace App\Http\Controllers\Rest;

use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use App\Jobs\NotificacionesJobs;
use App\Models\Device;
use App\Models\User;
use App\Services\PushExpoService;
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
            $notificacion = new PushExpoService();
            $to = $user->notitokens;
            $notificacion->send(
                $to,
                $req->title,
                $req->body
            );
            return response()->json(['success'=>true,'message'=>'Notificacion enviada con exito']);

        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['success'=>false,'message'=>'Error de servidor'],500);
        }
    }

    public function enviarNotificacionesMasivas(Request $req){
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
        $tokens = Device::pluck('notitoken')->toArray();
        NotificacionesJobs::dispatch($req->title,$req->text,$tokens)->onConnection('database');
        return response()->json(['success'=>true,'message'=>'Notificaciones enviadas']);
    }

    public function enviarNotificacionSelectiva(Request $req){

    }
}
