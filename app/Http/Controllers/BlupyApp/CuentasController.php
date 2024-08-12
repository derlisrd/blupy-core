<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\FarmaService;
use App\Services\InfinitaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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

        $resFarma = (object)$this->farmaService->cliente($user->cliente->cedula);
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

        return response()->json([
            'success'=>true,
            'results'=>$results
        ]);
    }



    public function movimientos(Request $req){
        try {
            $validator = Validator::make($req->all(),trans('validation.movimientos'),trans('validation.movimientos.messages'));
            if($validator->fails())
                return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

            $results = [];
            $user = $req->user();
            $fechaActual = Carbon::now();
            $periodo = isset($req->periodo) ? $req->periodo : $fechaActual->format('m-Y');
            if(isset($req->cuenta)){
                //infinita
                $resInfinita = (object) $this->infinitaService->movimientosPorFecha($req->cuenta,$periodo);
                $infinita = (object) $resInfinita->data;
                if(property_exists($infinita,'Tarj')){
                    foreach($infinita->Tarj['Mov'] as $val){
                        $date = Carbon::parse($val['TcMovFec']);
                        $fecha = $date->format('Y-m-d');
                        $hora = $date->format('H:i:s');
                        array_push($results,[
                            'comercio'=>$val['TcComNom'],
                            'descripcion'=>$val['MvDes'],
                            'detalles'=> $val['TcMovDes'],
                            'fecha'=>$fecha,
                            'hora'=>$hora,
                            'monto'=>(int) $val['TcMovImp']
                        ]);
                    }
                }
            }
            if(!isset($req->cuenta) || $req->cuenta == null || $req->cuenta == '0'){
                //farma
                $resFarma = (object) $this->farmaService->movimientos($user->cliente->cedula,$periodo);
                $farma = (object) $resFarma->data;
                if(property_exists($farma,'result')){
                    foreach($farma->result['movimientos'] as $val){
                        $date = Carbon::parse($val['evenFecha'],'UTC');
                        $date->setTimezone('America/Asuncion');
                        $fecha = $date->format('Y-m-d');
                        $hora = $date->format('H:i:s');
                        array_push($results,[
                            'comercio'=>'Farma S.A.',
                            'descripcion'=>$val['ticoDescripcion'],
                            'detalles'=> $val['ticoCodigo'].' '.$val['evenNumero'],
                            'fecha'=>$fecha,
                            'hora'=>$hora,
                            'monto'=>$val['monto']
                        ]);
                    }
                }
            }

            return response()->json(['success'=>true,'results'=>$results]);

        } catch (\Throwable $th) {
            Log::error($th);
            throw $th;
        }
    }




    public function extracto(Request $req){
        try {
            $validator = Validator::make($req->only('periodo'),['periodo'=>'required'],['periodo.required'=>'El periodo es requerido (MM-AAAA).']);
            if($validator->fails())
                return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

            $res = (object)$this->infinitaService->extractoCerrado($req->cuenta,1,$req->periodo);
            $resultado = (object) $res->data;
            if($resultado->Retorno == 'Extracto no encontrado.'){
                return response()->json(['success'=>false,'message'=>'Extracto no disponible'],404);
            }
            return response()->json([
                'success'=>true,
                'message'=>'Extracto disponible',
                'results'=>[
                    'url'=>env('BASE_EXTRACTO') . $resultado->Url
                ]
            ]);
        } catch (\Throwable $th) {
           return response()->json(['success'=>false,'message'=>'Error de servidor'],500);
        }
    }

    public function misDispositivos(Request $req){
        $user = $req->user();
        return response()->json(['success'=>true,'results'=>$user->devices]);
    }
    public function eliminarDispositivo(Request $req){
        $device = Device::findOrFail($req->id);
        $device->delete();
        $user = $req->user();
        return response()->json(['success'=>true, 'message' => 'Dispositivo eliminado con éxito', 'results'=>$user->devices]);
    }
}

