<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Services\InfinitaService;
use App\Services\SupabaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdatePerfilDigital implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        // Procesar en chunks para evitar problemas de memoria
        Cliente::chunk(100, function ($clientes) {
            foreach ($clientes as $cliente) {
                // Despachamos un job individual por cada cliente
                UpdatePerfilDigitalInidividual::dispatch($cliente);
            }
        });

        SupabaseService::LOG('Job de distribución de clientes digitales completado','completado');
    }
}
