<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\FarmaService;
use App\Services\InfinitaService;
use App\Services\SupabaseService;
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
        $cedula = $user->cliente->cedula;
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
                    //if($alianza['frpaCodigo'] === 129 ){
                        array_push($alianzas,[
                            'codigo'=>$alianza['codigoAdicional'],
                            'nombre'=> $alianza['alianza'],
                            'descripcion'=> $alianza['alianza'],
                            'formaPagoCodigo'=> $alianza['frpaCodigo'],
                            'formaPago'=>$alianza['formaPago']
                        ]);
                    //}
                }
                //if(count($alianzas)>0 || $val['esFuncionario'] ==='S'){
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
                //}
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
                        //$horario = Carbon::parse($val['TcMovCFh'],'UTC');
                        //$horario->setTimezone('America/Asuncion');
                        $fecha = $date->format('Y-m-d');
                        //$hora = $horario->format('H:i:s');
                        $hora = '00:00:00';
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
            //SupabaseService::LOG('movimientos',$th);
            throw $th;
            Log::error($th);
        }
    }




    public function extracto(Request $req){
        try {
            $validator = Validator::make($req->all(),trans('validation.extracto'),trans('validation.extracto.messages'));
            if($validator->fails())
                return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

            $fechaActual = Carbon::now();
            $periodo = isset($req->periodo) ? $req->periodo : $fechaActual->format('m-Y');

            $res = (object)$this->infinitaService->extractoCerrado($req->cuenta,1,$periodo);
            $resultado = (object) $res->data;
            if($resultado->Retorno == 'Extracto no encontrado.'){
                return response()->json(['success'=>false,'message'=>'Extracto no disponible','results'=>null],404);
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

