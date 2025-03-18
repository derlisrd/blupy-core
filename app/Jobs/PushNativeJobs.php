<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNativeJobs implements ShouldQueue
{
    use Queueable;
    public $body;
    public $title;
    public $tokens;
    public $type;

    /**
     * Create a new job instance.
     */
    public function __construct($title,$body,$tokens,$type)
    {
        $this->body = $body;
        $this->title = $title;
        $this->tokens = $tokens;
        $this->type = $type;
    }
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $chunksTokens = array_chunk($this->tokens, 100);
            foreach ($chunksTokens as $chunk) {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'x-api-key' => env('PUSH_SERVICE_API_KEY')
                ])->post(env('PUSH_SERVICE_URL') . '/send-push-difusion',[
                    'tokens' => $chunk,
                    'title' => $this->title,
                    'body' => $this->body,
                    'type' => $this->type,
                ]);
                $json = $response->json();
            }
            Log::info('Notificaciones nativas enviadas con exito');
        } catch (RequestException $e) {
            Log::error('Error al enviar notificaciones', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
