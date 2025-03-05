<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

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

    public function clientePorCodigo(String $codigo){
        return $this->get('cliente/getClienteCodigo',['codigo' => $codigo]);
    }
    public function cliente(String $cedula){
        return $this->get('cliente/getCliente',['documento' => $cedula]);
    }

    public function MiCreditoContratosPorDocumento(String $documento){
        return $this->get('micredito-contratos/',['documento' => $documento]);
    }
    public function MiCreditoContratosPorCodigo(String $codigo){
        return $this->get('micredito-contratos/',['codigo' => $codigo]);
    }

    public function actualizarPedidosQR($codigo, $numero_cuenta, $numero_tarjeta, $movimiento){
        return $this->post('pedidosqr/autorizar',['codigo' => $codigo,'numero_cuenta'=>$numero_cuenta,'numero_tarjeta'=>$numero_tarjeta,'movimiento'=>$movimiento]);
    }
    public function movimientos(String $cedula, String $periodo){
        return $this->get('cliente/getMovimientos',['documento' => $cedula, 'periodo' =>$periodo]);
    }
    public function sucursales(){
        return $this->get('estructura/getEstructuras',[]);
    }
    public function ventasRendidas($fecha){
        return $this->get('ventas/deldia',['fecha'=>$fecha]);
    }
    public function ventasRendidasPorCliente($fecha,$documento){
        return $this->get('ventas/porcliente',['fecha'=>$fecha,'documento'=>$documento]);
    }

    public function ingresarContrato($documento){
        return $this->post('contratos-blupy/ingresar',['documento'=>$documento]);
    }


    private function post(String $endpoint,Array $parametros) {
        try {
            $response = Http::withHeaders($this->header)->post($this->url . '/' . $endpoint, $parametros);
            $json = $response->json();
            return [
                'data'=>$json,
                'status'=>$response->status()
            ];
        } catch (RequestException $e) {
            Log::error($e);
            return [
                'status' => $e->response ? $e->response->status() : null,
                'data' => $e->response ? $e->response->json() : null,
            ];
        }
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
