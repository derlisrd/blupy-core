<?php

namespace App\Jobs;

use App\Services\EmailService;
use App\Services\TigoSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispositivoInusualJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Tiempo máximo en segundos que puede durar este Job antes de ser interrumpido.
     * Debe ser mayor a la suma de los timeouts de los servicios externos.
     */
    public int $timeout = 120;

    /**
     * Número de veces que se reintentará el Job si falla.
     */
    public int $tries = 2;

    private $celular;
    private $mensaje;
    private $email;
    private $codigo;
    private $datosEmail;
    private $numeroTelefonoWa;

    public function __construct($celular, $mensaje, $email, $codigo, $datosEmail, $numeroTelefonoWa)
    {
        $this->celular = $celular;
        $this->mensaje = $mensaje;
        $this->email = $email;
        $this->codigo = $codigo;
        $this->datosEmail = $datosEmail;
        $this->numeroTelefonoWa = $numeroTelefonoWa;
    }

    public function handle(): void
    {
        // 1. Enviar SMS con try/catch para evitar que un fallo de SMS bloquee el envío del Mail
        try {
            (new TigoSmsService())->enviarSms($this->celular, $this->mensaje);
        } catch (\Throwable $e) {
            // Loguear pero continuar con el flujo
            \Illuminate\Support\Facades\Log::warning("No se pudo enviar SMS en DispositivoInusualJob: " . $e->getMessage());
        }

        // 2. Enviar Email
        (new EmailService())->enviarEmail(
            $this->email,
            "[$this->codigo] Blupy confirmar dispositivo",
            'email.validarDispositivo',
            $this->datosEmail
        );
    }
}
