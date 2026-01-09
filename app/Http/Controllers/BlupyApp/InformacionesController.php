<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Informacion;
use Illuminate\Http\Request;

class InformacionesController extends Controller
{
    public function infoPopUpInicial(Request $req){
        $user = $req->user();
        $cliente = $user->cliente;
        $general = Informacion::where('active',1)
        ->where('general',1)
        ->latest()->first();
        $digital = null;
        if($cliente->digital == 1){
            $digital = Informacion::where('active',1)
            ->where('digital',1)->latest()->first();
        }
        $resultsAceptado = null;
        if($cliente->aceptado == 0){
            $resultsAceptado = [
                'id'=>1,
                'user_id'=>$user->id,
                'title'=>'Términos y condiciones',
                'description'=>'Para continuar utilizando la aplicación, es necesario que aceptes los términos y condiciones actualizados.',
                'text'=>'Para continuar utilizando la aplicación, es necesario que aceptes los términos y condiciones actualizados.',
                'url'=>'https://www.blupy.com/terminos-y-condiciones',
                'imgUrl'=>null
            ];
        } 

        return response()->json([
            'success'=>true,
            'message'=>'',
            'results'=>[
                'general'=>$general,
                'user' =>$user->info(),
                'digital'=>$digital,
                'aso'=>null,
                'aceptado'=>$resultsAceptado
            ]
        ]);
    }

    public function infoLista(Request $req){
        $user = $req->user();
        $results = Informacion::where('user_id', $user->id)->get();
        return response()->json([
            'success'=>true,
            'results'=>$results
        ]);
    }

    public function marcarInfoLeida(Request $req, $id){
        $user = $req->user();
        Informacion::where('user_id', $user->id)
        ->where('id', $id)
        ->update(['leido' => true, 'active'=>0]);


        return response()->json([
            'success'=>true,
            'message'=>'Leido'
        ]);

    }
}
