<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TigoSmsService
{
    private $url;
    private $key;
    private $timeout = 10;
    private $retries = 2;

    public function __construct()
    {
        $this->url = env('TIGO_API_URL');
        $this->key = env('TIGO_API_KEY');
    }

    public function enviarSms(string $numero, string $texto)
    {
        $textoEncoded = urlencode($texto);
        
        try {
            return Http::timeout($this->timeout)
                ->retry($this->retries, 100)
                ->get($this->url, [
                    'key' => $this->key,
                    'message' => $textoEncoded,
                    'msisdn' => $numero
                ]);
        } catch (\Throwable $th) {
            Log::error('Error en enviarSms', [
                'numero' => $numero,
                'error' => $th->getMessage()
            ]);
            throw $th;
        }
    }

    /**
     * Envía SMS masivos usando requests concurrentes
     * @param array $numeros Array de números de teléfono
     * @param string $texto Mensaje a enviar
     * @param int $concurrency Número máximo de requests concurrentes (default: 10)
     * @return array Array de responses
     */
    public function enviarSmsMasivo(array $numeros, string $texto, int $concurrency = 10)
    {
        
        
        // Dividir en chunks según la concurrencia permitida
        $chunks = array_chunk($numeros, $concurrency);
        $todasLasRespuestas = [];

        foreach ($chunks as $chunkNumeros) {
            try {
                $respuestas = Http::pool(function ($pool) use ($chunkNumeros, $texto) {
                    $requests = [];
                    
                    foreach ($chunkNumeros as $numero) {
                        $requests[] = $pool->timeout($this->timeout)
                            ->retry($this->retries, 100)
                            ->get($this->url, [
                                'key' => $this->key,
                                'message' => $texto,
                                'msisdn' => $numero
                            ]);
                    }
                    
                    return $requests;
                });

                $todasLasRespuestas = array_merge($todasLasRespuestas, $respuestas);
                
                // Pequeña pausa entre chunks para no saturar
                usleep(100000); // 0.1 segundos
                
            } catch (\Throwable $th) {
                Log::error('Error en chunk de enviarSmsMasivo', [
                    'numeros_count' => count($chunkNumeros),
                    'error' => $th->getMessage()
                ]);
            }
        }

        return $todasLasRespuestas;
    }
}