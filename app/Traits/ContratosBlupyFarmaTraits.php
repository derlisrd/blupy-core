<?php

namespace App\Traits;

use App\Services\FarmaService;

trait ContratosBlupyFarmaTraits
{
    public function consultarContratoBlupyPorDocumentoEnFarma(string $documento){
        $farmaService = new FarmaService();
        $response = (object) $farmaService->MiCreditoContratosPorDocumento($documento);
        $data = (object) $response->data;
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
    public function consultarContratoPorCodigo(){

    }
}
