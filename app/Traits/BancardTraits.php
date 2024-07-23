<?php

namespace App\Traits;

use App\Services\InfinitaService;

trait BancardTraits
{
    public function TraerDeudaPorDocumento(string $cedula, string $cuenta){
        $infinitaService = new InfinitaService();
        $res = (object)$infinitaService->ListarTarjetasPorDoc($cedula);
        $data = (object) $res->data;
        $resultado = [];
        if(property_exists($data,'Tarjetas')){
            $tarjetas = (array) $data->Tarjetas;
            foreach ($tarjetas as $item) {
                if ($item['MaeCtaId'] == $cuenta) {
                    $resultado = [
                       'descripcion'=>$item['SolProdNom'],
                       'deuda'=> (int) $item['MTSaldo'],
                       'minimo'=> (int) $item['MCPagMin'],
                       'linea'=> (int) $item['MTLinea'],
                       'nombre'=> $item['MTNomT'],
                       'otorgado'=>$item['AfinNom']
                    ];
                    break;
                }
            }
        }

        return $resultado;
    }

    public function pagarDeudaPorDocumento($documento,$cuenta,$importe){
        $infinitaService = new InfinitaService();
        $res = (object)$infinitaService->PagoTarjeta($documento,$cuenta,$importe);
        $data = (object) $res->data;

        if( (int)($data->MovCajNro) > 0)
        {
            return (object) [
                'success'=>true,
                'message'=>$data->Retorno,
                'recibo'=>$data->MovCajNro
            ];
        }

        return (object) [
            'success'=>false,
            'message'=>$data->Retorno,
            'recibo'=>null
        ];

    }
}
