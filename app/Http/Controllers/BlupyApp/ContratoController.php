<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ContratoController extends Controller
{


    public function aceptarContrato(Request $req){
        $user = $req->user();
        $cliente = Cliente::where('id',$user->cliente_id)->first();

        if(!$cliente){
            return response()->json(['success'=>false, 'message'=>'No se encontro el cliente']);
        }

        $cliente->aceptado = 1;
        $cliente->save();
        
        return response()->json([
            'success'=>true,
            'message'=>'Contrato aceptado']);
        
    }

    public function terminos(Request $req){
        $text = '';
        return response()->json([
            'success'=>true,
            'message'=>'Contrato',
            'results' =>[
                'text'=>$text
            ]
        ]);
    }

    public function contrato(){
        return view('pdf.contrato');
    }
}
