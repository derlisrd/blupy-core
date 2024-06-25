<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class InfinitaService
{
    private $url;
    private $token;
    private $header;

    public function __construct() {
        $this->url = config('services.infinita.url');
        $this->token = config('services.infinita.token');
        $this->header = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ];
    }



    public function TraerPorDocumento(String $cedula) {
        return $this->get('TraerPorDocumento',['Clidocu' => $cedula]);
    }


    private function get(String $endpoint,Array $parametros) {
        try {
            $response = Http::withHeaders($this->header)->get($this->url . '/' . $endpoint, $parametros);
            $json = $response->json();
            return [
                'data'=>$json,
                'status'=>$response->status()
            ];
        } catch (RequestException $e) {
            return [
                'status' => $e->response ? $e->response->status() : null,
                'data' => $e->response ? $e->response->json() : null,
            ];
        }
    }

    private function post($body, $endpoint) {
        try {
            $response = Http::withHeaders($this->header)
            ->post($this->url . '/'.$endpoint, $body);
            $json = $response->json();

            return [
                'data'=>$json,
                'status'=>$response->status()
            ];
        } catch (RequestException $e) {

            return [
                'status' => $e->response ? $e->response->status() : 500,
                'data' => $e->response ? $e->response->json() : null,
            ];
        }
    }

}
