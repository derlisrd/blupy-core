<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Services\InfinitaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateClienteDigitalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $clientes = Cliente::where('digital',1)->select('id','cedula');

        foreach ($clientes->get() as $cliente) {
            $infinitaService = new InfinitaService();
            $resInfinita = $infinitaService->ListarTarjetasPorDoc($cliente['cedula']);
            $infinita = (object)$resInfinita['data'];
            $existe = 0;
            if(property_exists( $infinita,'Tarjetas')){
                $existe = 1;
            }
            $cliente->digital = $existe;
            $cliente->save();
        }
    }
}
