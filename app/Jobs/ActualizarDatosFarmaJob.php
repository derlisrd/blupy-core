<?php

namespace App\Jobs;

use App\Models\Adicional;
use App\Models\Cliente;
use App\Services\FarmaService;
use App\Services\InfinitaService;
use App\Services\SupabaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class ActualizarDatosFarmaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private string $cedula
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $farmaResponse = (new FarmaService())->esAlianzaOFuncionario($this->cedula);
            $farmaData = $farmaResponse['data'];

            $asofarma = 0;
            $funcionario = 0;

            if ($farmaData && isset($farmaData['result'])) {
                $result = $farmaData['result'];
                if ($result['alianza'] === true) {
                    $asofarma = 1;
                }
                if ($result['funcionario'] === true) {
                    $funcionario = 1;
                }
            }

            $esAdicional = (bool) Adicional::where('cedula', $req->cedula)->first();

            $direccionCompletado = ($funcionario == 1 || $esAdicional || $asofarma == 1) ? 1 : 0;

            Cliente::where('cedula', $this->cedula)->update([
                'asofarma'             => $asofarma,
                'funcionario'          => $funcionario,
                'direccion_completado' => $direccionCompletado,
            ]);
        } catch (\Throwable $th) {
            SupabaseService::LOG('Error ActualizarDatosFarmaJob: ' . $this->cedula, $th->getMessage());
            throw $th;
        }
    }
}
