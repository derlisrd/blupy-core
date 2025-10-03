<?php

namespace App\Jobs;

use App\Models\SolicitudCredito;
use App\Services\InfinitaService;
use App\Traits\SolicitudesInfinitaTraits;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ActualizarSolicitudesJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, SolicitudesInfinitaTraits;

    public $codigos;
    /**
     * Create a new job instance.
     */
    public function __construct($codigos)
    {
        $this->codigos = $codigos;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $infinitaService = new InfinitaService();
            foreach($this->codigos as $codigo){
                $results = $this->consultarEstadoSolicitudInfinita($codigo);
                if($results && $results['id'] != 5){
                    $solicitud_actualizada = SolicitudCredito::where('codigo',$codigo)->update(
                        [
                            'estado_id'=>$results['id'],
                            'estado'=>$results['estado']
                        ]
                    );
                    /* $cliente = Cliente::find($solicitud_actualizada->cliente_id);
                    if($cliente){
                        $clienteId = $cliente->id;
                        $resInfinita = (object) $infinitaService->ListarTarjetasPorDoc($cliente->cedula);
                        $infinitaData = (object)$resInfinita->data;
                        if(property_exists($infinitaData,'Tarjetas')){
                            $tarjeta = ($infinitaData->Tarjetas[0]);
                            $tarjeta = Tarjeta::create(
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
                    } */
                    //($solicitud_actualizada);
                }
            }
        } catch (\Throwable $th) {
            throw $th;
            
        }
    }
}
