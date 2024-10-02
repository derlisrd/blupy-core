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
            $log = '';
            foreach($this->codigos as $codigo){
                $results = $this->consultarEstadoSolicitudInfinita($codigo);
                if($results && $results['id'] !== 5 ){
                    $log .= $results['id'].' -';
                }
            }
            SupabaseService::LOG('log_solic',$log);
        } catch (\Throwable $th) {
            throw $th;
            Log::error($th);
        }
    }
}
