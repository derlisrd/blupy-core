<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Models\Tarjeta;
use App\Services\InfinitaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActualizarTarjetasJobs implements ShouldQueue
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
    public $timeout = 300;

    public function handle(): void
    {
        $solicitudes = Cliente::select('clientes.id','clientes.cedula');
        $infinitaService = new InfinitaService();
        Log::info($solicitudes->count());
        foreach($solicitudes->get() as $sol){
            $cedula = ($sol['cedula']);
            $clienteId = $sol['id'];
            $resInfinita = (object) $infinitaService->ListarTarjetasPorDoc($cedula);
            $infinitaData = (object)$resInfinita->data;
            if(property_exists($infinitaData,'Tarjetas')){
                $tarjeta = ($infinitaData->Tarjetas[0]);
                $tarjeta = Tarjeta::firstOrCreate(
                    ['cliente_id' => $clienteId], // Condición correcta como array asociativo
                    [
                        'cliente_id'=>$clienteId,
                        'cuenta'=>$tarjeta['MaeCtaId'],
                        'tipo' => $tarjeta['MTTipo'] === 'P' ? 1 : 2,
                        'numero' => $tarjeta['MTNume'],
                        'linea' =>$tarjeta['MTLinea'],
                        'bloqueo' => $tarjeta['MTBloq'] === '' ? 0 : 1,
                        'motivo_bloqueo' => $tarjeta['MotBloqNom']
                    ]
                );
            }
        }

    }
}
