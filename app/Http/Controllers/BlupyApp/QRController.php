<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Services\BlupyQrService;
use App\Services\PushExpoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QRController extends Controller
{
    private $webserviceBlupyQRCore;

    public function __construct()
    {
        $this->webserviceBlupyQRCore = new BlupyQrService();
    }

    public function autorizar(Request $req)
    {
        try {
            $user = $req->user();
            $cliente = $user->cliente;
            $parametrosPorArray = [
                'id' => $req->id,
                'documento' => $cliente->cedula,
                'numero_cuenta' => $req->numerocuenta,
                'telefono' => $req->telefono,
                'ip' => $req->ip(),
                'localizacion' => $req->localizacion,
                'adicional' => $req->adicional
            ];
            $blupy = $this->webserviceBlupyQRCore
                ->autorizarQR($parametrosPorArray);
            $data = (object) $blupy['data'];

            if (property_exists($data, 'results')) {

                $noti = new PushExpoService();
                $tokens = $user->notitokens();
                $noti->send($tokens, 'Compra en comercio', 'Se ha registrado una compra en comercio');

                Notificacion::create([
                    'user_id' => $user->id,
                    'title' => 'Compra en comercio',
                    'body' => $data->results['info']
                ]);
            }
            return response()->json([
                'success' => $data->success,
                'message' => $data->message
            ], $blupy['status']);
        } catch (\Throwable $th) {
            Log::error($th);
            throw $th;
            return response()->json([
                'success' => false,
                'message' => 'Error de servidor'
            ], 500);
        }
    }

    public function consultar($id)
    {

        $blupy = $this->webserviceBlupyQRCore->consultarQR($id);
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
    }
}
