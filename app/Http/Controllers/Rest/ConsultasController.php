<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Services\FarmaService;
use App\Services\InfinitaService;
use Illuminate\Http\Request;

class ConsultasController extends Controller
{
    public function clienteFarmaMiCredito(Request $req){

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
                'farma'=>$farmaResult,
                'micredito'=>$infinitaResult
            ]
        ]);
    }
}
