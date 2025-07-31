<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Services\BlupyQrService;
use App\Services\FarmaService;
use App\Services\InfinitaService;
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
                'numeroCuenta' => $req->numeroCuenta ? (int) $req->numeroCuenta : 0,
                'numeroTarjeta' => $req->numeroTarjeta ?? 1,
                'telefono' => $req->telefono,
                'ip' => $req->ip(),
                'localizacion' => $req->localizacion,
                'adicional' => $req->adicional,
                'extranjero' => $cliente->extranjero,
            ];
            //Log::info('numeroCuenta: '.$req->numeroCuenta .' tipo ' . gettype($req->numeroCuenta));
            if($req->numeroCuenta !== '0'){
                $resInfinita = app(InfinitaService::class)->ListarTarjetasPorDoc($cliente->cedula);
                $infinita = (object)$resInfinita['data'];
                if(property_exists( $infinita,'Tarjetas')){
                 $tarjeta = $infinita->Tarjetas[0];
                 $disponible = (int) $tarjeta['MTLinea'] - (int) $tarjeta['MTSaldo'];
                 if($disponible < (int) $req->monto){
                     return response()->json([
                         'success' => false,
                         'message' => 'Saldo insuficiente',
                     ], 400);
                 }
                }
             }

            $blupy = $this->webserviceBlupyQRCore
                ->autorizarQR($parametrosPorArray);
            $data = (object) $blupy['data'];

            $datasResults = $data->results;

            if ($datasResults['web'] === 0 && $datasResults['farma'] === 1) {
                try {
                    //confirmar pago en farma
                    $farmaService = new FarmaService();
                    $farmaService->actualizarPedidosQR(
                        (string) ($datasResults['id'] ?? ''),
                        $datasResults['numero_cuenta'] ?? '',
                        $datasResults['numero_tarjeta'] ?? '',
                        $datasResults['numero_movimiento'] ?? ''
                    ); 
                } catch (\Throwable $th) {
                    Log::error($th->getMessage());
                    return response()->json([
                        'success' => false,
                        'message' => 'Error. Intente otra vez en unos momentos. QRF500'
                    ],500);
                }  
                    
            }

            


            return response()->json([
                'success' => $data->success,
                'message' => $data->message
            ], $blupy['status']);
        } catch (\Throwable $th) {
            Log::error('Error en autorizar QR: ' . $th->getMessage(), [
                'user_id' => $req->user()->id ?? null,
                'parametros'=>$parametrosPorArray
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión. Por favor intente en unos momentos. CQ500'
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

            if (isset($data->results)){

                $datasResults = $data->results;

                if ($datasResults['web'] === 0 && $datasResults['farma'] === 1) {
                    app(FarmaService::class)->actualizarPedidosQR(
                        (string) ($datasResults['id'] ?? ''),
                        $datasResults['numero_cuenta'] ?? '',
                        $datasResults['numero_tarjeta'] ?? '',
                        $datasResults['numero_movimiento'] ?? ''
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
