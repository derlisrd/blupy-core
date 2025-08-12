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



    public function tarjetas(string $cedula, int $extranjero, string $codigo_farma, int $franquicia)
    {

        $results = [];

        // Manejo de errores para API Infinita
        try {
            $resInfinita = $this->infinitaService->ListarTarjetasPorDoc($cedula);
            $infinita = (object)$resInfinita['data'];

            if (isset($infinita->Tarjetas)) {
                foreach ($infinita->Tarjetas as $val) {
                    array_push($results, [
                        'id' => 2,
                        'descripcion' => 'Blupy Digital',
                        'otorgadoPor' => 'Mi crédito S.A.',
                        'tipo' => 1,
                        'emision' => $val['MTFEmi'],
                        'bloqueo' => $val['MTBloq'] === "" ? false : true,
                        'condicion' => 'Contado',
                        'condicionVenta' => 1,
                        'cuenta' => $val['MaeCtaId'],
                        'principal' => $val['MTTipo'] === 'P',
                        'adicional' => $val['MTTipo'] === 'A',
                        'numeroTarjeta' => $val['MTNume'],
                        'linea' => (int)$val['MTLinea'],
                        'pagoMinimo' => (int) $val['MCPagMin'],
                        'deuda' => (int) $val['MTSaldo'],
                        'disponible' => (int) $val['MTLinea'] - (int) $val['MTSaldo'],
                        'alianzas' => [],
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Log del error pero continúa la ejecución
            Log::error('Error al consultar API Infinita: ' . $e->getMessage(), [
                'cedula' => $cedula,
                'trace' => $e->getTraceAsString()
            ]);

            // Opcionalmente enviar notificación a Supabase
            SupabaseService::LOG('infinita_api_error', $e->getMessage());
        }
        // Manejo de errores para API Farma
        $resFarma = null;
        try {
            if ($extranjero == 1) {
                $resFarma = $this->farmaService->clientePorCodigo($codigo_farma);
            } else {
                $resFarma = $this->farmaService->cliente($cedula);
            }
        } catch (\Exception $e) {
            // Log del error pero continúa la ejecución
            Log::error('Error al consultar API Farma: ' . $e->getMessage(), [
                'cedula' => $cedula,
                'extranjero' => $extranjero,
                'codigo_farma' => $codigo_farma,
                'trace' => $e->getTraceAsString()
            ]);

            SupabaseService::LOG('farma_api_error', $e->getMessage());

            // Continúa con $resFarma = null
        }

        // El resto del código se mantiene igual
        if (!$resFarma) {
            return $results;
        }

        $farma = (object) $resFarma['data'];
        if (property_exists($farma, 'result')) {
            foreach ($farma->result as $val) {
                $alianzas = [];
                foreach ($val['alianzas'] as $alianza) {
                    if ($alianza['frpaCodigo'] > 126) {
                        array_push($alianzas, [
                            'codigo' => $alianza['codigoAdicional'],
                            'nombre' => $alianza['alianza'],
                            'descripcion' => $alianza['alianza'],
                            'formaPagoCodigo' => $alianza['frpaCodigo'],
                            'formaPago' => $alianza['formaPago']
                        ]);
                    }
                }
                if (count($alianzas) > 0 || $val['esFuncionario'] === 'S' || $franquicia === 1) {
                    array_push($results, [
                        'id' => 1,
                        'descripcion' => $val['esFuncionario'] === 'S' ? 'Blupy Farma' : 'Blupy Alianza',
                        'otorgadoPor' => $val['esFuncionario'] === 'S' ? 'Farma S.A.' : 'Farma por alianza',
                        'tipo' => 0,
                        'emision' => null,
                        'condicion' => 'credito',
                        'condicionVenta' => 2,
                        'cuenta' => null,
                        'bloqueo' => false,
                        'numeroTarjeta' => null,
                        'linea' => $val['limiteCreditoTotal'], //$val['clerLimiteCredito'] +  $val['clerLimiteCreditoAdic']
                        'pagoMinimo' => null,
                        'deuda' => $val['pendiente'],
                        'disponible' => $val['saldoDisponible'], // ($val['clerLimiteCredito'] +  $val['clerLimiteCreditoAdic']) - $val['pendiente'],
                        'alianzas' => $alianzas
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results,
            'message' => ''
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
            if(isset($req->cuenta) && $req->cuenta !== null && $req->numero_tarjeta !== null){
                //infinita
                $resInfinita = $this->infinitaService->movimientosPorFecha($req->cuenta,$periodo,$req->numero_tarjeta);
                $infinita = (object) $resInfinita['data'];
                if(property_exists($infinita,'Tarj')){
                    $movimientos = isset($infinita->Tarj['Mov']) ? $infinita->Tarj['Mov'] : [];
                    foreach($movimientos as $val){
                        $date = Carbon::parse($val['TcMovFec']);
                        $horario = Carbon::parse($val['TcMovCFh'],'UTC');
                        $horario->setTimezone('America/Asuncion');
                        $fecha = $date->format('Y-m-d');
                        $hora = $horario->format('H:i:s');
                        array_push($results,[
                            'comercio'=>$val['TcComNom'],
                            'descripcion'=>$val['MvDes'],
                            'detalles'=> $val['TcMovDes'],
                            'fecha'=>$fecha,
                            'hora'=>$hora,
                            'monto'=>(int) $val['TcMovImp'],
                            'numero'=>$val['TcMovNro'],
                        ]);
                    }
                }
            }
            if(!isset($req->cuenta) || $req->cuenta == null || $req->cuenta == '0'){
                //farma
                $resFarma = $this->farmaService->movimientos2($user->cliente->cedula,$periodo);
                $farma = (object) $resFarma['data'];
                if(property_exists($farma,'result')){
                    foreach($farma->result as $val){
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
                    /* foreach($farma->result['movimientos'] as $val){
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
                    } */
                }
            }

            return response()->json(['success'=>true,'results'=>$results]);

        } catch (\Throwable $th) {
            //SupabaseService::LOG('movimientos',$th);
            //throw $th;
            Log::error($th->getMessage());
            return response()->json(['success'=>false,'message'=>'Ocurrió un error inesperado','results'=>[]], 500);
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

