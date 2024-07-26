<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Adicional;
use App\Models\Cliente;
use App\Models\SolicitudCredito;
use App\Services\InfinitaService;
use App\Traits\RegisterTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SolicitudesController extends Controller
{
    use RegisterTraits;
    private $infinitaService;

    public function __construct()
    {
        $this->infinitaService = new InfinitaService();
    }

    public function solicitudes(Request $req){
        $validator = Validator::make($req->all(),trans('validation.solicitudes.listar'),trans('validation.solicitudes.listar.messages'));
        if($validator->fails()) return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);
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


    public function verificarEstadoSolicitud(Request $req){
        $user = $req->user();
        $cliente = $user->cliente;
        $solicitudes = SolicitudCredito::where('cliente_id',$cliente->id)->where('tipo',1)->latest()->first();

        if($solicitudes){
            $fechaCarbon = Carbon::parse($solicitudes->created_at);
            $fechaActual = Carbon::now();
            $haPasadoUnDia = $fechaActual->diffInDays($fechaCarbon) > 2;
            if(!$haPasadoUnDia){
                return response()->json([
                    'success' => false,
                    'message' => 'Su solicitud ya ingresó. Debe esperar al menos 48 hs para hacer una nueva.'
                ],403);
            }
        }
        return response()->json(['success'=>true, 'message'=>'Puede ingresar una nueva solicitud']);

    }

    public function contratoPendiente(Request $req){

    }


    /**********************************************************************
     * solicitar credito sol linea credito
    ************************************************************************/
    public function solicitarCredito(Request $req){
        $user = $req->user();

        if(!$this->verificarSolicitud($user->cliente->id)){
            return response()->json(['success' => false, 'message' => 'Su solicitud ya ingresó. Debe esperar al menos 48 hs para hacer una nueva.'],403);
        }
        $validator = Validator::make($req->all(),trans('validation.solicitudes.solicitar'),trans('validation.solicitudes.solicitar.messages'));
        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);


        $verificarSolicitudPendiente = SolicitudCredito::where('cliente_id',$user->cliente->id)->where('tipo',1)->where('estado_id',5)->latest()->first();
        if($verificarSolicitudPendiente)
            return response()->json(['success'=>false,'message'=>'Ya tiene una solicitud con contrato pendiente.'],400);


        try {
            $request = (array) $req->all();
            $clienteUpdated = Cliente::find($user->cliente->id);
            $clienteUpdated->update($request);
            $clienteUpdated['email'] = $user->email;

            $res = (object)$this->infinitaService->solicitudLineaDeCredito($clienteUpdated);
            $solicitudResultado = $res->data;

            $this->ingresarSolicitud($user->cliente->id,$solicitudResultado);

            return response()->json(['success'=>true,'message'=>'Solicitud ingresada correctamente.']);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['success'=>false,'message'=>'Hubo un error con el servidor. Contacte con nosotros por favor.'],500);
        }
    }




    /**********************************************************************
     * AUMENTO ampliacion aumentar solicitud aum adicional solicitar aumentar
    ************************************************************************/
    public function solicitarAmpliacion(Request $req){
        try {
            $validator = Validator::make($req->all(),trans('validation.solicitudes.ampliacion'),trans('validation.solicitudes.ampliacion.messages'));
            if($validator->fails())
                return response()->json(['success' => false,'message' => $validator->errors()->first()], 400);

            $user = $req->user();
            $cliente = $user->cliente;
            $cliente['email'] = $user['email'];

            $infinita = (object)$this->infinitaService->ampliacionCredito($cliente,$req->lineaSolicitada,$req->numeroCuenta);
            $res = (object) $infinita->data;
            Log::info($infinita->data);

            if($res->CliId == "0"){
                $message = property_exists($res,'Messages') ? $res->Messages[0]['Description'] : 'Error de servidor. ERROR_CLI';
                return response()->json(['success' => false,'message' => $message],400);
            }

            $ingreso = preg_replace('#data:image/[^;]+;base64,#', '', $req->fotoIngreso);
            $ande = preg_replace('#data:image/[^;]+;base64,#', '', $req->fotoAnde);
            $this->infinitaService->enviarComprobantes($cliente->cedula, $ingreso, $ande);

            SolicitudCredito::create([
                'cliente_id' => $cliente->id,
                'codigo' => $res->SolId,
                'estado' => trim($res->SolEstado),
                'estado_id'=> 3,
                'importe'=>$req->lineaSolicitada,
                'tipo' => 3
            ]);
            return response()->json(['success'=>true,'message'=>'La ampliación de la línea ha ingresado con éxito.']);

        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['success'=>false,'message'=>'Error de servidor']);
        }
    }



    /******************************************************
     * ADICIONAL adiciona adi adicional solicitar adicional
    *******************************************************/

    public function solicitarAdicional(Request $req){
        try {
            $validator = Validator::make($req->all(),trans('validation.solicitudes.adicional'),trans('validation.solicitudes.adicional.messages'));
        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

        $nombres = $this->separarNombres( $req->nombres );
        $apellidos = $this->separarNombres( $req->apellidos );

        $datoDelAdicional = [
            'cedula'=>$req->cedula,
            'nombre1'=>$nombres[0],
            'nombre2'=>$nombres[1],
            'apellido1'=>$apellidos[0],
            'apellido2'=>$apellidos[1],
            'limite'=>(int)$req->limite,
            'telefono'=>$req->celular,
            'direccion'=>$req->direccion
        ];

        $user = $req->user();
        $cliente = $user->cliente;
        $cliente['email'] = $user->email;

        $infinitaAmpliacion = $this->adicionalEnInfinita($cliente,$datoDelAdicional,$req->maectaid);

        if( ! $infinitaAmpliacion->success ){
            return response()->json(['success'=>false,'message'=>$infinitaAmpliacion->message],400);
        }
        $res = $infinitaAmpliacion->results;

        Adicional::create([
            'cliente_id'=>$cliente->id,
            'cedula'=>$req->cedula,
            'nombres'=>$req->nombres,
            'celular'=>$req->celular,
            'apellidos'=>$req->apellidos,
            'limite'=>$req->limite,
            'direccion'=>$req->direccion,
            'mae_cuenta_id'=>$req->maectaid
        ]);

        SolicitudCredito::create([
            'cliente_id' => $cliente->id,
            'codigo' => $res->SolId,
            'estado' => trim($res->SolEstado),
            'estado_id'=> 3,
            'importe'=>$req->limite,
            'tipo' => 2
        ]);
        return response()->json(['success'=>true,'message'=>'Adicional ingresado correctamente']);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['success'=>false,'message'=>'Error de servidor'],500);
        }
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

    private function adicionalEnInfinita($clientePrincipal,$datosDelAdicional,$cuentaPrincipal){
        $datos = [(object)$datosDelAdicional];

        $infinita = (object) $this->infinitaService->agregarAdicional($clientePrincipal,$datos,$cuentaPrincipal);
        $res = (object)$infinita->data;
        Log::info($infinita->data);
        if($res->CliId == "0"){
            $message = property_exists($res,'Messages') ? $res->Messages[0]['Description'] : 'Error de servidor. ERROR_CLI';
            return (object)['success'=>false, 'message'=>$message,'results'=>null];
        }
        return (object)[
            'success'=>true,
            'results'=> (object) [
            'solicitudId'=>$res->SolId,
            'solicitudEstado'=>$res->SolEstado
            ]
        ];
    }

}
