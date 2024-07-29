<?php

namespace App\Traits;

use App\Services\InfinitaService;

trait SolicitudesInfinita
{
    private $infinitaService;

    public function __construct()
    {
        $this->infinitaService = new InfinitaService();
    }

    public function listaSolicitudes($cedula,$desde,$hasta){
        $res = (object)$this->infinitaService->ListarSolicitudes($cedula,$desde,$hasta);
        $solicitudes = (object)$res->data;
        $result = [];
        if(property_exists($solicitudes,'wSolicitudes')){
            foreach ($solicitudes->wSolicitudes as $value) {
                array_push($results,[
                    'id'=>$value['SolId'],
                    'producto'=>$value['SolProdId'],
                    'estado'=>$value['SolEstado'],
                    'descripcion'=>$value['SolProdNom'],
                    'fecha'=>$value['SolFec'],
                    'importe'=>(int) $value['SolImpor'],
                ]);
            }
        }
        return $result;
    }

    public function ingresarSolicitudInfinita($cliente){

        $res = (object)$this->infinitaService->solicitudLineaDeCredito($cliente);
        $resultadoInfinitaObject = (object) $res->data;
        $resultado = ['success'=>false, 'message'=>'Error en la solicitud'];
        if(property_exists($resultadoInfinitaObject,'CliId')){
            if($resultadoInfinitaObject->CliId !== '0'){
                $codigoSolicitud = $resultadoInfinitaObject->SolId;
                $estadoId = 11;
                $estado = trim($resultadoInfinitaObject->SolEstado);
                if($estado == 'Contrato Pendiente'){
                    $estadoId = 5;
                }
                if($estado == 'Pend. Aprobación'){
                    $estadoId= 3;
                }

                $resultado = [
                    'success'=>true,
                    'estado'=>$estado,
                    'codigo'=>$codigoSolicitud,
                    'id'=> $estadoId
                ];
            }
        }
        return (object) $resultado;
    }

    public function ampliacionEnInfinita($datosDeCliente,$lineaSolicitada,$numeroCuenta,$ingreso,$ande){

        $infinita = (object)$this->infinitaService->ampliacionCredito($datosDeCliente,$lineaSolicitada,$numeroCuenta);
            $res = (object) $infinita->data;
            $resultado = ['success'=>false];
            if($res->CliId == "0"){
                $message = property_exists($res,'Messages') ? $res->Messages[0]['Description'] : 'Error de servidor. ERROR_CLI';
                return ['success' => false,'message' => $message];
            }

            $resultado = [
                'success'=>true,
                'codigo'=>$res->$res->SolId,
                'estado'=>trim($res->SolEstado)
            ];

            $ingreso = preg_replace('#data:image/[^;]+;base64,#', '', $ingreso);
            $ande = preg_replace('#data:image/[^;]+;base64,#', '', $ande);
            $this->infinitaService->enviarComprobantes($datosDeCliente->cedula, $ingreso, $ande);
            return (object) $resultado;
    }

    public function adicionalEnInfinita($clientePrincipal,$datosDelAdicional,$cuentaPrincipal){
        $datos = [(object)$datosDelAdicional];

        $infinita = (object) $this->infinitaService->agregarAdicional($clientePrincipal,$datos,$cuentaPrincipal);
        $res = (object)$infinita->data;
        if($res->CliId == "0"){
            $message = property_exists($res,'Messages') ? $res->Messages[0]['Description'] : 'Error de servidor. ERROR_CLI';
            return (object)['success'=>false, 'message'=>$message,'results'=>null];
        }
        return (object)[
            'success'=>true,
            'results'=> (object) [
            'solicitudId'=>$res->SolId,
            'solicitudEstado'=>$res->SolEstado
            ]
        ];
    }
}
