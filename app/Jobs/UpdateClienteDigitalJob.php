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
        $clientes = Cliente::where('digital',1)->select('id','cedula');
        $infinita = new InfinitaService();
        foreach ($clientes->get() as $cliente) {
            $digital = $infinita->ListarTarjetasPorDoc($cliente->cedula);
            $infinita = (object)$digital['data'];
            if(property_exists( $infinita,'Tarjetas')){
                $cliente->digital = 1;
                $cliente->save();
            }
        }
    }
}
