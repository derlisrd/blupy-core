<?php

namespace App\Http\Controllers\Farma;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ContactoBlupyController extends Controller
{
    public function getNroTelefono(Request $req){
        try {
            $cedula = $req->cedula;

            $cliente =  Cliente::where('cedula','=',$cedula,false)->first();

            if($cliente){
                return response()->json([
                    'success'=>true,
                    'results' =>$cliente->celular
                ]);
            }
            return response()->json([
                'success' => true,
                'results' => null
            ],400);

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
