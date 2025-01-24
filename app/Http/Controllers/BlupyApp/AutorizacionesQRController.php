<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Services\BlupyQrService;
use Illuminate\Http\Request;

class AutorizacionesQRController extends Controller
{
    public function solicitarAutorizacion(Request $req){
        try{
            $user = $req->user();
            $cliente = $user->cliente;

            $blupyQrService = new BlupyQrService();

            $blupy = $blupyQrService->consultarPorDocumento($cliente->cedula);
            $data = (object) $blupy['data'];

            if ($data->success) {
                return response()->json([
                    'success' => $data->success,
                    'message' => '',
                    'results' => $data->results,
                ], $blupy['status']);
            }

            return response()->json([
                'success' => $data->success,
                'message' => $data->message
            ], $blupy['status']);
            ///return response()->json(['cedula'=>$cliente->cedula]);

        }catch(\Exception $e){
            throw $e;
            return response()->json([
                'success' => false,
                'message' => 'Error al solicitar autorización',
                'error' => $e->getMessage()
            ],500);
        }
    }
}
