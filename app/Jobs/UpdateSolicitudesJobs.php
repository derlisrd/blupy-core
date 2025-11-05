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
        // Usamos el mismo punto de corte (solicitudes de más de 30 días de antigüedad)
        $fechaCorte = Carbon::now()->subDays(30);
        $totalAnuladas = 0; // Contador para rastrear el progreso

        SupabaseService::LOG('JOB', 'Job de solicitudes iniciado');

        // 1. Usa chunkById() para procesar en lotes de 50
        SolicitudCredito::where('estado_id', 5)
            ->where('created_at', '<', $fechaCorte)
            ->chunkById(50, function ($solicitudes) use (&$totalAnuladas) { // 'solicitudes' es el lote de 50

                $webserviceInfinita = new InfinitaService();

                foreach ($solicitudes as $solicitud) {
                    // Llama a la API de Infinita
                    $webserviceInfinita->anularSolicitud($solicitud->codigo); // Usar $solicitud->codigo si es un objeto

                    // Actualiza el estado
                    $solicitud->update([
                        'estado' => 'Anulada',
                        'estado_id' => 13
                    ]);

                    $totalAnuladas++; // Incrementa el contador global
                }

                // 2. 🛑 Pausa obligatoria para evitar saturar la API externa
                // Ajusta el tiempo (ej. 1, 2, o 5 segundos) según la tolerancia de la API de Infinita
                sleep(2);

                // Opcional: Log del progreso de cada lote
                SupabaseService::LOG('CHUNK', "Lote de 50 procesado. Total anuladas hasta ahora: {$totalAnuladas}");
            });

        SupabaseService::LOG('JOB', 'Job de solicitudes finalizado. Total de registros anulados: ' . $totalAnuladas);
    }
}
