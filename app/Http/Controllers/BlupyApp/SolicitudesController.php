<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\SolicitudCredito;
use App\Services\InfinitaService;
use Aws\DynamoDb\NumberValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
        $user = $req->user();

        if(!$this->verificarSolicitud($user->cliente->id)){
            return response()->json(['success' => false, 'message' => 'Su solicitud ya ingresó. Debe esperar al menos 48 hs para hacer una nueva.'],403);
        }
        $validator = Validator::make($req->all(),trans('validation.solicitudes.solicitar'),trans('validation.solicitudes.solicitar.messages'));
        if($validator->fails()) return response()->json(['success'=>false,'messages'=>$validator->errors()->first() ], 400);


        $verificarSolicitudPendiente = SolicitudCredito::where('cliente_id',$user->cliente->id)->where('tipo',1)->where('estado_id',5)->latest()->first();
        if($verificarSolicitudPendiente) return response()->json(['success'=>false,'message'=>'Ya tiene una solicitud con contrato pendiente.'],400);


        try {
            $request = (array) $req->all();
            $clienteUpdated = Cliente::find($user->cliente->id);
            $clienteUpdated->update($request);
            $clienteUpdated['email'] = $user->email;

            $res = (object)$this->infinitaService->solicitudLineaDeCredito($clienteUpdated);
            $solicitudResultado = $res->data;

            if($this->ingresarSolicitud($user->cliente->id,$solicitudResultado)){

            }

            return response()->json(['success'=>true,'message'=>'Solicitud ingresada correctamente.']);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['success'=>false,'message'=>'Hubo un error con el servidor. Contacte con nosotros por favor.'],500);
        }
    }

    public function solicitarAmpliacion(Request $req){

    }

    public function solicitarAdicional(Request $req){

    }


    private function verificarSolicitud ($id) : bool{
        $verificarSolicitud = SolicitudCredito::where('cliente_id',$id)->where('tipo',1)->latest()->first();
        if($verificarSolicitud){
            $fechaCarbon = Carbon::parse($verificarSolicitud->created_at);
            $fechaActual = Carbon::now();
            $haPasado2Dias = $fechaActual->diffInDays($fechaCarbon) > 2;
            return $haPasado2Dias;
        }
        return true;
    }

    private function ingresarSolicitud(string $clienteId, array $resultado): bool{

        $resultadoInfinitaObject = (object) $resultado;
        $resultado = false;
        if(property_exists($resultadoInfinitaObject,'CliId')){
            if($resultadoInfinitaObject->CliId !== '0'){
                $codigoSolicitud = $resultadoInfinitaObject->SolId;
                $estadoId = 11;
                $estado = trim($resultadoInfinitaObject->SolEstado);
                if($estado == 'Contrato Pendiente'){
                    $estadoId = 5;
                    $resultado = true;
                }
                if($estado == 'Pend. Aprobación'){
                    $estadoId= 3;
                }
                SolicitudCredito::create([
                    'cliente_id'=>$clienteId,
                    'estado_id'=>$estadoId,
                    'estado'=>$estado,
                    'codigo'=>$codigoSolicitud,
                    'tipo'=>1,
                    'importe'=>0
                ]);
                return $resultado;
            }
        }
        return $resultado;
    }

}
