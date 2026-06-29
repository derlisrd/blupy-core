<?php

namespace App\Jobs;

use App\Services\TigoSmsService;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class EnviarSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private String $texto;
    private String $nro;

    public function __construct(String $nro, String $texto)
    {
        $this->nro = $nro;
        $this->texto = $texto;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            
            $tigoService = new TigoSmsService();
            $tigoService->enviarSms($this->nro, $this->texto);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
