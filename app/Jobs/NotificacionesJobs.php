<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class NotificacionesJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $text;
    public $title;
    public $tokens;
    /**
     * Create a new job instance.
     */
    public function __construct($title,$text,$tokens)
    {
        $this->text = $text;
        $this->title = $title;
        $this->tokens = $tokens;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Inicia enviar notificaciones");
            $chunks = array_chunk($this->tokens, 100);
            foreach ($chunks as $chunk) {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->post('https://exp.host/--/api/v2/push/send', [
                    'to' => $chunk,
                    'title' => $this->title,
                    'body' => $this->text,
                    'data' => ['screen'=>'flyer']
                ]);

                Log::info($response->body());
            }
            Log::info("Fin enviar notificaciones");
        } catch (\Throwable $th) {
            Log::info($th);
        }
    }
}
