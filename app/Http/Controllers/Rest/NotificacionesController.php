<?php

namespace App\Http\Controllers\Rest;

use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use App\Jobs\EnviarEmailJobs;
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
            $to = $user->notitokens();
            $notificacion->send(
                $to,
                $req->title,
                $req->body,
                [
                    'info'=>'notificaciones',
                    'body'=>$req->body,
                    'title'=>$req->title
                ]
            );
            return response()->json(['success'=>true,'message'=>'Notificacion enviada con exito', 'results'=>$to, 'user'=>$user]);

        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['success'=>false,'message'=>'Error de servidor. No se pudo enviar.'],500);
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
        $tokens = Device::whereNotNull('notitoken')->pluck('notitoken')->toArray();
        $emails = User::whereNotNull('email')->where('rol',0)->pluck('email')->toArray();
        NotificacionesJobs::dispatch($req->title,$req->text,$tokens)->onConnection('database')->onQueue('notificaciones');;
        EnviarEmailJobs::dispatch($req->title,$req->text,$emails)->onConnection('database');
        return response()->json(['success'=>true,'message'=>'Notificaciones enviadas en 2do plano']);
    }

    public function enviarNotificacionSelectiva(Request $req){

    }
}
