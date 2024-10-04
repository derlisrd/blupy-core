<?php

namespace App\Http\Controllers\Farma;

use App\Http\Controllers\Controller;
use App\Models\Vendedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VendedoresController extends Controller
{
    public function generarQRVendedor(Request $r){
        $validator = Validator::make($r->all(), [
            'cedula' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=> $validator->errors()
            ], 400);
        }

        $vendedor = Vendedor::where('cedula',$r->cedula)->first();
        if($vendedor){
            return response()->json([
                'success'=>true,
                'id'=> $vendedor->id,
                'results'=>$vendedor
            ]);
        }

        return response()->json([
            'success'=>false,
            'id'=> null
        ],404);
    }
}
