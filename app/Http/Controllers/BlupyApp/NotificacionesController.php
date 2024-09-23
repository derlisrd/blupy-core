<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use Illuminate\Http\Request;

class NotificacionesController extends Controller
{
    public function notificaciones(Request $req){
        $user = $req->user;

        $results = Notificacion::where('user_id',$user->id)->get();

        return response()->json([
            'success'=>true,
            'results' =>$results
        ]);
    }
}
