<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Services\FarmaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdatePerfilJobs implements ShouldQueue
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
        $farmaService = new FarmaService();
        $clientes = Cliente::where('funcionario',0)->get();
        $funcionario = 0;

        foreach ($clientes as $key => $val) {
            $res = (object)$farmaService->cliente($val);
            $data = (object)$res->data;
            if(property_exists($data,'result')){
                $result = $data->result;
                if(count($result)>0){
                    $dato = (object)$result[0];
                    $funcionario = $dato->esFuncionario == "N" ? 0 : 1;
                    $cliente = Cliente::find($val['id']);
                    $cliente->funcionario = $funcionario;
                    $cliente->save();
                }
            }

        }
    }
}
