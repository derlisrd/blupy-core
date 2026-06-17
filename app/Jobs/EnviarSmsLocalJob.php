<?php

namespace App\Jobs;

use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnviarSmsLocalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // segundos entre reintentos

    public function __construct(
        protected Cliente $cliente,
        protected string $mensaje
    ) {}

    public function handle(): void
    {
        $celularFormateado = $this->formatearCelular($this->cliente->celular);

        if (! $celularFormateado) {
            Log::warning("EnviarSmsJob: celular inválido para cliente ID {$this->cliente->id} — valor: {$this->cliente->celular}");
            return;
        }

        $mensajeFinal = str_replace(
            '{{NOMBRE DE CLIENTE }}',
            $this->cliente->nombre_primero,
            $this->mensaje
        );

        $response = Http::timeout(10)
            ->post('http://10.40.100.153:8080/send-sms', [
                'phone'   => $celularFormateado,
                'message' => $mensajeFinal,
            ]);

        $body = $response->json();

        if (! ($body['success'] ?? false)) {
            Log::error("EnviarSmsJob: fallo al enviar a {$celularFormateado} (cliente ID {$this->cliente->id})", $body);
            $this->fail("API respondió con error para cliente ID {$this->cliente->id}");
            return;
        }

        Log::info("EnviarSmsJob: SMS enviado a {$celularFormateado} (cliente ID {$this->cliente->id})");
    }

    /**
     * Reemplaza el 0 inicial por 595.
     * Retorna null si el número no tiene el formato esperado.
     */
    private function formatearCelular(string $celular): ?string
    {
        $celular = trim($celular);

        // Acepta 0XXXXXXXXX (10 dígitos)
        if (preg_match('/^0(\d{9})$/', $celular, $m)) {
            return '595' . $m[1];
        }

        // Si ya viene con 595, lo dejamos pasar
        if (preg_match('/^595\d{9}$/', $celular)) {
            return $celular;
        }

        return null;
    }
}
