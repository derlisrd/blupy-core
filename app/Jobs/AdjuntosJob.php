<?php

namespace App\Jobs;

use App\Models\Adjunto;
use App\Models\Cliente;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AdjuntosJob implements ShouldQueue
{
    use Queueable;

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
        Cliente::chunk(100, function ($clientes) { // Usamos chunk para procesar en lotes y evitar problemas de memoria con muchos clientes
            foreach ($clientes as $cliente) {
                // Procesar 'selfie'
                if ($cliente->selfie) {
                    Adjunto::create([
                        'cliente_id' => $cliente->id,
                        'nombre' => $cliente->selfie,
                        'tipo' => 'selfie',
                        'path' => 'clientes',
                        'url' => 'clientes/' . $cliente->selfie,
                    ]);
                }

                // Procesar 'foto_ci_frente'
                if ($cliente->foto_ci_frente) {
                    Adjunto::create([
                        'cliente_id' => $cliente->id,
                        'nombre' => $cliente->foto_ci_frente,
                        'tipo' => 'cedula1', // Según tu descripción
                        'path' => 'clientes',
                        'url' => 'clientes/' . $cliente->foto_ci_frente,
                    ]);
                }

                // Procesar 'foto_ci_dorso'
                if ($cliente->foto_ci_dorso) {
                    Adjunto::create([
                        'cliente_id' => $cliente->id,
                        'nombre' => $cliente->foto_ci_dorso,
                        'tipo' => 'cedula2', // Según tu descripción
                        'path' => 'clientes',
                        'url' => 'clientes/' . $cliente->foto_ci_dorso,
                    ]);
                }
            }
        });
    }
}
