<?php

namespace App\Jobs;

use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
       try {
        Cliente::chunk(100, function ($clientes) {
            foreach ($clientes as $cliente) {
                // Despachamos un job individual por cada cliente
                UpdatePerfilDigitalIndividual::dispatch($cliente);
            }
        });
       } catch (\Throwable $th) {
        Log::error('Error en el proceso de actualización de perfiles digitales: ' . $th->getMessage());
        throw $th;
       }


    }
}
