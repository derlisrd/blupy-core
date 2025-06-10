<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Models\Adjunto;
use App\Models\Cliente;
use App\Traits\ContratosBlupyFarmaTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContratosController extends Controller
{
    use ContratosBlupyFarmaTraits;

    public function recibirContrato(Request $req)
    {
        $validator = Validator::make($req->all(),['codigo'=>'required']);
        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);
        $codigoDeContrato = $req->codigo;

        $res = $this->recibirContratoPorCodigo($codigoDeContrato);
        if(!$res->success)
            return response()->json(['success' => false, 'message' => 'No se encontraron contratos para el codigo ingresado'], 404);

        return response()->json(['success'=>true,'results'=>$res->results],$res->status);
    }

    public function contratoPorDocumento(Request $req){
        $validator = Validator::make($req->all(),['documento'=>'required']);
        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);
        $documento = $req->documento;

        $res = $this->consultarContratoBlupyPorDocumentoEnFarma($documento);
        if(!$res->success)
            return response()->json(['success'=>false,'message'=>'No se encontraron contratos para el documento ingresado'], 404);

        $cliente = Cliente::where('clientes.cedula',$documento)
        ->where('s.tipo',1)
        ->whereIn('s.estado_id',[3,5,7])
        ->join('solicitud_creditos as s','clientes.id','=','s.cliente_id')
        ->select('clientes.foto_ci_frente','clientes.cedula','clientes.id','clientes.selfie','clientes.nombre_primero',
        'clientes.apellido_primero','clientes.celular','s.tipo','s.estado','s.codigo','s.estado_id','s.created_at')
        ->first();
        $adjuntos = Adjunto::where('cliente_id',$cliente->id)->get();
        $results = [
            'contratos'=>$res->results,
            'cliente'=>$cliente,
            'adjuntos'=>$adjuntos
        ];
        return response()->json(['success'=>true,'results'=>$results],$res->status);
    }

    public function contratoPorCodigo(Request $req){
        $validator = Validator::make($req->all(),['codigo'=>'required']);
        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

        $codigo = $req->codigo;
        $res = $this->consultarContratoPorCodigo($codigo);
        if(!$res->success)
            return response()->json(['success'=>false,'message'=>'No se encontraron contratos para el documento ingresado'], 404);

        $cliente = Cliente::where('s.codigo',$codigo)
        ->where('s.tipo',1)
        ->whereIn('s.estado_id',[3,5,7])
        ->join('solicitud_creditos as s','clientes.id','=','s.cliente_id')
        ->select('clientes.foto_ci_frente','clientes.cedula','clientes.id','clientes.selfie','clientes.nombre_primero',
        'clientes.apellido_primero','clientes.celular','s.tipo','s.estado','s.codigo','s.estado_id','s.created_at')->first();
        $adjuntos = Adjunto::where('cliente_id',$cliente->id)->get();
        $results = [
            'contratos'=>$res->results,
            'cliente'=>$cliente,
            'adjuntos'=>$adjuntos
        ];
        return response()->json(['success'=>true,'results'=>$results],$res->status);


    }


}
