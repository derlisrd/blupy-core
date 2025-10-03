<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Models\Tarjeta;
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
    public $timeout = 300;

    public function handle(): void
    {


        $jsonArchivo = json_decode(file_get_contents(public_path('tarjetas.json')), true);

        // Verificar si el JSON fue leído correctamente
        if ($jsonArchivo === null) {
            throw new \Exception('Error al leer o decodificar el archivo JSON');
        }
        $infinitaService = new InfinitaService();
        foreach($jsonArchivo as $sol){
            $cliente = Cliente::where('cedula',$sol['cedula'])->first();
            if($cliente){
                $cedula = ($sol['cedula']);
                $clienteId = $cliente->id;
                $resInfinita = (object) $infinitaService->ListarTarjetasPorDoc($cedula);
                $infinitaData = (object)$resInfinita->data;
                if(property_exists($infinitaData,'Tarjetas')){
                    $tarjeta = ($infinitaData->Tarjetas[0]);
                    $tarjeta = Tarjeta::firstOrCreate(
                        ['cliente_id' => $clienteId], // Condición correcta como array asociativo
                        [
                            'cliente_id'=>$clienteId,
                            'cuenta'=>$tarjeta['MaeCtaId'],
                            'tipo' => $tarjeta['MTTipo'] === 'P' ? 1 : 2,
                            'numero' => $tarjeta['MTNume'],
                            'linea' =>$tarjeta['MTLinea'],
                            'bloqueo' => $tarjeta['MTBloq'] === '' ? 0 : 1,
                            'motivo_bloqueo' => $tarjeta['MotBloqNom']
                        ]
                    );
                }
            } else{
              //($sol['cedula']);
            }
        }

    }
}
