<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\FarmaService;
use App\Services\InfinitaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConsultasController extends Controller
{
    public function clienteFarmaMiCredito(Request $req){
        $validator = Validator::make($req->only(['cedula']),['cedula'=>'required']);

        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

        $cliente = Cliente::where('cedula',$req->cedula)->first();

        $infinita = new InfinitaService();
        $farma = new FarmaService();

        $infinitaRes = (object)$infinita->ListarTarjetasPorDoc($req->cedula);
        $infinitaData = (object)$infinitaRes->data;
        $infinitaResult = null;
        if(property_exists( $infinitaData,'Tarjetas')){
            $infinitaResult = $infinitaData->Tarjetas[0];
        }

        $res = (object)$farma->cliente($req->cedula);
        $dataFarma = (object)$res->data;

        $farmaResult = null;

        if(property_exists($dataFarma,'result')){
            $result = $dataFarma->result;
            if(count($result)>0){
                $farmaResult = $result[0];
            }
        }
        return response()->json([
            'success'=>true,
            'message'=>'',
            'results'=>[
                'registro'=>$cliente ? true : false,
                'farma'=>$farmaResult,
                'micredito'=>$infinitaResult,
            ]
        ]);
    }

    public function clienteFarmaPorCodigo(Request $req){
        $validator = Validator::make($req->only(['codigo']),['codigo'=>'required']);

        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);


        $farma = new FarmaService();



        $res = (object)$farma->clientePorCodigo($req->codigo);
        $dataFarma = (object)$res->data;

        $farmaResult = null;

        if(property_exists($dataFarma,'result')){
            $result = $dataFarma->result;
            if(count($result)>0){
                $farmaResult = $result[0];
            }
        }
        return response()->json([
            'success'=>true,
            'message'=>'',
            'results'=>[
                'registro'=>true,
                'farma'=>$farmaResult,
                'micredito'=>null,
            ]
        ]);
    }
}
