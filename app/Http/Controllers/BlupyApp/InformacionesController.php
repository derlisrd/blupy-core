<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Informacion;
use Illuminate\Http\Request;

class InformacionesController extends Controller
{
    public function infoPopUpInicial(Request $req){
        $user = $req->user();
        $general = Informacion::where('active',1)
        ->where('general',1)
        ->latest()->first();

        return response()->json([
            'success'=>true,
            'message'=>'',
            'results'=>[
                'general'=>$general,
                'user' =>$user->info()
            ]
        ]);
    }

    public function infoLista(Request $req){
        $user = $req->user();
        $results = Informacion::where('user_id', $user->id)
        ->where('id', $req->id)->get();
        return response()->json([
            'success'=>true,
            'results'=>$results
        ]);
    }

    public function marcarInfoLeida(Request $req){
        $user = $req->user();
        Informacion::where('user_id', $user->id)
        ->where('id', $req->id)
        ->update(['leido' => true]);


        return response()->json([
            'success'=>true,
            'message'=>'Leido'
        ]);

    }
}
