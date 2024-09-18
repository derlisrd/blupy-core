<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Services\BlupyQrService;
use App\Services\SupabaseService;
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
        $parametrosPorArray = [
            'id'=>$req->id,
            'documento'=>$cliente->cedula,
            'numero_cuenta'=>$req->numerocuenta,
            'telefono'=>$req->telefono,
            'ip'=>$req->ip(),
            'localizacion'=>$req->localizacion,
            'adicional'=>$req->adicional
        ];
        $blupy = $this->webserviceBlupyQRCore
            ->autorizarQR($parametrosPorArray);
        $data = (object) $blupy['data'];
        SupabaseService::LOG('autorizadoQR', $blupy['data'] );
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
                'message'=>'',
                'results'=>$data->results,
            ],$blupy['status']);
        }

        return response()->json([
            'success'=>$data->success,
            'message'=>$data->message
        ],$blupy['status']);
    }
}
