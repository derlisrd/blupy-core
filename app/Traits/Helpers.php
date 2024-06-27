<?php

namespace App\Traits;

use App\Services\FarmaService;
use App\Services\InfinitaService;

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
                    'credito'=> $dato->limiteCreditoTotal,
                ];
            }
        }
        return (object)[
            'funcionario'=>0,
            'credito'=> 0,
        ];
    }

    public function registrarInfinita(Object $cliente){
        $infinitaService = new InfinitaService();
        $registrarEnInfinita = (object)$infinitaService->registrar((object)$cliente);

        $dataInfinita = (object) $registrarEnInfinita->data;

            if(property_exists($dataInfinita,'CliId')){
                if($dataInfinita->CliId == '0'){
                    return [
                        'register'=>false
                    ];
                }
                return [
                    'cliid'=>$dataInfinita->CliId,
                    'estado'=> trim($dataInfinita->SolEstado),
                    'id'=>$dataInfinita->SolId,
                    'register'=>true
                ];
            }
            return [
                'register'=>false
            ];
    }



}
