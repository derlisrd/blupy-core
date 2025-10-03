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
    public $timeout = 120;

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
        $clientes = Cliente::where('funcionario', 1)->cursor();

        foreach ($clientes as $cliente) {
            try {
                $res = $farmaService->cliente($cliente);

                if (!isset($res['data']['result']) || empty($res['data']['result'])) {
                    continue;
                }

                $esFuncionario = $res['data']['result'][0]['esFuncionario'] ?? null;

                if ($esFuncionario !== null) {
                    $funcionario = $esFuncionario == "N" ? 0 : 1;

                    // Actualizar directamente sin recuperar el modelo completo
                    Cliente::where('id', $cliente->id)
                          ->update(['funcionario' => $funcionario]);
                }
            } catch (\Exception $e) {
                // Manejar el error o registrarlo
                throw $e;
            }
        }
    }
}
