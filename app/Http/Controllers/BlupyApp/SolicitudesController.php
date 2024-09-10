<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Adicional;
use App\Models\Barrio;
use App\Models\Ciudad;
use App\Models\Cliente;
use App\Models\Departamento;
use App\Models\SolicitudCredito;
use App\Services\SupabaseService;
use App\Traits\RegisterTraits;
use App\Traits\SolicitudesInfinita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SolicitudesController extends Controller
{
    use RegisterTraits;
    use SolicitudesInfinita;


    public function solicitudes(Request $req){
        $validator = Validator::make($req->all(),trans('validation.solicitudes.listar'),trans('validation.solicitudes.listar.messages'));
        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

        $user = $req->user();

        $desde = isset($req->fechaDesde) ? $req->fechaDesde : Carbon::now()->startOfMonth()->format('Y-m-d');
        $hasta = isset($req->fechaHasta) ? $req->fechaHasta : Carbon::now()->format('Y-m-d');

        $results = $this->listaSolicitudes($user->cliente->cedula,$desde,$hasta);
        // $results = SolicitudCredito::where([
        //     ['cliente_id','=',$user->cliente->id],
        //     ['tipo','>',0]
        // ])
        // ->select('id','estado','codigo','created_at as fecha','tipo')
        // ->get();
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
        {
            SupabaseService::LOG('Error en solicitud','Error en validacion');
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);
        }


        $verificarSolicitudPendiente = SolicitudCredito::where('cliente_id',$user->cliente->id)->where('tipo',1)->where('estado_id',5)->latest()->first();
        if($verificarSolicitudPendiente)
        {
            SupabaseService::LOG('Error en solicitud','Tiene una solicitud pendiente');
            return response()->json(['success'=>false,'message'=>'Ya tiene una solicitud con contrato pendiente.'],400);
        }


        try {
            $request = (array) $req->all();
            $clienteUpdated = Cliente::find($user->cliente->id);
            $clienteUpdated->update($request);
            $clienteUpdated['email'] = $user->email;

            $departamento = Departamento::find($req->departamento_id);
            $ciudad = Ciudad::find($req->ciudad_id);
            $barrio = Barrio::find($req->barrio_id);

            $departamento_empresa = Departamento::find($req->empresa_departamento_id);
            $ciudad_empresa = Ciudad::find($req->empresa_ciudad_id);

            $datosAenviar = $clienteUpdated;

            $datosAenviar['departamento_id'] = $departamento->codigo;
            $datosAenviar['ciudad_id'] = $ciudad->codigo;
            $datosAenviar['barrio_id'] = $barrio->codigo;

            $datosAenviar['empresa_departamento_id'] = $departamento_empresa->codigo;
            $datosAenviar['empresa_ciudad_id'] = $ciudad_empresa->codigo;



            $solicitud = $this->ingresarSolicitudInfinita($datosAenviar);
            if(!$solicitud->success){
                SupabaseService::LOG('Error en solicitud infinita',$solicitud);
                return response()->json(['success'=>false,'message'=>$solicitud->message],400);
            }


            SolicitudCredito::create([
                'cliente_id'=>$user->cliente->id,
                'estado_id'=>$solicitud->id,
                'estado'=>$solicitud->estado,
                'codigo'=>$solicitud->codigo,
                'tipo'=>1,
                'importe'=>0
            ]);
            $results = [
                'estado_id'=>$solicitud->id,
                'estado'=>$solicitud->estado,
                'codigo'=>$solicitud->codigo
            ];
            return response()->json([
                'success'=>true,
                'results'=>$results,
                'message'=>'Solicitud ingresada correctamente.']);

        } catch (\Throwable $th) {
            Log::error($th);
            SupabaseService::LOG($th->getMessage(),$th);
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
            $lineaSolicitada = $req->lineaSolicitada;
            $nroCuenta = $req->numeroCuenta;
            $fotoIngreso = $req->fotoIngreso;
            $fotoAnde = $req->fotoAnde;
            $ampliacion = $this->ampliacionEnInfinita($cliente,$lineaSolicitada,$nroCuenta,$fotoIngreso,$fotoAnde);

            if(!$ampliacion->success){
                return response()->json(['success'=>false,'message'=>$ampliacion->message],400);
            }


            SolicitudCredito::create([
                'cliente_id' => $cliente->id,
                'codigo' => $ampliacion->codigo,
                'estado' => $ampliacion->estado,
                'estado_id'=> 3,
                'importe'=>$lineaSolicitada,
                'tipo' => 3
            ]);
            return response()->json(['success'=>true,'message'=>'La ampliación de la línea ha ingresado con éxito.']);

        } catch (\Throwable $th) {
            SupabaseService::LOG('core_ampliacion_193',$th->getMessage());
            return response()->json(['success'=>false,'message'=>'Error de servidor']);
        }
    }





    /******************************************************
     * ADICIONAL AGREGAR adiciona adi adicional solicitar adicional
    *******************************************************/

    public function agregarAdicional(Request $req){
        try {
            $validator = Validator::make($req->all(),trans('validation.solicitudes.adicional'),trans('validation.solicitudes.adicional.messages'));
        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

        $adicional = Adicional::where('cedula',$req->cedula)->first();

        if($adicional)
            return response()->json(['success'=>false,'message'=>'Adicional ya existe en la base de datos'], 400);


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
            SupabaseService::LOG('error_adicional',$th->getMessage());
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



}
