<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Adicional;
use App\Models\Barrio;
use App\Models\Ciudad;
use App\Models\Departamento;
use App\Models\Informacion;
use App\Models\Notificacion;
use App\Models\SolicitudCredito;
use App\Services\PushExpoService;
use App\Services\SupabaseService;
use App\Traits\Helpers;
use App\Traits\RegisterTraits;
use App\Traits\SolicitudesInfinitaTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Private\CuentasController as CuentasPrivate;
use App\Jobs\SolicitudAprobadaJob;
use App\Models\Adjunto;
use App\Services\InfinitaService;
use App\Services\WaService;

class SolicitudesController extends Controller
{
    use RegisterTraits, SolicitudesInfinitaTraits, Helpers;

    /**
     * LISTA DE SOLICITUDES
     */
    public function solicitudes(Request $req)
    {
        $validator = Validator::make($req->all(), trans('validation.solicitudes.listar'), trans('validation.solicitudes.listar.messages'));
        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        $user = $req->user();

        $desde = isset($req->fechaDesde) ? $req->fechaDesde : Carbon::now()->startOfMonth()->format('Y-m-d');
        $hasta = isset($req->fechaHasta) ? $req->fechaHasta : Carbon::now()->format('Y-m-d');

        $results = $this->listaSolicitudes($user->cliente->cedula, $desde, $hasta);
        // $results = SolicitudCredito::where([
        //     ['cliente_id','=',$user->cliente->id],
        //     ['tipo','>',0]
        // ])
        // ->select('id','estado','codigo','created_at as fecha','tipo')
        // ->get();
        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    }


    public function verificarEstadoSolicitud(Request $req)
    {
        $user = $req->user();
        $cliente = $user->cliente;
        $solicitudes = SolicitudCredito::where('cliente_id', $cliente->id)->where('tipo', 1)->latest()->first();

        if ($solicitudes) {

            if ($solicitudes->estado_id == 7 || $solicitudes->estado_id == 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Su solicitud ya ingresó o yá esta activa. Verifique en sus solicitudes.'
                ], 400);
            }


            $fechaSolicitud = Carbon::parse($solicitudes->created_at);
            $fechaActual = Carbon::now();
            $cantidadDias = $fechaSolicitud->diffInDays($fechaActual);

