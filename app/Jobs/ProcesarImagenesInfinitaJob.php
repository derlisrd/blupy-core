<?php

namespace App\Jobs;

use App\Services\InfinitaService;
use App\Services\SupabaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class ProcesarImagenesInfinitaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private string $cedula,
        private string $fotoSelfieReq,
        private string $fotoFrontalReq,
        private string $fotoDorsalReq
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try{
        $infinitaService = new InfinitaService();
        $fotoselfie = preg_replace('#data:image/[^;]+;base64,#', '', $this->fotoSelfieReq);
        $infinitaService->enviarSelfie($this->cedula, $fotoselfie);
    

        $foto1 = preg_replace('#data:image/[^;]+;base64,#', '', $this->fotoFrontalReq);
        $foto2 = preg_replace('#data:image/[^;]+;base64,#', '', $this->fotoDorsalReq);
        $infinitaService->enviarFotoCedula($this->cedula, $foto1, $foto2);
    
        } catch (\Throwable $th) {
            SupabaseService::LOG('Error ProcesarImagenesRegistroJob: ' . $this->cedula, $th->getMessage());
            throw $th; // re-throw para que el job reintente
        }
    }
}
