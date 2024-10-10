<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ContratoController extends Controller
{
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
}
