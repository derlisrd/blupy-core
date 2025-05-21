<?php

namespace App\Jobs;

use App\Models\Adjunto;
use App\Models\Cliente;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AdjuntosJob implements ShouldQueue
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

        // --- Procesar clientes que no tienen 'selfie' en adjuntos pero sí en clientes ---
        Cliente::whereNotNull('clientes.selfie') // Aseguramos que el cliente tenga una selfie
            ->leftJoin('adjuntos', function ($join) {
                $join->on('clientes.id', '=', 'adjuntos.cliente_id')
                     ->where('adjuntos.tipo', '=', 'selfie');
            })
            ->whereNull('adjuntos.id') // Donde no hay un registro de adjunto de tipo 'selfie'
            ->select('clientes.*') // Seleccionamos solo las columnas de clientes para evitar conflictos de nombres
            ->chunk(100, function ($clientes) {
                foreach ($clientes as $cliente) {
                    Adjunto::firstOrCreate(
                        ['cliente_id' => $cliente->id, 'tipo' => 'selfie'],
                        [
                            'nombre' => $cliente->selfie,
                            'path' => 'clientes',
                            'url' => 'clientes/' . $cliente->selfie,
                        ]
                    );
                }
            });

        // --- Procesar clientes que no tienen 'foto_ci_frente' en adjuntos pero sí en clientes ---
        /* Cliente::whereNotNull('clientes.foto_ci_frente')
            ->leftJoin('adjuntos', function ($join) {
                $join->on('clientes.id', '=', 'adjuntos.cliente_id')
                     ->where('adjuntos.tipo', '=', 'cedula1');
            })
            ->whereNull('adjuntos.id')
            ->select('clientes.*')
            ->chunk(100, function ($clientes) {
                foreach ($clientes as $cliente) {
                    Adjunto::firstOrCreate(
                        ['cliente_id' => $cliente->id, 'tipo' => 'cedula1'],
                        [
                            'nombre' => $cliente->foto_ci_frente,
                            'path' => 'clientes',
                            'url' => 'clientes/' . $cliente->foto_ci_frente,
                        ]
                    );
                }
            }); */

        // --- Procesar clientes que no tienen 'foto_ci_dorso' en adjuntos pero sí en clientes ---
        /* Cliente::whereNotNull('clientes.foto_ci_dorso')
            ->leftJoin('adjuntos', function ($join) {
                $join->on('clientes.id', '=', 'adjuntos.cliente_id')
                     ->where('adjuntos.tipo', '=', 'cedula2');
            })
            ->whereNull('adjuntos.id')
            ->select('clientes.*')
            ->chunk(100, function ($clientes) {
                foreach ($clientes as $cliente) {
                    Adjunto::firstOrCreate(
                        ['cliente_id' => $cliente->id, 'tipo' => 'cedula2'],
                        [
                            'nombre' => $cliente->foto_ci_dorso,
                            'path' => 'clientes',
                            'url' => 'clientes/' . $cliente->foto_ci_dorso,
                        ]
                    );
                }
            }); */
    }
    
}
