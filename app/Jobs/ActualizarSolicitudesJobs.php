<?php

namespace App\Jobs;

use App\Models\SolicitudCredito;
use App\Services\SupabaseService;
use App\Traits\SolicitudesInfinitaTraits;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ActualizarSolicitudesJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, SolicitudesInfinitaTraits;

    public $codigoDeSolicitudesPendientes;
    /**
     * Create a new job instance.
     */
    public function __construct($codigoDeSolicitudesPendientes)
    {
        $this->codigoDeSolicitudesPendientes = $codigoDeSolicitudesPendientes;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            foreach($this->codigoDeSolicitudesPendientes as $codigo){

                SupabaseService::LOG('result ',$codigo);


            }

        } catch (\Throwable $th) {
            throw $th;
            SupabaseService::LOG('Error_46_','Error al actualizar los pendientes');
        }
    }
}
