<?php

namespace App\Jobs;

use App\Services\EmailService;
use App\Services\TigoSmsService;
use App\Services\WaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;


use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispositivoInusualJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $celular;
    private $mensaje;
    private $email;
    private $codigo;
    private $datosEmail;
    private $numeroTelefonoWa;


    protected $queue = 'dispositivo-inusual';
    protected $tries = 2;
    protected $timeout = 60;
    protected $maxTries = 3;
    protected $delay = 60;

    public function __construct($celular, $mensaje, $email, $codigo, $datosEmail, $numeroTelefonoWa)
    {
        $this->celular = $celular;
        $this->mensaje = $mensaje;
        $this->email = $email;
        $this->codigo = $codigo;
        $this->datosEmail = $datosEmail;
        $this->numeroTelefonoWa = $numeroTelefonoWa;
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Enviar SMS
        (new TigoSmsService())->enviarSms($this->celular, $this->mensaje);
        (new WaService())->send($this->numeroTelefonoWa, $this->mensaje);
        // Enviar Email
        (new EmailService())->enviarEmail(
            $this->email,
            "[$this->codigo] Blupy confirmar dispositivo",
            'email.validarDispositivo',
            $this->datosEmail
        );
    }
}
