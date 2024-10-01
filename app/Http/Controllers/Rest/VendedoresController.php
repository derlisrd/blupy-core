<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Models\Vendedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VendedoresController extends Controller
{
    public function ingresarVendedor(Request $req){
        $validator = Validator::make($req->all(), [
            'cedula' => ['required','numeric'],
            'nombre'=> 'required',
            'punto'=> ['required','numeric'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=> $validator->errors()->first()
            ], 400);
        }
        $vendedor = Vendedor::where('cedula',$req->cedula)->first();
        if($vendedor){
            return response()->json([
                'success'=>false,
                'message'=>'Vendedor existente'
            ],400);
        }


        Vendedor::create([
            'cedula'=>$req->cedula,
            'nombre'=>$req->nombre,
            'punto'=>$req->punto,
        ]);

        return response()->json([
            'success'=>true,
            'message'=>'Ingresado'
        ]);

    }
}
