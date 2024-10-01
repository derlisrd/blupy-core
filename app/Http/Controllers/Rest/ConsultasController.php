<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Services\FarmaService;
use Illuminate\Http\Request;

class ConsultasController extends Controller
{
    public function farma(Request $req){

        $farma = new FarmaService();
        $res = (object)$farma->cliente($req->cedula);
        $data = (object)$res->data;

        if(property_exists($data,'result')){
            $result = $data->result;
            if(count($result)>0){
                return response()->json([
                    'success'=>true,
                    'message'=>'Posee regitros',
                    'results'=>$result
                ]);
            }
        }
        return response()->json([
            'success'=>false,
            'message'=>'No posee regitros',
            'results'=>null
        ],404);
    }
}
