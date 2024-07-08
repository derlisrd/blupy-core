<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

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

    public function solicitudLineaDeCredito($cliente)
    {
        $data = (object)[
            "wSolicitud" => (object)[
                "SolApe1"=> $cliente->apellido_primero,
                "SolApe2"=> $cliente->apellido_segundo ?? "",
                "SolCed"=> $cliente->cedula,
                "SolCel"=> $cliente->celular,
                "SolDir"=> $cliente->calle . $cliente->numero_casa ?? '',
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
                "SolLinea"=> 300000,
                "SolMail"=> $cliente->email,
                "SolNom1"=> $cliente->nombre_primero,
                "SolNom2"=> $cliente->nombre_segundo ?? "",
                "SolProfId"=> $cliente->profesion_id ?? 0,
                "SolFec"=> Carbon::now()->format('Y-m-d'),
                "SolProdId"=> 172, // 172 solicitud de credito
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
                "SolMaeCta"=> 0,
                "SolMonId"=> 6900,
                "Adicional" => [],
                "SolNacId"=> 172,
                "SolObs"=> "",
                "SolRUC"=> "0000000",
                "SolSepBi"=> "N",
                "SolSexo"=> "M",
                "SolSucNro"=> 1,
                "SolTcEmb"=> "D",
                "SolTcTip"=> "P",
                "SolTel"=> "",
                "SolTipCal"=> 5,
                "SolTipViv"=> "P",
                "SolTipVto"=> 1,
                "SolVendId"=> 0,
                "SolicGarId"=> 0
            ],
            "Proceso"=> 2
        ];
        return $this->post('IngresarSolicitud',$data);
    }

    public function registrar(Object $cliente)
    {
        $data = (object)[
            "wSolicitud" => (object)[
                "SolApe1"=> $cliente->apellido_primero,
                "SolApe2"=> $cliente->apellido_segundo ?? "",
                "SolCed"=> $cliente->cedula,
                "SolCel"=> $cliente->celular,
                "SolFNa"=> $cliente->fecha_nacimiento,
                "SolFec"=> Carbon::now()->format('Y-m-d'),
                "SolMail"=> $cliente->email,
                "SolNom1"=> $cliente->nombre_primero,
                "SolNom2"=> $cliente->nombre_segundo ?? "",
                "SolProdId"=> 171, // 171 registro
                "AfinId" => 1,
                "BancaId"=> 1,
                "DesCreId"=> 0,
                "LugTrabId"=> 0,
                "MarcaId"=> 1,
                "MedioId"=> 0,
                "OficialId"=> 0,
                "Sol1Vto"=> "2023-03-20",
                "SolAsoId"=> 0,
                "SolAsoOrd"=> "123",
                "SolAuxId"=> 0,
                "SolCanPer"=> 1,
                "SolCargo"=> "CARGO",
                "SolCond"=> "TAR",
                "SolConsol"=> 0,
                "SolCuoC"=> 1,
                "SolCygSala"=> 0,
                "SolDir"=> '',
                "SolDepId" => 0,
                "SolCiuId" => 0,
                "SolBarId" => 0,
                "SolEsCiv"=> 1,
                "SolDirLat" =>  '',
                "SolDirLon" => '',
                "SolLabDirLat" => '',
                "SolLabDirLon" => '',
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
                "SolLabEmp" => '',
                "SolLabAntA"=> 0,
                "SolLabAntM" => 0,
                "SolLabDir"=> '',
                "SolLabFecIn"=> "",
                "SolLabSal"=> '',
                "SolLabTel"=> '',
                "SolLabTipId"=> 0,
                "SolLinea"=> 300000,
                "SolMaeCta"=> 0,
                "SolMonId"=> 6900,
                "Adicional" => [],
                "SolNacId"=> 172,
                "SolObs"=> "",
                "SolProfId"=> 0,
                "SolRUC"=> "0000000",
                "SolSepBi"=> "N",
                "SolSexo"=> "M",
                "SolSucNro"=> 1,
                "SolTcEmb"=> "D",
                "SolTcTip"=> "P",
                "SolTel"=> "",
                "SolTipCal"=> 5,
                "SolTipViv"=> "P",
                "SolTipVto"=> 1,
                "SolVendId"=> 0,
                "SolicGarId"=> 0
            ],
            "Proceso"=> 2
        ];

        return $this->post('IngresarSolicitud',$data);
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
