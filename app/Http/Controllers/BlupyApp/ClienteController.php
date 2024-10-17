<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function misDescuentos(Request $req){
        $user = $req->user();

        $cliente = Cliente::find($user->cliente->id);
        $descuentoTotal = $cliente->ventas()
        ->sum('descuento');

        return response()->json([
            'success'=>true,
            'results'=>[
                'descuentosTotales'=>$descuentoTotal
            ]
        ]);


    }
}
