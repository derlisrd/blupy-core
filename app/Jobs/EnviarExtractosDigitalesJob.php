<?php

namespace App\Jobs;

use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnviarExtractosDigitalesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $periodo;

    /**
     * @param string|null $periodo Ejemplo: "Agosto 2026" o "Mes Actual"
     */
    public function __construct($periodo = null)
    {
        $this->periodo = $periodo ?? 'Mes Actual';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Consultamos solo clientes con digital = 1 que tengan un usuario con email
        Cliente::where('digital', 1)
            ->whereHas('user', function ($query) {
                $query->whereNotNull('email')
                    ->where('email', '!=', '')
                    ->where('active', 1); // Opcional: solo usuarios activos
            })
            ->with('user')
            ->chunk(100, function ($clientes) {
                foreach ($clientes as $cliente) {
                    $user = $cliente->user;

                    // Si por alguna razón el usuario no tiene email, saltamos
                    if (!$user || empty($user->email)) {
                        continue;
                    }

                    // Parámetros que recibe la vista views/email/extractodisponible.blade.php
                    $params = [
                        'nombre'  => $user->name,
                        'periodo' => $this->periodo,
                    ];

                    $subject = 'Tu extracto de BLUPY ya está disponible';
                    $view    = 'email.extractodisponible';

                    // Despachamos el job individual para enviar el correo
                    EmailSenderJob::dispatch($user->email, $params, $subject, $view);
                }
            });
    }
}
