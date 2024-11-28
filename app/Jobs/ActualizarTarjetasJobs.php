<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Services\InfinitaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
    public function handle(): void
    {
        $solicitudes = Cliente::join('solicitud_creditos as s','s.cliente_id','=','clientes.id')->where('s.tipo',1)->get();
        $infinitaService = new InfinitaService();
        foreach($solicitudes as $sol){
            $cedula = ($sol['cedula']);
            $resInfinita = (object) $infinitaService->ListarTarjetasPorDoc($cedula);
            $infinitaData = (object)$resInfinita->data;
            Log::info($resInfinita->data);
        }

    }
}
