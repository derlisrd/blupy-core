<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class BlupyQrService
{
    private $url;
    private $xapikey;
    private $header;

    public function __construct() {
        $this->url = config('services.blupyqr.url');
        $this->xapikey = config('services.blupyqr.xapikey');
        $this->header = [
            'Content-Type' => 'application/json',
            'x-api-key' => $this->xapikey,
            'Accept' => 'application/json',
        ];
    }

    public function consultarQR($codigo_id_qr){
        return $this->get('cliente/consultar-qr/'.$codigo_id_qr,[]);
    }

    public function autorizarQR(Array $params){
        $data = (object)[
            'id'=>$params['id'],
            'documento'=>$params['documento'],
            'numero_cuenta'=>$params['numero_cuenta'],
            'telefono'=>$params['telefono'],
            'ip'=>$params['ip'],
            'localizacion'=>$params['localizacion'],
            'adicional'=>$params['adicional'],
            'numero_tarjeta'=>$params['numeroTarjeta']
        ];
        return $this->post('cliente/autorizar-qr',$data);
    }


    private function post(String $endpoint, Object $body) {
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


    private function get($endpoint,Array $query) {
        try {
            $response = Http::withHeaders($this->header)
                ->get($this->url . '/'.$endpoint,$query);
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
