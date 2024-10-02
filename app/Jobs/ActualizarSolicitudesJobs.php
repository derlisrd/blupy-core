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
            SupabaseService::LOG('results',$this->codigos);

        } catch (\Throwable $th) {
            SupabaseService::LOG('Error_46_','Error de solicitudes');
        }
    }
}
