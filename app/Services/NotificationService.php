<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
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
            $json = $response->json();
            return [
                'data'=>$json,
                'status'=>$response->status()
            ];

        } catch (RequestException $e) {
            throw $e;
            return [
                'status' => $e->response ? $e->response->status() : 500,
                'data' => $e->response ? $e->response->json() : null,
            ];
        }
    }



}
