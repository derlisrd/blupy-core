<?php

namespace App\Jobs;

use App\Models\SolicitudCredito;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class IngresarContratoFarmaJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $ayer = Carbon::yesterday()->format('Y-m-d');
        $desde = $ayer . ' 00:00:00';
        $hasta = $ayer . ' 23:59:59';
        $solicitudes = SolicitudCredito::where('estado_id',5)
        ->whereBetween('solicitud_creditos.updated_at',[$desde,$hasta])
        ->join('clientes','clientes.id','=','solicitud_creditos.cliente_id')
        ->select('clientes.id','clientes.cedula')
        ->get();
        foreach($solicitudes as $val){

            Log::info($val['cedula']);
        }

    }
}
