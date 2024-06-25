<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class BlupyQRCore
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
        return $this->get('cliente/consultar-qr/'.$codigo_id_qr);
    }

    public function autorizarQR($codigo_id_qr,$numerocuenta,$tel,$ip,$loc){
        $data = (object)[
            'id'=>$codigo_id_qr,
            'numero_cuenta'=>$numerocuenta,
            'telefono'=>$tel,
            'ip'=>$ip,
            'localizacion'=>$loc
        ];
        return $this->post($data,'cliente/autorizar-qr');
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


    private function get($endpoint) {
        try {
            $response = Http::withHeaders($this->header)
                ->get($this->url . '/'.$endpoint);
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
