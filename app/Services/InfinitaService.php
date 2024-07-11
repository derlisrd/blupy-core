<?php

namespace App\Services;

use Carbon\Carbon;
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

    public function ExtractoCerrado($Maectaid,$Mtnume,$Periodo){
        return $this->get('ExtractoCerrado',['Maectaid' => $Maectaid,'Mtnume'=>$Mtnume,'Periodo' => $Periodo]);
    }

    public function movimientosPorFecha(String $cuenta, String $periodo){
        return $this->get('TarjMovimPorFecha',['Maectaid'=>$cuenta,'Periodo'=>$periodo,'Mtnume'=>"1"]);
    }

    public function TraerDatosCliente($cliid){
        return $this->get('TraerDatosCliente',['Cliid'=>$cliid]);
    }

    public function ListarTarjetasPorDoc($cedula)
    {
        return $this->get('ListarTarjetasPorDoc',['Mtdocu' => $cedula]);
    }

    public function listarProfesiones()
    {
        return $this->get('ListarProfesiones',[]);
    }

    public function listarTiposLaboral()
    {
        return $this->get('ListarTiposLaboral',[]);
    }

    public function ModificarCliente ($clienteId,$arrayDatas){
        $data = (object)[
            "CliId"=>$clienteId,
            "wCliente"=>(object)$arrayDatas
        ];
        return $this->post('ModificarCliente',$data);
    }

    public function TraerPorDocumento(String $cedula) {
        return $this->get('TraerPorDocumento',['Clidocu' => $cedula]);
    }

    public function enviarComprobantes($cedula,$comprobanteIngreso,$ande){
        $data = (object)[
            "CliAdj" => (object)[
                "CliDocu" => $cedula,
                "adjunto" => array(
                    (object)[
                        "titulo" => "INGRESO",
                        "detalle" => "COMPROBANTE DE INGRESO",
                        "imagen" => $comprobanteIngreso
                    ],
                    (object)[
                        "titulo" => "ANDE",
                        "detalle" => "Comprobante de pago de ANDE",
                        "imagen" => $ande
                    ]
                )
            ]
        ];
        return $this->post('IngresarAdj',$data);
    }


    public function enviarFotoCedula($cedula, $frontCi, $backCi)
    {
        $data = (object)[
            "CliAdj" => (object)[
                "CliDocu" => $cedula,
                "adjunto" => array(
                    (object)[
                        "titulo" => "CEDULA FRENTE",
                        "detalle" => "CEDULA FRENTE",
                        "imagen" => $frontCi
                    ],
                    (object)[
                        "titulo" => "CEDULA DORSO",
                        "detalle" => "CEDULA DORSO",
                        "imagen" => $backCi
                    ]
                )
            ]
        ];
        return $this->post('IngresarAdj',$data);
    }


    public function ListarSolicitudes(String $cedula, String $fechaDesde, String $fechaHasta)
    {
        return $this->get('ListarSolicitudes', [
            'Solced' => $cedula,
            'Fecdes' => $fechaDesde,
            'Fechas' => $fechaHasta
        ]);
    }

    public function ampliacionCredito($cliente,$solicitudDeLineaAmpliada,$numeroCuenta)
    {
        $datosDeCliente = $this->datosCliente($cliente,174,null,$solicitudDeLineaAmpliada,null);
        return $this->post('IngresarSolicitud',$datosDeCliente);
    }

    public function agregarAdicional($cliente,$adicional,$numeroCuenta)
    {
        $datosDeCliente = $this->datosCliente($cliente,173,$adicional,null,$numeroCuenta);
        return $this->post('IngresarSolicitud',$datosDeCliente);
    }

    public function solicitudLineaDeCredito($cliente)
    {
        $datosDeCliente = $this->datosCliente($cliente,172,[],null,null);
        return $this->post('IngresarSolicitud',$datosDeCliente);
    }

    public function registrar(Object $cliente)
    {
        $data = $this->datosCliente($cliente,171,[],null,null);
        return $this->post('IngresarSolicitud',$data);
    }

    private function datosCliente($cliente,$productoId,$adicionales,$solicitudDeLinea,$cuentaNumero){
        $adicionalesObject = $adicionales ? $adicionales  : [];
        return  (object)[
            "wSolicitud" => (object)[
                "SolProdId"=> $productoId, // 171 registro 172 solicitud de credito 173 adicional 174 ampliacion
                "SolTcTip"=> $adicionales ? "A" : "P",
                "SolApe1"=> $cliente->apellido_primero,
                "SolApe2"=> $cliente->apellido_segundo ?? "",
                "SolCed"=> $cliente->cedula,
                "SolCel"=> $cliente->celular,
                "SolDir"=> isset($cliente->calle) ? $cliente->calle : '' ,
                "SolDepId" => (int)$cliente->departamento_id ?? 0,
                "SolCiuId" => (int)$cliente->ciudad_id ?? 0,
                "SolBarId" => (int)$cliente->barrio_id ?? 0,
                "SolDirLat" => $cliente->latitud_direccion ?? '',
                "SolDirLon" => $cliente->longitud_direccion ?? '',
                "SolLabDirLat" => $cliente->latitud_empresa ?? '',
                "SolLabDirLon" => $cliente->longitud_empresa ?? '',
                "SolFNa"=> $cliente->fecha_nacimiento,
                "SolLabEmp" => $cliente->empresa ?? '',
                "SolLabAntA"=> (int)$cliente->antiguedad_laboral ?? 0,
                "SolLabAntM" => (int)$cliente->antiguedad_laboral_mes ?? 0,
                "SolLabDir"=> $cliente->empresa_direccion ?? '',
                "SolLabSal"=> $cliente->salario ?? '',
                "SolLabTel"=> $cliente->empresa_telefono ?? '',
                "SolLabTipId"=> $cliente->tipo_empresa_id ?? 0,
                "SolLinea"=> $solicitudDeLinea ? $solicitudDeLinea : 300000,
                "SolMaeCta"=> $cuentaNumero ? $cuentaNumero : 0,
                "SolMail"=> $cliente->email,
                "SolNom1"=> $cliente->nombre_primero,
                "SolNom2"=> $cliente->nombre_segundo ?? "",
                "SolProfId"=> $cliente->profesion_id ?? 0,
                "SolFec"=> Carbon::now()->format('Y-m-d'),
                "SolRUC"=> $cliente->cedula,
                "Adicional" => $adicionales ? $adicionalesObject : [],
                "AfinId" => 1,
                "BancaId"=> 1,
                "DesCreId"=> 0,
                "LugTrabId"=> 0,
                "MarcaId"=> 1,
                "MedioId"=> 0,
                "OficialId"=> 0,
                "Sol1Vto"=> "2023-03-20",
                "SolEsCiv"=> 1,
                "SolAsoId"=> 0,
                "SolAsoOrd"=> "123",
                "SolAuxId"=> 0,
                "SolCanPer"=> 1,
                "SolCargo"=> "CARGO",
                "SolCond"=> "TAR",
                "SolConsol"=> 0,
                "SolCuoC"=> 1,
                "SolCygSala"=> 0,
                "SolFijVto"=> false,
                "SolGarBarId"=> 0,
                "SolGarCiuId"=> 0,
                "SolGarCySala"=> 0,
                "SolGarDepId"=> 0,
                "SolGarEsCiv"=> 0,
                "SolGarFNa"=> "0001-01-01",
                "SolGarLabAntA"=> 0,
                "SolGarLabAntM"=> 0,
                "SolGarNacId"=> 0,
                "SolGarProfId"=> 0,
                "SolGarSala"=> 0,
                "SolId"=> 0,
                "SolImpSol"=> 300000,
                "SolImpor"=> 300000,
                "SolLabFecIn"=> "",
                "SolMonId"=> 6900,
                "SolNacId"=> 172,
                "SolObs"=> "",
                "SolSepBi"=> "N",
                "SolSexo"=> "M",
                "SolSucNro"=> 1,
                "SolTcEmb"=> "D",
                "SolTel"=> "",
                "SolTipCal"=> 5,
                "SolTipViv"=> "P",
                "SolTipVto"=> 1,
                "SolVendId"=> 0,
                "SolicGarId"=> 0
            ],
            "Proceso"=> 2
        ];
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

    private function post(String $endpoint,Object $body) {
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
