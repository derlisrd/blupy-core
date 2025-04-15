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
use Illuminate\Support\Facades\Validator;

class AutorizacionesQRController extends Controller
{
    public function solicitarAutorizacion(Request $req)
    {
        try {
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

        } catch (\Exception $e) {
            throw $e;
            return response()->json([
                'success' => false,
                'message' => 'Error al solicitar autorización',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function autorizar(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required',
            'numeroCuenta' => 'required',
            'numeroTarjeta' => 'nullable',
            'password' => 'required',
        ], [
            'id.required' => 'ID requerido.',

            'password.required' => 'Contraseña requerida.'
        ]);

        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);


        try {
            $user = $req->user();
            $cliente = $user->cliente;

            $cedula = $cliente->cedula;

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
            $blupyQrService = new BlupyQrService();
            $blupy = $blupyQrService->autorizarQR($parametrosPorArray);
            $data = (object) $blupy['data'];
            $datasResults = null;
            if (property_exists($data, 'results')) {

                $datasResults = $data->results;
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
                'success' => $data->success,
                'message' => $data->message,
                'results' => $datasResults,
            ], $blupy['status']);

            return response()->json();
        } catch (\Exception $e) {
            throw $e;
            return response()->json([
                'success' => false,
                'message' => 'Error al solicitar autorización',
                'error' => $e->getMessage()
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
