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
        SolicitudCredito::where('estado_id', 5)
        ->where('created_at', '<', $fechaCorte) 
        ->chunkById(50, function ($solicitudes) { // Procesa en lotes de 50
            $webserviceInfinita = new InfinitaService();

            foreach ($solicitudes as $solicitud) {
                // Llama a la API de Infinita
                $webserviceInfinita->anularSolicitud($solicitud->codigo);
                
                // Actualiza el estado
                $solicitud->update([
                    'estado' => 'Anulada',
                    'estado_id' => 13
                ]);
            }
            
            // 😴 Pausa de 1 a 2 segundos entre cada lote para evitar la saturación de la API externa
            sleep(1); 
        });
        
    SupabaseService::LOG('JOB', 'Job de solicitudes finalizado');
    }
}
