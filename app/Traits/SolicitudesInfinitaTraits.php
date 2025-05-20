<?php

namespace App\Traits;

use App\Services\InfinitaService;
use App\Services\SupabaseService;
use Illuminate\Support\Facades\Log;

trait SolicitudesInfinitaTraits
{

    public function aprobarSolicitudInfinita(string $codigo){
        $infinitaService = new InfinitaService();
        $res = (object) $infinitaService->AprobarSolicitud($codigo);
        $data = (object) $res->data;
        $results = ['success'=>false,'message'=>null,'status'=>400];
        if($data && property_exists($data, 'Messages')){
            $results = [
                'success'=> $data->Messages[0]['Id'] == 'OK',
                'message'=>$data->Messages[0]['Description'],
                'status' => $data->Messages[0]['Id'] == 'OK' ? 200 : 400
            ];
        }
        return (object) $results;
    }


    public function consultarEstadoSolicitudInfinita(string $codigo){
        $infinitaService = new InfinitaService();
        $res = (object) $infinitaService->ConsultaEstadoSolicitud($codigo);

        $data = (object) $res->data;

        if($data && property_exists($data, 'wDato')){
            $results = [
                'estado'=>$data->wDato[0]['DatoDesc'],
                'id'=>(int) $data->wDato[0]['DatoId']
            ];
            return $results;
        }
        return null;
    }

    public function actualizarSolicitudInfinita(string $codigo){
        $infinitaService = new InfinitaService();
        $infinita = (object)$infinitaService->ConsultaEstadoSolicitud($codigo);
        $data = (object) $infinita->data;
        $results = ['success'=>false,'estado'=>null, 'id'=>null];
        if($data && property_exists($data, 'wDato')){
            $results = [
                'success'=>true,
                'estado'=>$data->wDato[0]['DatoDesc'],
                'id'=>$data->wDato[0]['DatoId']
            ];
        }
        return (object) $results;
    }

    public function listaSolicitudes($cedula,$desde,$hasta){
        $infinitaService = new InfinitaService();
        $res = (object)$infinitaService->ListarSolicitudes($cedula,$desde,$hasta);
        $solicitudes = (object)$res->data;
        $result = [];
        if(property_exists($solicitudes,'wSolicitudes')){
           $arr = ($solicitudes->wSolicitudes);
            foreach ($arr as $value) {
                array_push($result,[
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
        $infinitaService = new InfinitaService();
        $res = (object)$infinitaService->solicitudLineaDeCredito($cliente);
        $resultadoInfinitaObject = (object) $res->data;
        $resultado = ['success'=>false, 'message'=>'Error en la solicitud','estado'=>null,'codigo'=>null,'id'=>null];
        SupabaseService::LOG('ingresar solicitud',$res->data);
        if(property_exists($resultadoInfinitaObject,'CliId')){
            if($resultadoInfinitaObject->CliId == '0'){
                $resultado = ['success'=>false, 'message'=> $resultadoInfinitaObject->Messages[0]['Description'] ,'estado'=>null,'codigo'=>null,'id'=>null];
            }
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

    public function ampliacionEnInfinita($datosDeCliente,$lineaSolicitada,$numeroCuenta){
        $infinitaService = new InfinitaService();
        $infinita = $infinitaService->ampliacionCredito($datosDeCliente,$lineaSolicitada,$numeroCuenta);
        $res = (object) $infinita['data'];
        $resultado = ['success'=>false];
        SupabaseService::LOG('core_infinita_ampliacion_86',$res);
        if($res->CliId == "0"){
            SupabaseService::LOG('core_infinita_ampliacion_88',$res);
            $message = property_exists($res,'Messages') ? $res->Messages[0]['Description'] : 'Error de servidor. ERROR_CLI';
            return ['success' => false,'message' => $message];
        }
            $resultado = [
                'success'=>true,
                'codigo'=>$res->$res->SolId,
                'estado'=>trim($res->SolEstado),
                'message'=>'Ingresado'
            ];


        return (object) $resultado;
    }



    public function adicionalEnInfinita($clientePrincipal,$datosDelAdicional,$cuentaPrincipal){
        $ADICIONALES = [];
        array_push($ADICIONALES, [
            'SolAdiCed' =>$datosDelAdicional['cedula'],
            "SolAdiNom1" => $datosDelAdicional['nombre1'],
            "SolAdiApe1"=> $datosDelAdicional['apellido1'],
            "SolAdiTel" => $datosDelAdicional['nombre1'],
            "SolAdiLim" => $datosDelAdicional['limite'],
            "SolAdiNom2" => $datosDelAdicional['nombre2'],
            "SolAdiApe2" => $datosDelAdicional['apellido2'],
            "SolAdiDire" => $datosDelAdicional['direccion']
        ]);
        $infinitaService = new InfinitaService();
        $infinita = (object) $infinitaService->agregarAdicional($clientePrincipal,$ADICIONALES,$cuentaPrincipal);
        $res = (object)$infinita->data;

        if(property_exists($res,'CliId')){
            $message = property_exists($res,'Messages') ? $res->Messages[0]['Description'] : 'Error de servidor. ERROR_CLI';

            if($res->CliId == '0'){
                return (object)[
                    'success'=>false,
                    'results'=> null,
                    'message'=> $message
                ];
            }
            if(property_exists($res,'SolId')){
                return (object)[
                    'success'=>true,
                    'results'=> (object) [
                    'solicitudId'=>$res->SolId,
                    'solicitudEstado'=>trim($res->SolEstado)
                    ]
                ];
            }
            return (object)[
                'success'=>false,
                'results'=> null,
                'message'=> $message
            ];

        }
        SupabaseService::LOG('adicional_error',$res);
        return (object)['success'=>false, 'message'=>'Error de servidor','results'=>null];
    }
}