            if ($cantidadDias < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Su solicitud ya ingresó. Debe esperar al menos 48 hs para hacer una nueva.'
                ], 403);
            }
        }
        return response()->json(['success' => true, 'message' => 'Puede ingresar una nueva solicitud']);
    }



    /**********************************************************************
     * solicitar credito sol linea credito
     ************************************************************************/
    public function solicitarCredito(Request $req)
    {
        $user = $req->user();
        $fechaSolicitud = $this->verificarSolicitud($user->cliente->id);

        if (!$fechaSolicitud) {
            return response()->json(['success' => false, 'message' => 'Su solicitud ya ingresó. Debe esperar al menos 48 hs para hacer una nueva.'], 403);
        }

        $validator = Validator::make($req->all(), trans('validation.solicitudes.solicitar'), trans('validation.solicitudes.solicitar.messages'));
        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);



        $verificarSolicitudPendiente = SolicitudCredito::where('cliente_id', $user->cliente->id)->where('tipo', 1)->where('estado_id', 5)->latest()->first();
        if ($verificarSolicitudPendiente)
            return response()->json(['success' => false, 'message' => 'Ya tiene una solicitud con contrato pendiente.'], 400);



        try {
            $cliente = $user->cliente;


            $departamento = Departamento::find($req->departamento_id);
            $ciudad = Ciudad::find($req->ciudad_id);
            $barrio = Barrio::find($req->barrio_id);

            $departamento_empresa = Departamento::find($req->empresa_departamento_id);
            $ciudad_empresa = Ciudad::find($req->empresa_ciudad_id);

            $datosAenviar = (object) [
                'cedula' => $cliente->cedula,
                'apellido_primero' => $cliente->apellido_primero,
                'apellido_segundo' => $cliente->apellido_segundo,
                'nombre_primero' => $cliente->nombre_primero,
                'nombre_segundo' => $cliente->nombre_segundo,
                'fecha_nacimiento' => $cliente->fecha_nacimiento,
                'celular' => $cliente->celular,
                'latitud_direccion' => $req->latitud_direccion,
                'longitud_direccion' => $req->longitud_direccion,
                'departamento_id' => $req->departamento_id,
                'ciudad_id' => $req->ciudad_id,
                'ciudad' => $req->ciudad,
                'barrio_id' => $req->barrio_id,
                'barrio' => $req->barrio,
                'calle' => $req->calle,
                'referencia_direccion' => $req->referencia_direccion,
                'profesion_id' => $req->profesion_id,
                'salario' => $req->salario,
                'antiguedad_laboral' => $req->antiguedad_laboral,
                'antiguedad_laboral_mes' => $req->antiguedad_laboral_mes,
                'empresa' => $req->empresa,
                'empresa_direccion' => $req->empresa_direccion,
                'empresa_telefono' => $req->empresa_telefono,
                'empresa_departamento_id' => $req->empresa_departamento_id,
                'empresa_ciudad_id' => $req->empresa_ciudad_id,
                'empresa_ciudad' => $req->empresa_ciudad,
                'tipo_empresa_id' => $req->tipo_empresa_id,
                'solicitud_credito' => 1,
                'direccion_completado' => 1,
                'email' => $user->email,
                'ciudad_id' => $ciudad->codigo,
                'departamento_id' => $departamento->codigo,
                'barrio_id' => $barrio->codigo,
                'empresa_ciudad_id' => $ciudad_empresa->codigo,
                'empresa_departamento_id' => $departamento_empresa->codigo,
            ];




            $imagenSelfie = $this->guardarSelfieImagenBase64($req->selfie, $cliente->cedula);



            if ($imagenSelfie == null)
                return response()->json(['success' => false, 'message' => 'No se pudo guardar la selfie.'], 400);

            $solicitud = $this->ingresarSolicitudInfinita($datosAenviar);
            if (!$solicitud->success)
                return response()->json(['success' => false, 'message' => $solicitud->message], 400);

            // enviar selfie
            $this->enviarSelfieInfinita($cliente->cedula, $req->selfie);


            $cliente->update([
                'latitud_direccion' => $req->latitud_direccion,
                'longitud_direccion' => $req->longitud_direccion,
                'departamento_id' => $req->departamento_id,
                'ciudad_id' => $req->ciudad_id,
                'ciudad' => $req->ciudad,
                'barrio_id' => $req->barrio_id,
                'barrio' => $req->barrio,
                'calle' => $req->calle,
                'referencia_direccion' => $req->referencia_direccion,
                'profesion_id' => $req->profesion_id,
                'salario' => $req->salario,
                'antiguedad_laboral' => $req->antiguedad_laboral,
                'antiguedad_laboral_mes' => $req->antiguedad_laboral_mes,
                'empresa' => $req->empresa,
                'empresa_direccion' => $req->empresa_direccion,
                'empresa_telefono' => $req->empresa_telefono,
                'empresa_departamento_id' => $req->empresa_departamento_id,
                'empresa_ciudad_id' => $req->empresa_ciudad_id,
                'empresa_ciudad' => $req->empresa_ciudad,
                'tipo_empresa_id' => $req->tipo_empresa_id,
                'solicitud_credito' => 1,
                'direccion_completado' => 1,
            ]);

            Adjunto::create([
                'cliente_id' => $cliente->id,
                'nombre' => $imagenSelfie,
                'tipo' => 'selfie',
                'path' => 'adjuntos',
                'url' => 'adjuntos/' . $imagenSelfie,
            ]);

            Notificacion::create([
                'user_id' => $user->id,
                'title' => 'Solicitud de crédito',
                'body' => $solicitud->estado
            ]);

            SolicitudCredito::create([
                'cliente_id' => $cliente->id,
                'estado_id' => $solicitud->id,
                'estado' => $solicitud->estado,
                'codigo' => $solicitud->codigo,
                'tipo' => 1,
                'importe' => 0
            ]);
            $titulo = '¡Solicitud de crédito!';
            $descripcion = 'Tu solicitud de crédito ha sido enviada. ¡Estamos procesando tu solicitud! 🥳';
            if ($solicitud->id === 5) {
                
                SolicitudAprobadaJob::dispatch($cliente->email,$cliente->celular);

                Informacion::create([
                    'user_id' => $user->id,
                    'codigo_info' => 1,
                    'title' => $titulo,
                    'description' => $descripcion,
                    'text' => $descripcion,
                    'active' => 1,
                    'leido' => 0,
                    'general' => 0,
                ]);
            }
            $devicesTokens = $user->devices;
            $expoService = new PushExpoService();

            $expoService->send(
                [$devicesTokens],
                $titulo,
                $descripcion,
                [
                    'info' => 'notificaciones',
                    'title' => $titulo,
                    'body' => $descripcion
                ]
            );
            $results = [
                'estado_id' => $solicitud->id,
                'estado' => $solicitud->estado,
                'codigo' => $solicitud->codigo
            ];
            return response()->json([
                'success' => true,
                'results' => $results,
                'message' => 'Solicitud ingresada correctamente.'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            SupabaseService::LOG($th->getMessage(), $th);
            return response()->json(['success' => false, 'message' => 'Hubo un error con el servidor. Contacte con nosotros por favor.'], 500);
        }
    }




    /**********************************************************************
     * AUMENTO ampliacion aumentar solicitud aum  solicitar aumentar
     ************************************************************************/
    public function solicitarAmpliacion(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), trans('validation.solicitudes.ampliacion'), trans('validation.solicitudes.ampliacion.messages'));
            if ($validator->fails())
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
            $user = $req->user();
            $cliente = $user->cliente;
            $cliente['email'] = $user['email'];
            $lineaSolicitada = $req->lineaSolicitada;
            $nroCuenta = $req->numeroCuenta;
            $fotoIngreso = $req->fotoIngreso;
            $fotoAnde = $req->fotoAnde;
            if ($cliente->created_at->diffInDays(Carbon::now()) < 90) {
                return response()->json(['success' => false, 'message' => 'No puede solicitar ampliación. Debe tener al menos 3 meses de antigüedad.'], 403);
            }

            $datosAenviar = $cliente;

            $departamento = Departamento::find($cliente->departamento_id);
            $ciudad = Ciudad::find($cliente->ciudad_id);
            $barrio = Barrio::find($cliente->barrio_id);

            $departamento_empresa = Departamento::find($cliente->empresa_departamento_id);
            $ciudad_empresa = Ciudad::find($cliente->empresa_ciudad_id);

            $datosAenviar['departamento_id'] = $departamento->codigo;
            $datosAenviar['ciudad_id'] = $ciudad->codigo;
            $datosAenviar['barrio_id'] = $barrio->codigo;

            $datosAenviar['empresa_departamento_id'] = $departamento_empresa->codigo;
            $datosAenviar['empresa_ciudad_id'] = $ciudad_empresa->codigo;

            $ingreso = preg_replace('#data:image/[^;]+;base64,#', '', $fotoIngreso);
            $ande = preg_replace('#data:image/[^;]+;base64,#', '', $fotoAnde);
            app(InfinitaService::class)->enviarComprobantes($cliente->cedula, $ingreso, $ande);

            $ampliacion = (object) $this->ampliacionEnInfinita($datosAenviar, $lineaSolicitada, $nroCuenta);

            if (!$ampliacion->success) {
                SupabaseService::LOG('core_ampliacion_178', $ampliacion);
                return response()->json(['success' => false, 'message' => $ampliacion->message], 400);
            }
            SolicitudCredito::create([
                'cliente_id' => $cliente->id,
                'codigo' => $ampliacion->codigo,
                'estado' => $ampliacion->estado,
                'estado_id' => 3,
                'importe' => $lineaSolicitada,
                'tipo' => 3
            ]);
            return response()->json(['success' => true, 'message' => 'La ampliación de la línea ha ingresado con éxito.']);
        } catch (\Throwable $th) {
            //Log::error($th);
            SupabaseService::LOG('core_ampliacion_194', $th->getMessage());

            return response()->json(['success' => false, 'message' => 'Error de servidor. Contacto con atención al cliente'],500);
        }
    }





    /******************************************************
     * ADICIONAL AGREGAR adiciona adi adicional solicitar adicional
     *******************************************************/

    public function agregarAdicional(Request $req)
    {
        try {

            $validator = Validator::make($req->all(), trans('validation.solicitudes.adicional'), trans('validation.solicitudes.adicional.messages'));
            if ($validator->fails())
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

            $adicional = Adicional::where('cedula', $req->cedula)->first();
            if ($adicional)
                return response()->json(['success' => false, 'message' => 'Adicional ya existe en la base de datos'], 400);


            $nombres = $this->separarNombres($req->nombres);
            $apellidos = $this->separarNombres($req->apellidos);

            $datoDelAdicional = [
                'cedula' => $req->cedula,
                'nombre1' => $nombres[0],
                'nombre2' => $nombres[1],
                'apellido1' => $apellidos[0],
                'apellido2' => $apellidos[1],
                'limite' => (int)$req->limite,
                'telefono' => $req->celular,
                'direccion' => $req->direccion
            ];

            $user = $req->user();
            $cliente = $user->cliente;
            $cliente['email'] = $user->email;

            $tarjetasConsultas = new CuentasPrivate();
            $tarjetas = $tarjetasConsultas->tarjetaBlupyDigital($cliente->cedula);
            if ($tarjetas === null)
                return response()->json(['success' => false, 'message' => 'Error tarjeta no encontrada'], 404);

            $tarjetaObject = (object) $tarjetas;

            if ((string)$req->cuenta !== $tarjetaObject->cuenta)
                return response()->json(['success' => false, 'message' => 'Error tarjeta no pertenece a cuenta'], 403);



            if ($tarjetaObject->linea < (int)$req->limite)
                return response()->json(['success' => false, 'message' => 'Error, limite excedido'], 403);

                $datosAenviar = $cliente;

                $departamento = Departamento::find($cliente->departamento_id);
                $ciudad = Ciudad::find($cliente->ciudad_id);
                $barrio = Barrio::find($cliente->barrio_id);
    
                $datosAenviar['departamento_id'] = $departamento->codigo;
                $datosAenviar['ciudad_id'] = $ciudad->codigo;
                $datosAenviar['barrio_id'] = $barrio->codigo;
    
                


            $infinitaAdicional = $this->adicionalEnInfinita($datosAenviar, $datoDelAdicional, $req->cuenta);

            if (! $infinitaAdicional->success) {
                return response()->json(['success' => false, 'message' => $infinitaAdicional->message], 400);
            }
            $res = $infinitaAdicional->results;

            Adicional::create([
                'cliente_id' => $cliente->id,
                'cedula' => $req->cedula,
                'nombres' => $req->nombres,
                'celular' => $req->celular,
                'apellidos' => $req->apellidos,
                'limite' => $req->limite,
                'direccion' => $req->direccion,
                'cuenta' => $req->cuenta
            ]);

            SolicitudCredito::create([
                'cliente_id' => $cliente->id,
                'codigo' => $res->solicitudId,
                'estado' => trim($res->solicitudEstado),
                'estado_id' => 3,
                'importe' => $req->limite,
                'tipo' => 2
            ]);
            return response()->json(['success' => true, 'message' => 'Adicional ingresado correctamente', 'tarjetas' => $tarjetas]);
        } catch (\Throwable $th) {
            SupabaseService::LOG('error_adicional', $th->getMessage());
            return response()->json(['success' => false, 'message' => 'Error de servidor'], 500);
        }
    }





    private function verificarSolicitud($id): bool
    {
        $verificarSolicitud = SolicitudCredito::where('cliente_id', $id)->where('tipo', 1)->latest()->first();

        if ($verificarSolicitud) {
            $fechaActual = Carbon::now();
            $fechaCarbon = Carbon::parse($verificarSolicitud->created_at);
            $diferenciaEnDias = $fechaCarbon->diffInDays($fechaActual);
            return $diferenciaEnDias >= 2;
        }
        return true;
    }
}
