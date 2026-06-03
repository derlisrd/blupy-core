<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use Illuminate\Http\Request;

class NotificacionesController extends Controller
{
    public function NotificacionesPorUser(Request $req){
        try {
            $user = $req->user();
            $results = Notificacion::where('user_id',$user->id)
            ->take(10)
            ->orderBy('id', 'desc')->get();

            return response()->json([
                'success'=>true,
                'results' =>$results
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function ContarNotificacionesNoLeidas(Request $req){
        try {
            $user = $req->user();
            $results = Notificacion::where('user_id',$user->id)
            ->where('leido',0)
            ->count();
            return response()->json([
                'success'=>true,
                'results' =>$results
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    public function marcarComoLeido(Request $req,String $id){
        try {
            $notificacion = Notificacion::where('id',$id)
            ->where('user_id',$req->user()->id)
            ->first();
            if(!$notificacion){
                return response()->json([
                    'success'=>false,
                    'message' =>'Notificación no encontrada'
                ],404);
            }
            $notificacion->leido = 1;
            $notificacion->save();
            return response()->json([
                'success'=>true,
                'message' =>'Notificación marcada como leída'
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
