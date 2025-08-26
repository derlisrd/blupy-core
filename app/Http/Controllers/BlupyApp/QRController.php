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

            $documento = $req->ruc ? $req->ruc : $cliente->cedula;

            $parametrosPorArray = [
                'id' => $req->id,
                'documento' => $documento,
                'numeroCuenta' => $req->numeroCuenta ? (int) $req->numeroCuenta : 0,
                'numeroTarjeta' => $req->numeroTarjeta ?? 1,
                'telefono' => $req->telefono,
                'ip' => $req->ip(),
                'localizacion' => $req->localizacion,
                'adicional' => $req->adicional,
                'extranjero' => $cliente->extranjero,
            ];
            //Verificar si tiene saldo
            if($req->numeroCuenta !== '0'){
                $resInfinita = app(InfinitaService::class)->ListarTarjetasPorDoc($cliente->cedula);
                $infinita = $resInfinita['data'];
                if($infinita && isset($infinita['Tarjetas'])){
                 $tarjeta = $infinita['Tarjetas'][0];
                 $disponible = (int) $tarjeta['MTLinea'] - (int) $tarjeta['MTSaldo'];
                 if($disponible < (int) $req->monto){
                     return response()->json([
                         'success' => false,
                         'message' => 'Saldo insuficiente',
                     ], 400);
                 }
                }
             }

             $blupy = app(BlupyQrService::class)->autorizarQR($parametrosPorArray);
             $data = $blupy['data'];
             $datasResults = null;
             if ($data && isset($data['results']) ) {
 
                 $datasResults = $data['results'];
                 if ($datasResults['web'] === 0 && $datasResults['farma'] === 1) {
                     $farmaService = new FarmaService();
 
                     try {
                         $this->executeWithRetry(function () use ($farmaService, $datasResults) {
                             return $farmaService->actualizarPedidosQR(
                                 (string)$datasResults['id'],
                                 $datasResults['numero_cuenta'],
                                 $datasResults['numero_tarjeta'],
                                 $datasResults['numero_movimiento']
                             );
                         }, 3); // 3 intentos máximos
                     } catch (\Exception $innerException) {
                         // Capturar y loguear el error final después de todos los reintentos
                         Log::error("Error final en FarmaService: " . $innerException->getMessage(), [
                             'id' => $datasResults['id'],
                             'exception' => $innerException
                         ]);
                         // Decide si quieres continuar o relanzar la excepción
                         //throw $innerException;
                         return response()->json([
                             'success' => false,
                             'message' => 'Error al procesar el pedido.',
                         ], 500);
                     }
                 }
             }
 
            


            return response()->json([
                'success' => $data['success'],
                'message' => $data['message']
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


    protected function executeWithRetry(callable $function, int $maxRetries = 3, int $baseDelayMs = 200)
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $maxRetries) {
            try {
                $attempts++;
                return $function();
            } catch (\Exception $e) {
                $lastException = $e;

                // No reintentamos en el último intento
                if ($attempts >= $maxRetries) {
                    break;
                }

                // Errores que no deberían reintentarse
                if (
                    $e instanceof \InvalidArgumentException ||
                    $e instanceof \BadMethodCallException
                ) {
                    break;
                }

                // Backoff exponencial con jitter para evitar tormentas de reintentos
                $maxDelay = $baseDelayMs * pow(2, $attempts - 1);
                $jitter = mt_rand(0, $maxDelay / 2);
                $delay = $maxDelay + $jitter;

                Log::warning("Intento {$attempts}/{$maxRetries} fallido: " . $e->getMessage(), [
                    'delay_ms' => $delay,
                    'exception' => $e
                ]);

                // Usar una espera más eficiente sin bloquear el thread en entornos async
                if (function_exists('usleep')) {
                    usleep($delay * 1000); // usleep usa microsegundos
                } else {
                    sleep(ceil($delay / 1000));
                }
            }
        }

        // Si llegamos aquí, todos los intentos fallaron
        throw $lastException ?? new \RuntimeException('Todos los intentos fallaron sin excepción específica');
    }
}
