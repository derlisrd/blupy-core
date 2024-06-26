<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Services\FarmaService;
use App\Services\InfinitaService;
use Illuminate\Http\Request;

class CuentasController extends Controller
{
    private $infinitaService;
    private $farmaService;

    public function __construct()
    {
        $this->infinitaService = new InfinitaService();
        $this->farmaService = new FarmaService();
    }

    public function tarjetas(Request $req){
        $results = [];
        $user = $req->user();

        $resInfinita = (object) $this->infinitaService->ListarTarjetasPorDoc($user->cliente->cedula);
        $infinita = (object)$resInfinita->data;
        if(property_exists( $infinita,'Tarjetas')){
            foreach ($infinita->Tarjetas as $val) {
                array_push($results, [
                    'descripcion'=>'Blupy crédito digital',
                    'otorgadoPor'=>'Mi crédito S.A.',
                    'cuenta' => $val['MaeCtaId'],
                    'linea' => (int)$val['MTLinea'],
                    'deuda' => (int) $val['MTSaldo'],
                    'disponible' => (int) $val['MTLinea'] - (int) $val['MTSaldo']
                ]);
            }
        }

        $resFarma = (object)$this->farmaService->cliente($user->cliente->cedula);
        $farma = (object) $resFarma->data;

        if(property_exists( $farma,'result')){
            foreach ($farma->result as $val) {
                array_push($results, [
                    'descripcion'=>'Blupy crédito 1 día',
                    'otorgadoPor'=>'Farma S.A.',
                    'cuenta' => null,
                    'linea' => $val['limiteCreditoTotal'],
                    'deuda' => $val['pendiente'],
                    'disponible' => $val['saldoDisponible']
                ]);
            }
        }

        return response()->json([
            'success'=>true,
            'results'=>$results
        ]);
    }
}

