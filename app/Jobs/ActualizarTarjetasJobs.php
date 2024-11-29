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
        $solicitudes = Cliente::join('solicitud_creditos as s','s.cliente_id','=','clientes.id')->where('s.tipo',1)->where('s.estado_id','<>',11)->select('clientes.id','clientes.cedula');
        $infinitaService = new InfinitaService();
        Log::info($solicitudes->count());
        foreach($solicitudes->get() as $sol){
            $cedula = ($sol['cedula']);
            $clienteId = $sol['id'];
            sleep(1);
            $resInfinita = (object) $infinitaService->ListarTarjetasPorDoc($cedula);
            $infinitaData = (object)$resInfinita->data;
            if(property_exists($infinitaData,'Tarjetas')){
                $tarjeta = ($infinitaData->Tarjetas[0]);
                $dataInsert = [
                    'cliente_id'=>$clienteId,
                    'cuenta'=>$tarjeta['MaeCtaId'],
                    'tipo' => $tarjeta['MTTipo'] === 'P' ? 1 : 2,
                    'numero' => $tarjeta['MTNume'],
                    'linea' =>$tarjeta['MTLinea'],
                    'bloqueo' => $tarjeta['MTBloq'] === 'A',
                    'motivo_bloqueo' => $tarjeta['MotBloqNom']
                ];
                Tarjeta::create($dataInsert);
            }
        }

    }
}
