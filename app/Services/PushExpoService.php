<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushExpoService
{
    private $urlService;
    public function __construct(){
        $this->urlService = env('PUSH_SERVICE');
    }

    public function send(array $to, string $title,string $body){
        try {
            Http::post($this->urlService, [
                'to' => $to,
                'title' =>$title,
                'body'=>$body
            ],['Content-Type' => 'application/json']);
            return true;
        } catch (\Throwable $th) {
            Log::error($th);
            return false;
        }
    }
}
