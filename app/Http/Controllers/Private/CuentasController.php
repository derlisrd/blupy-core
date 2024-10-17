<?php

namespace App\Http\Controllers\Private;

use App\Http\Controllers\Controller;
use App\Services\FarmaService;
use App\Services\InfinitaService;


class CuentasController extends Controller
{
    private $infinitaService;
    private $farmaService;

    public function __construct()
    {
        $this->infinitaService = new InfinitaService();
        $this->farmaService = new FarmaService();
    }

    public function tarjetas(string $cedula){
        $results = [];
        $resInfinita = (object) $this->infinitaService->ListarTarjetasPorDoc($cedula);
        $infinita = (object)$resInfinita->data;
        if(property_exists( $infinita,'Tarjetas')){
            foreach ($infinita->Tarjetas as $val) {
                array_push($results, [
                    'id'=>2,
                    'descripcion'=>'Blupy crédito digital',
                    'otorgadoPor'=>'Mi crédito S.A.',
                    'tipo'=>1,
                    'bloqueo'=> $val['MTBloq'] === "" ? false : true,
                    'condicion'=>'Contado',
                    'condicionVenta'=>1,
                    'cuenta' => $val['MaeCtaId'],
                    'numeroTarjeta'=>$val['MTNume'],
                    'linea' => (int)$val['MTLinea'],
                    'pagoMinimo'=> (int) $val['MCPagMin'],
                    'deuda' => (int) $val['MTSaldo'],
                    'disponible' => (int) $val['MTLinea'] - (int) $val['MTSaldo'],
                    'alianzas' => []
                ]);
            }
        }

        $resFarma = (object)$this->farmaService->cliente($cedula);
        $farma = (object) $resFarma->data;

        if(property_exists( $farma,'result')){
            foreach ($farma->result as $val) {
                $alianzas = [];
                foreach($val['alianzas'] as $alianza){
                    if($alianza['frpaCodigo'] === 129 ){
                        array_push($alianzas,[
                            'codigo'=>$alianza['codigoAdicional'],
                            'nombre'=> $alianza['alianza'],
                            'descripcion'=> $alianza['alianza'],
                            'formaPagoCodigo'=> $alianza['frpaCodigo'],
                            'formaPago'=>$alianza['formaPago']
                        ]);
                    }
                }

                    array_push($results, [
                        'id'=>1,
                        'descripcion'=>'Blupy crédito 1 día',
                        'otorgadoPor'=>'Farma S.A.',
                        'tipo'=>0,
                        'condicion'=>'credito',
                        'condicionVenta'=>2,
                        'cuenta' => null,
                        'bloqueo'=> false,
                        'numeroTarjeta'=>null,
                        'linea' => $val['limiteCreditoTotal'],
                        'pagoMinimo'=> null,
                        'deuda' => $val['pendiente'],
                        'disponible' => $val['saldoDisponible'],
                        'alianzas' => $alianzas
                    ]);
                }

        }

        return $results;
    }
}
