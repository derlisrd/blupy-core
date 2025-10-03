<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PushNativeJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
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
                //$response = 
                Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'x-api-key' => env('PUSH_SERVICE_API_KEY')
                ])->post(env('PUSH_SERVICE_URL') . '/send-push-difusion',[
                    'tokens' => $chunk,
                    'title' => $this->title,
                    'body' => $this->body,
                    'type' => $this->type,
                ]);
                //$json = $response->json();
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
