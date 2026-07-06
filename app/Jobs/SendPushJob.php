<?php

namespace App\Jobs;



use App\Services\SupabaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SendPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public string  $text;
    public string  $title;
    public array $tokens;
    /**
     * Create a new job instance.
     */
    public function __construct( array $tokens, string $title, string $text)
    {
        $this->tokens = $tokens;
        $this->title = $title;
        $this->text = $text;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {

            $chunks = array_chunk($this->tokens, 500);
            foreach ($chunks as $chunk) {
                app(\App\Services\PushService::class)->sendPushMulti($chunk, $this->title, $this->text);
            }
        } catch (\Throwable $th) {
            SupabaseService::LOG('Notificaciones','Error en enviar notificaciones');
        }
    }
}
