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
}
