<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Traits\ContratosBlupyFarmaTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContratosController extends Controller
{
    use ContratosBlupyFarmaTraits;

    public function consultaContratoPorDocFarma(Request $req){
        $validator = Validator::make($req->all(),['documento'=>'required']);
        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);
        $documento = $req->documento;

        $res = $this->consultarContratoBlupyPorDocumentoEnFarma($documento);
        if(!$res->success)
            return response()->json(['success'=>false,'message'=>'No se encontraron contratos para el documento ingresado'], 404);
        $cliente = Cliente::where('cedula',$documento)->select('foto_ci_frente','cedula','id','selfie','nombre_primero','apellido_primero','celular')->first();
        $results = [
            'contratos'=>$res->results,
            'cliente'=>$cliente
        ];
        return response()->json(['success'=>true,'results'=>$results],$res->status);
    }

    public function consultarPorContratoFarma(Request $req){
        $validator = Validator::make($req->all(),['contrato'=>'required']);
        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);
        $contrato = $req->contrato;



    }


}
