<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Services\BlupyQrService;
use App\Services\FarmaService;
use App\Services\PushExpoService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
                'numeroCuenta' => $req->numeroCuenta,
                'numeroTarjeta' => $req->numeroTarjeta,
                'telefono' => $req->telefono,
                'ip' => $req->ip(),
                'localizacion' => $req->localizacion,
                'adicional' => $req->adicional,
                'extranjero' => $cliente->extranjero,
            ];
            $blupy = $this->webserviceBlupyQRCore
                ->autorizarQR($parametrosPorArray);
            $data = (object) $blupy['data'];

            if (property_exists($data, 'results')) {
                $noti = new PushExpoService();
                $tokens = $user->notitokens();
                $noti->send($tokens, 'Compra en comercio', 'Se ha registrado una compra en comercio', []);
                SupabaseService::LOG('Compra commercio 46', 'Compra QR ' . $cliente->cedula);
                Notificacion::create([
                    'user_id' => $user->id,
                    'title' => 'Compra en comercio',
                    'body' => $data->results['info']
                ]);

                $datasResults = $data->results;

                if ($datasResults['web'] === 0 && $datasResults['farma'] === 1) {
                    $farmaService = new FarmaService();
                    $farmaService->actualizarPedidosQR(
                        (string)$datasResults['id'],
                        $datasResults['numero_cuenta'],
                        $datasResults['numero_tarjeta'],
                        $datasResults['numero_movimiento']
                    );
                }
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
                'message' => 'Error de servidor. CQ500'
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


    public function autorizarSinQR(Request $req)
    {
        try {
            $user = $req->user();
            $cliente = $user->cliente;
            SupabaseService::LOG('Compra commercio 46', 'Compra QR ' . $cliente->cedula);
            if (!Hash::check($req->password, $user->password))
                return response()->json(['success' => false, 'message' => 'Contraseña incorrecta.'], 401);

                $parametrosPorArray = [
                    'id' => $req->id,
                    'documento' => $cliente->cedula,
                    'numeroCuenta' => $req->numeroCuenta,
                    'numeroTarjeta' => $req->numeroTarjeta,
                    'telefono' => $req->telefono,
                    'ip' => $req->ip(),
                    'localizacion' => $req->localizacion,
                    'adicional' => $req->adicional,
                    'extranjero' => $cliente->extranjero,
                ];

            $blupy = $this->webserviceBlupyQRCore->autorizarQR($parametrosPorArray);
            $data = (object) $blupy['data'];

            if (property_exists($data, 'results')) {
                $noti = new PushExpoService();
                $tokens = $user->notitokens();
                $noti->send($tokens, 'Compra en comercio', 'Se ha registrado una compra en comercio', []);

                Notificacion::create([
                    'user_id' => $user->id,
                    'title' => 'Compra en comercio',
                    'body' => $data->results['info']
                ]);

                $datasResults = $data->results;

                if ($datasResults['web'] === 0 && $datasResults['farma'] === 1) {
                    $farmaService = new FarmaService();
                    $farmaService->actualizarPedidosQR(
                        (string)$datasResults['id'],
                        $datasResults['numero_cuenta'],
                        $datasResults['numero_tarjeta'],
                        $datasResults['numero_movimiento']
                    );
                }
            }


            return response()->json([
                'success' => $data->success,
                'message' => $data->message
            ], $blupy['status']);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
