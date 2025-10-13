<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Services\InfinitaService;
use App\Services\SupabaseService;
use App\Services\TigoSmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendSmsToMorososJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 3;
    public $backoff = 60;

    private $mensajeTexto;

    /**
     * Constructor para procesar un cliente específico
     */
    public function __construct($mensajeTexto)
    {
        $this->mensajeTexto = $mensajeTexto;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $infinitaService = new InfinitaService();
        $totalEnviados = 0;
        $totalFallidos = 0;

        Cliente::where('digital', 1)
            ->select('id', 'cedula', 'celular')
            ->chunkById(100, function ($clientes) use ($infinitaService, &$totalEnviados, &$totalFallidos) {
                $enviarMensajesA = [];
                
                foreach ($clientes as $cliente) {
                    try {
                        $existeDeuda = $this->verificarDeudaTarjetaDigital($cliente->cedula, $infinitaService);
                        
                        if ($existeDeuda && $cliente->celular) {
                            $enviarMensajesA[] = $cliente->celular;
                        }
                        
                    } catch (\Exception $e) {
                        Log::error("Error procesando cliente {$cliente->id}: " . $e->getMessage());
                    }
                }
                
                if (!empty($enviarMensajesA)) {
                    [$enviados, $fallidos] = $this->sendSms($enviarMensajesA);
                    $totalEnviados += $enviados;
                    $totalFallidos += $fallidos;
                }
            });

        SupabaseService::LOG(
            'JOB DE SMS MASIVOS COMPLETADO', 
            "Total SMS enviados: $totalEnviados, fallidos: $totalFallidos", 
            'info'
        );
    }

    private function verificarDeudaTarjetaDigital(string $cedula, InfinitaService $infinitaService): bool
    {
        $resInfinita = $infinitaService->ListarTarjetasPorDoc($cedula);
        $infinita = (object)($resInfinita['data'] ?? []);
        
        if (property_exists($infinita, 'Tarjetas') && !empty($infinita->Tarjetas)) {
            $tarjeta = $infinita->Tarjetas[0];
            if (!$tarjeta) {
                return false;
            }
            $pagoMinimo = (int) $tarjeta['MCPagMin'];
            $saldo = (int) $tarjeta['MTSaldo'];
            
            return $saldo > $pagoMinimo;
        }
        
        return false;
    }

    private function sendSms(array $numeros): array
    {
        $tigoSmsService = new TigoSmsService();
        $enviados = 0;
        $fallidos = 0;

        try {
            // Rate limiting antes de enviar
            $this->rateLimit(count($numeros));
            
            // Usar el método masivo con concurrencia
            $respuestas = $tigoSmsService->enviarSmsMasivo($numeros, $this->mensajeTexto, 10);
            
            // Procesar respuestas
            foreach ($respuestas as $index => $respuesta) {
                if ($respuesta->successful()) {
                    $enviados++;
                } else {
                    $fallidos++;
                    Log::warning("SMS fallido al número index {$index}", [
                        'status' => $respuesta->status(),
                        'body' => $respuesta->body()
                    ]);
                }
            }
            
        } catch (\Throwable $th) {
            $fallidos = count($numeros);
            SupabaseService::LOG(
                'Error enviando lote de SMS', 
                'Total números: ' . count($numeros) . ', Error: ' . $th->getMessage() 
            );
        }

        SupabaseService::LOG('Lote de SMS procesado', 
            'total: ' . count($numeros) .
            ' enviados: ' . $enviados .
            ' fallidos: ' . $fallidos
        );

        return [$enviados, $fallidos];
    }

    private function rateLimit(int $cantidad = 1)
    {
        $key = 'sms_rate_limit';
        $maxPerMinute = 30;
        
        $attempts = Cache::get($key, 0);
        
        // Si excedemos el límite con esta cantidad, esperamos
        if (($attempts + $cantidad) > $maxPerMinute) {
            $waitSeconds = 60;
            Log::info("Rate limit alcanzado, esperando {$waitSeconds} segundos...");
            sleep($waitSeconds);
            Cache::put($key, $cantidad, 60);
        } else {
            Cache::increment($key, $cantidad);
            
            if ($attempts === 0) {
                Cache::put($key, $cantidad, 60);
            }
        }
    }
}