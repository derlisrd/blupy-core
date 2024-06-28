<?php

namespace App\Traits;

use App\Services\FarmaService;
use App\Services\InfinitaService;
use Illuminate\Support\Facades\Log;

trait Helpers
{
    public function separarNombres(String $cadena) : Array{
        $nombresArray = explode(' ', $cadena);
        if (count($nombresArray) >= 2) {
            $nombre1 = $nombresArray[0];
            $nombre2 = implode(' ', array_slice($nombresArray, 1));
        } else {
            $nombre1 = $cadena;
            $nombre2 = '';
        }
        return [$nombre1,$nombre2];
    }

    public function clienteFarma(String $cedula){
        $farma = new FarmaService();
        $res = (object)$farma->cliente($cedula);
        $data = (object)$res->data;

        if(property_exists($data,'result')){
            $result = $data->result;
            if(count($result)>0){
                $dato = (object)$result[0];
                return (object)[
                    'funcionario'=> $dato->esFuncionario == "N" ? 0 : 1,
                    'lineaFarma'=> ($dato->limiteCreditoTotal > 0) ? 1 : 0,
                    'credito'=> $dato->limiteCreditoTotal,
                    'asofarma'=> ($dato->esFuncionario == "N" && ((int)$dato->limiteCreditoTotal) > 0 ) ? 1 : 0,
                    'completado'=>1
                ];
            }
        }
        return (object)[
            'funcionario'=>0,
            'credito'=> 0,
            'asofarma'=>0,
            'completado'=>0
        ];
    }

    public function verificarSiTieneInfinita(String $cedula){
        $infinitaService = new InfinitaService();

        $response = (object)$infinitaService->TraerPorDocumento($cedula);
        $datosDeInfinita = (object) $response->data;

        $response = [ 'cliid'=>0, 'tieneRegistro'=>false ];
        if(property_exists($datosDeInfinita,'CliId') && $datosDeInfinita->CliId !== '0'){
            $response = [ 'cliid'=>$datosDeInfinita->CliId, 'tieneRegistro'=>true ];
        }
        return (object) $response;
    }

    public function registrarInfinita(Object $cliente){
        $infinitaService = new InfinitaService();
        $registrarEnInfinita = (object)$infinitaService->registrar((object)$cliente);
        $response = [
            'register'=>false
        ];
        $dataInfinita = (object) $registrarEnInfinita->data;
            if(property_exists($dataInfinita,'CliId')){
                if( $dataInfinita->CliId !== '0'){
                    $response = [
                        'cliId'=>$dataInfinita->CliId,
                        'estadoSolicitud'=> trim($dataInfinita->SolEstado),
                        'solicitudId'=>$dataInfinita->SolId,
                        'register'=>true
                    ];
                }
            }
        return  $response;
    }


    public function userInfo($cliente,$token){
        return [
            'name'=>$cliente->user->name,
            'nombres'=>trim($cliente->nombre_primero . ' ' . $cliente->nombre_segundo),
            'apellidos'=>trim($cliente->apellido_primero . ' ' . $cliente->apellido_segundo),
            'cedula'=>$cliente->cedula,
            'fechaNacimiento'=>$cliente->fecha_nacimiento,
            'email'=>$cliente->user->email,
            'telefono'=>$cliente->celular,
            'celular'=>$cliente->celular,
            'solicitudCredito'=>$cliente->solicitud_credito,
            'funcionario'=>$cliente->funcionario,
            'aso'=>$cliente->asofarma,
            'token'=>$token
        ];
    }






}
