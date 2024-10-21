<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Vendedor;
use Illuminate\Http\Request;

class VendedorController extends Controller
{
    public function consultar(Request $req){
        $vendedor = Vendedor::find($req->id);
        if(!$vendedor){
            return response()->json([
                'success'=>false,
                'message'=>"No existe vendedor"
            ],404);
        }
        return response()->json([
            'success'=>true,
            'message'=>"Vendedor listo"
        ]);
    }

    public function vincular(Request $req){
        $user = $req->user();

        if($user->vendedor_id !== null){
            return response()->json([
                'success'=>true,
                'message'=>'Vendedor vinculado'
            ]);
        }
        return response()->json([
            'success'=>false,
            'message'=>'Vendedor ya vinculado',
            'vendedor'=>$user
        ],400);
    }
}
