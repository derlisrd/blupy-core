<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Services\InfinitaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SolicitudesController extends Controller
{
    private $infinitaService;

    public function __construct()
    {
        $this->infinitaService = new InfinitaService();
    }

    public function solicitudes(Request $req){
        $validator = Validator::make($req->all(),trans('validation.solicitudes.listar'),trans('validation.solicitudes.listar.messages'));
        if($validator->fails()) return response()->json(['success'=>false,'messages'=>$validator->errors()->first() ], 400);
        $results = [];
        $user = $req->user();
        $res = (object)$this->infinitaService->ListarSolicitudes($user->cliente->cedula,$req->fechaDesde,$req->fechaHasta);
        $solicitudes = (object)$res->data;
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
        return response()->json([
            'success'=>true,
            'results'=>$results
        ]);
    }

    public function contratoPendiente(Request $req){

    }

    public function solicitarCredito(Request $req){

    }

    public function solicitarAmpliacion(Request $req){

    }

    public function solicitarAdicional(Request $req){

    }

}
