<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Services\BlupyQrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QRController extends Controller
{
    private $webserviceBlupyQRCore;

    public function __construct() {
        $this->webserviceBlupyQRCore = new BlupyQrService();
    }

    public function autorizar(Request $req){
        $user = $req->user();
        $cliente = $user->cliente;
        $blupy = $this->webserviceBlupyQRCore
            ->autorizarQR($req->id,$cliente->cedula, $req->numerocuenta,$req->telefono,$req->ip,$req->localizacion);
        $data = (object) $blupy['data'];

        return response()->
        json([
            'success'=>$data->success,
            'message'=>$data->message
        ],
        $blupy['status']);
    }

    public function consultar($id){
        $blupy = $this->webserviceBlupyQRCore->consultarQR($id);
        $data = (object) $blupy['data'];

        if($data->success){
            return response()->json([
                'success'=>$data->success,
                'results'=>$data->results
            ],$blupy['status']);
        }

        return response()->json([
            'success'=>$data->success,
            'message'=>$data->message
        ],$blupy['status']);
    }
}
