<?php

namespace App\Jobs;

use App\Models\SolicitudCredito;
use App\Services\InfinitaService;
use App\Services\SupabaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateSolicitudesJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(){

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        SupabaseService::LOG('JOB','Job de solicitudes iniciadada');
        $soli2 = SolicitudCredito::where('estado_id',5)->get();
        $webserviceInfinita = new InfinitaService();
        foreach ($soli2 as $key => $val) {
            if($val['estado_id'] == 5){
                $resultado = (object)$webserviceInfinita->ConsultaEstadoSolicitud($val['codigo']);

                if($resultado && property_exists($resultado, 'wDato')){
                    if($resultado->wDato[0]['DatoDesc'] != 'Contrato Pendiente')
                    {
                    SolicitudCredito::where('id', $val['id'])
                    ->update([
                        'estado' => $resultado->wDato[0]['DatoDesc'],
                        'estado_id'=> $resultado->wDato[0]['DatoId']
                    ]);
                    }

                }
            }
        }
    }
}
