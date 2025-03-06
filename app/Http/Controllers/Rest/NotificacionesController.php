<?php

namespace App\Http\Controllers\Rest;


use App\Http\Controllers\Controller;
use App\Jobs\NotificacionesJobs;
use App\Models\Device;
use App\Models\User;
use App\Services\PushExpoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificacionesController extends Controller
{
    public function individual(Request $req){
        $validator = Validator::make($req->all(),['id'=>'required|exists:users,id','title'=>'required','body'=>'required']);
        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

        try {
            $user = User::find($req->id);
            $notificacion = new PushExpoService();
            $to = Device::where('user_id', $req->id)->whereNotNull('notitoken')->pluck('notitoken')->toArray();
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
}
