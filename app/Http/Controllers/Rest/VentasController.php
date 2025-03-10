<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Services\FarmaService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VentasController extends Controller
{
    public function index(Request $req){

    }

    public function porCodigo(Request $req){
        try {
            $validator  = Validator::make($req->all(), ['codigo' => 'required']);
            if ($validator->fails())
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

            $farma = new FarmaService();
            $res = $farma->ventaPorCodigo($req->codigo);

            $venta = (object) $res['data'];

            if(property_exists($venta,'result')){
                return response()->json(['success' => true, 'message' => $venta->result],$venta->status);
            }

            return response()->json(['success' => false, 'message' => 'Hubo un error de servidor.'],500);
        } catch (\Throwable $th) {
            throw $th;
        }

    }
    public function porFactura(Request $req){
        try {
            $validator  = Validator::make($req->all(), ['factura' => 'required']);

            if ($validator->fails())
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

            $farma = new FarmaService();
            $res = $farma->ventaPorFactura($req->factura);

            $venta = (object) $res['data'];

            if(property_exists($venta,'result')){
                return response()->json(['success' => true, 'message' => $venta->result],$venta->status);
            }
            return response()->json(['success' => false, 'message' => 'Hubo un error de servidor.'],500);

        } catch (\Throwable $th) {
            throw $th;
        }


    }
}
