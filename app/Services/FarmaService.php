<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class FarmaService
{
    private $url;
    private $token;
    private $header;

    public function __construct() {
        $this->url = config('services.farma.url');
        $this->token = config('services.farma.token');
        $this->header = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ];
    }

    public function cliente(String $cedula){
        return $this->get('cliente/getCliente',['documento' => $cedula]);
    }

    public function movimientos(String $cedula, String $periodo){
        return $this->get('cliente/getMovimientos',['documento' => $cedula, 'periodo' =>$periodo]);
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
}
