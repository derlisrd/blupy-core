<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Vendedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        $validator = Validator::make($req->all(),[
            'id'=>'required|exists:vendedores,id'
        ],[ 'vendedor_id.exists' => 'El vendedor seleccionado no existe en el sistema.' ]);

        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);


        $user = $req->user();
        if ($user->vendedor_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Vendedor ya vinculado',
            ], 400);
        }

        $user->vendedor_id = $req->id;
        $user->save();
        return response()->json([
            'success'=>true,
            'message'=>'Vendedor vinculado'
        ]);
    }
}
