<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConsultasController extends Controller
{
    public function verificarDocumento(Request $request){

        return response()->json([
            'success'=>true,
            'message'=>'true'
        ]);
    }
}
