<?php

namespace App\Jobs;

use App\Models\SolicitudCredito;
use App\Services\InfinitaService;
use App\Services\SupabaseService;
use Carbon\Carbon;
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
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        SupabaseService::LOG('JOB', 'Job de solicitudes iniciadada');
        //created_at sea de una mes para atras
        $fechaCorte = Carbon::now()->subDays(30);
        $solicitudes = SolicitudCredito::where('estado_id', 5)
        ->where('created_at', '<', $fechaCorte) 
        ->get();

        $webserviceInfinita = new InfinitaService();

            foreach ($solicitudes as $solicitud) {
                // Llama a la API de Infinita
                $webserviceInfinita->anularSolicitud($solicitud['codigo']);
                
                // Actualiza el estado
                $solicitud->update([
                    'estado' => 'Anulada',
                    'estado_id' => 13
                ]);
            }
        
    SupabaseService::LOG('JOB', 'Job de solicitudes finalizado');
    }
}
