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
                    'condicion'=>'Contado',
                    'cuenta' => $val['MaeCtaId'],
                    'linea' => (int)$val['MTLinea'],
                    'deuda' => (int) $val['MTSaldo'],
                    'disponible' => (int) $val['MTLinea'] - (int) $val['MTSaldo']
                ]);
            }
        }

        $resFarma = (object)$this->farmaService->cliente($cedula);
        $farma = (object) $resFarma->data;

        if(property_exists( $farma,'result')){
            foreach ($farma->result as $val) {
                array_push($results, [
                    'id'=>1,
                    'descripcion'=>'Blupy crédito 1 día',
                    'otorgadoPor'=>'Farma S.A.',
                    'tipo'=>0,
                    'condicion'=>'credito',
                    'cuenta' => null,
                    'linea' => $val['limiteCreditoTotal'],
                    'deuda' => $val['pendiente'],
                    'disponible' => $val['saldoDisponible']
                ]);
            }
        }

        return $results;
    }
}