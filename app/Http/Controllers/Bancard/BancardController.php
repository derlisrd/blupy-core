<?php

namespace App\Http\Controllers\Bancard;

use App\Http\Controllers\Controller;
use App\Traits\BancardTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BancardController extends Controller
{
    use BancardTraits;

    public function consultarDeuda(Request $req){
        try {
            $results = $this->TraerDeudaPorDocumento($req->cedula,$req->cuenta);

            if(count($results) < 1){
                return response()->json([
                    'success'=>false,
                    'results'=>null,
                    'message'=>'Documento o cuenta no encontrada.'
                ],404);
            }

            return response()->json([
                'success'=>true,
                'results'=>$results
            ]);

        } catch (\Throwable $th) {
            throw $th;
            Log::error($th);
        }
    }




    public function pagarDeuda(Request $req){
        try {
            $results = $this->pagarDeudaPorDocumento($req->cedula,$req->cuenta,$req->importe);


            if($results->success){
                return response()->json([
                    'success'=>true,
                    'message'=>'Pago efectuado correctamente.',
                    'results' => $results->recibo
                ]);
            }

            return response()->json([
                'success'=>false,
                'message'=> $results->message
            ],400);

        } catch (\Throwable $th) {
            throw $th;
            Log::error($th);
        }
    }
    public function revertirPago(Request $req){
        try {

            return response()->json([
                'success'=>false,
                'message'=> 'Revercion de pago'
            ]);

        } catch (\Throwable $th) {
            throw $th;
            Log::error($th);
        }
    }

}
