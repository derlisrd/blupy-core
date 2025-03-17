<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;


class NotificationService
{

    public function __construct(){

    }

    public function sendPush(array $data){
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-api-key' => env('PUSH_SERVICE_API_KEY')
            ])->post(env('PUSH_SERVICE_URL') . '/send-push-difusion',[
                'tokens' => [$data['tokens']],
                'title' => $data['title'],
                'body' => $data['body'],
                'type' => $data['type'],
            ]);
            return $response;

        } catch (\Throwable $th) {
            throw $th;
        }
    }



}
