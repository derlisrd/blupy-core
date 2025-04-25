<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaService
{
    private $url;
    private $key;
    public function __construct()
    {
        $this->url = env('WA_SERVICE_URL');
        $this->key = env('WA_SERVICE_API_KEY');
    }


    public function send(string $numero, string $texto){
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-api-key' => $this->key
            ])
            ->post($this->url . '/send',[
                'number' => $numero,
                'text' => $texto,
            ]);
            $json = $response->json();
            Log::info("message sent to wa",[
                'numero'=>$numero,
                'texto'=>$texto,
                'response'=>$json,
                'status'=>$response->status()
            ]);
            return [
                'data'=>$json,
                'status'=>$response->status()
            ];
            $json = $response->json();
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
