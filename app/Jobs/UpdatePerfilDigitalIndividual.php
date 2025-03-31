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

class UpdatePerfilDigitalInidividual implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $cliente;

    // Configuración de reintentos
    public $tries = 3;
    public $backoff = 30; // segundos entre reintentos

    /**
     * Create a new job instance.
     */
    public function __construct(Cliente $cliente)
    {
        $this->cliente = $cliente;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {

            $infinitaService = new InfinitaService();


            // Verificamos con InfinitaService
            $res = $infinitaService->ListarTarjetasPorDoc($this->cliente->cedula);

            // Convertimos a objeto
            $data = (object)$res['data'];

            // Actualizamos directamente en la base de datos sin cargar el modelo completo
            $digitalValue = (property_exists($data, 'Tarjetas') && !empty($data->Tarjetas)) ? 1 : 0;

            if($digitalValue == 1) {
                Cliente::where('id', $this->cliente->id)
                    ->update(['digital' => $digitalValue]);
            }

        } catch (\Exception $e) {
            SupabaseService::LOG('Error al procesar cliente ID: ' . $this->cliente->id, $e->getMessage());
            throw $e; // Para permitir reintentos
        }
    }
}
