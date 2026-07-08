<?php

namespace App\Jobs;

use App\Services\SmsLocalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class LocalEnviarSmsMorosoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $tries = 3;

    public function __construct(
        public string $numero,
        public string $mensaje
    ) {}

    public function handle(): void
    {
        $sms = new SmsLocalService();
        $sms->enviarSms($this->numero, $this->mensaje);
    }
}
