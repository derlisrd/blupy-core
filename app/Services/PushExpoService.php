<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;

class PushExpoService
{
    private $urlService;
    public function __construct(){
        $this->urlService = env('PUSH_SERVICE');
    }

    public function send(array $to, string $title,string $body, array $data){
        try {
            $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->post('https://exp.host/--/api/v2/push/send', [
                    'to' => $to,
                    'title' => $title,
                    'body' => $body,
                   'data' => $data
                ]);
            //SupabaseService::LOG('Notificacion',$response);
        } catch (\Throwable $th) {
            return false;
        }
    }
}
