<?php

namespace App\Traits;

use App\Services\FarmaService;

trait ContratosBlupyFarmaTraits
{
    public function firmadoContratoPorCodigo(string $codigo){
        $farmaService = new FarmaService();
        $response = $farmaService->MiCreditoContratosFirmado($codigo);
        $data = $response['data'];
        $success = false;
        $results = null;
        $status = 400;

        if($data['ok']){
            $success = true;
            $results = $data['result'];
            $status = 200;
        }

        return  [
            'success' => $success,
            'results' => $results,
            'status' => $status
        ];
    }
    public function recibirContratoPorCodigo(string $codigo){
        $farmaService = new FarmaService();
        $response = $farmaService->MiCreditoContratosRecibir($codigo);
        $data = $response['data'];
        $success = false;
        $results = null;
        $status = 400;

        if($data['ok']){
            $success = true;
            $results = $data['result'];
            $status = 200;
        }

        return  [
            'success' => $success,
            'results' => $results,
            'status' => $status
        ];
    }
    public function consultarContratoBlupyPorDocumentoEnFarma(string $documento){
        $farmaService = new FarmaService();
        $response = $farmaService->MiCreditoContratosPorDocumento($documento);
        $data = (object) $response['data'];
        $success = false;
        $results = null;
        $status = 400;

        if($data->ok){
            $success = true;
            $results = $data->result;
            $status = 200;
        }

        return (object) [
            'success' => $success,
            'results' => $results,
            'status' => $status
        ];
    }
    public function consultarContratoPorCodigo($numero_contrato){
        $farmaService = new FarmaService();
        $response = $farmaService->MiCreditoContratosPorCodigo($numero_contrato);
        $data = (object) $response['data'];
        $success = false;
        $results = null;
        $status = 400;

        if($data->ok){
            $success = true;
            $results = $data->result;
            $status = 200;
        }

        return (object) [
            'success' => $success,
            'results' => $results,
            'status' => $status
        ];
    }
}
