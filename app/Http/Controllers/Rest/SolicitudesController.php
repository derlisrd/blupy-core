<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Jobs\ActualizarSolicitudesJobs;
use App\Models\Cliente;
use App\Models\Informacion;
use App\Models\SolicitudCredito;
use App\Models\User;
use App\Services\PushExpoService;
use App\Services\WaService;
use App\Traits\SolicitudesInfinitaTraits;
use Illuminate\Http\Request;


class SolicitudesController extends Controller
{
    use SolicitudesInfinitaTraits;
    private $camposSolicitud;
    private $minutes;
    public function __construct()
    {
        $this->minutes = 30;
        $this->camposSolicitud = [
            'solicitud_creditos.id',
            'solicitud_creditos.codigo',
            'solicitud_creditos.estado',
            'solicitud_creditos.estado_id',
            'solicitud_creditos.created_at',
            'solicitud_creditos.updated_at',
            'solicitud_creditos.tipo',
            'solicitud_creditos.cliente_id',
            'u.name',
            'u.email',
            'u.id as uid',
            'c.cedula',
            'c.cliid',
            'c.celular',
            'c.foto_ci_frente',
            'c.foto_ci_dorso',
            'c.asofarma',
            'c.solicitud_credito',
            'c.funcionario',
            'c.solicitud_credito',
            'c.selfie',
            'c.salario',
            'c.empresa',
            'c.latitud_direccion',
            'c.longitud_direccion'
        ];
    }
    /*
    ==============================================================================================================
    APROBAR DESDE INFINITA
    ==============================================================================================================
    */
    public function aprobar(Request $req)
    {
        try {
            $codigo = $req->codigo;
            $solicitud = SolicitudCredito::where('codigo', $codigo)->where('estado_id', 5)->first();
            if (!$solicitud) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solicitud no existe o ya fue aprobada.'
                ], 404);
            }

            $res = $this->aprobarSolicitudInfinita($codigo);
            $user = User::where('cliente_id', $solicitud->cliente_id)->first();

            if ($res->success) {
                $solicitud->estado = 'Vigente';
                $solicitud->estado_id = 7;
                $solicitud->save();
                $notificacion = new PushExpoService();
                //$emailService = new EmailService();
                $to = $user->notitokens();
                $notificacion->send(
                    $to,
                    '¡Contrato Activo!',
                    'Su contrato está activo, su línea está lista para usarse.',
                    []
                );
                $numeroTelefonoWa = '595' . substr($user->cliente->celular, 1);
                (new WaService())->send($numeroTelefonoWa, "Tu contrato de BLUPY se ha activado, tienes hasta 30% de descuento en tu primera compra.");
                Cliente::where('id', $solicitud->cliente_id)->update(['digital' => 1]);
                $info = Informacion::where('user_id', $user->id)->where('codigo_info', 1)->first();
                $info->delete();
            }

            return response()->json([
                'success' => $res->success,
                'message' => $res->message,
                'results' => $solicitud
            ], $res->status);
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    /*
    ==============================================================================================================
    ACTUALIZAR SOLICITUD DESDE INFINITA
    ==============================================================================================================
    */

    public function actualizarSolicitudes(Request $req)
    {

        try {

            $pendientes = SolicitudCredito::where('estado_id', 5)->pluck('codigo')->toArray();
            ActualizarSolicitudesJobs::dispatch($pendientes)->onConnection('database');
            return response()->json([
                'success' => true,
                'message' => 'Actualizando solicitudes en segundo plano.'
            ]);
        } catch (\Throwable $th) {
            throw $th;
            return response()->json(['success' => false, 'message' => 'Error de servidor. BQS64'], 500);
        }
    }


    public function actualizarSolicitud(Request $request)
    {

        $codigo = $request->codigo;
        $solicitud = SolicitudCredito::where('codigo', $codigo)->first();
        if (!$solicitud) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitud no existe.'
            ], 404);
        }

        $res = $this->actualizarSolicitudInfinita($codigo);

        if ($res->success) {
            $solicitud->estado = $res->estado;
            $solicitud->estado_id = $res->id;
            $solicitud->save();
        }


        return response()->json([
            'success' => true,
            'message' => 'Actualizada',
            'results' => $solicitud
        ]);
    }

    /*
    ==============================================================================================================
    SOLICITUDES DEL MES, POR DIA Y SEMANA
    ==============================================================================================================
    */
    public function index(Request $request)
    {
        try {
            $desde = ($request->desde ?? date('Y-m-01')) . ' 00:00:00';
            $hasta = ($request->hasta ?? date('Y-m-t')) . ' 23:59:59';

            $data = SolicitudCredito::orderBy('solicitud_creditos.id', 'desc')
                ->join('clientes as c', 'c.id', '=', 'solicitud_creditos.cliente_id')
                ->join('users as u', 'u.cliente_id', '=', 'c.id')
                ->where('solicitud_creditos.tipo', '>', 0)
                ->whereBetween('solicitud_creditos.created_at', [$desde, $hasta])
                ->select($this->camposSolicitud);

            return response()->json([
                'success' => true,
                'total' => $data->count(),
                'results' => $data->get()
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /*
    ==============================================================================================================
    BUSCAR
    ==============================================================================================================
    */
    public function buscar(Request $request)
    {

        $buscar = trim($request->q ?? '');


        if(empty($buscar)) {
            return response()->json([
                'success' => true,
                'total' => 0,
                'results' => []
            ]);
        }

        $query = SolicitudCredito::join('clientes as c', 'c.id', '=', 'solicitud_creditos.cliente_id')
        ->join('users as u', 'u.cliente_id', '=', 'c.id')
        ->where('solicitud_creditos.tipo', '>', 0)
        ->where('c.cedula', 'like', '%' . $buscar . '%')
        ->orderBy('solicitud_creditos.id', 'desc')
        ->select($this->camposSolicitud);

        $total = $query->count();
        $resultados = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Solicitudes',
            'total' => $total,
            'results' => $resultados
        ]);
    }
    /*
    ==============================================================================================================
    FILTROS
    ==============================================================================================================
    */

    public function filtros(Request $request)
    {

        $estado_id = $request->estado_id;
        $desde = ($request->desde ?? date('Y-m-01')) . ' 00:00:00';
        $hasta = ($request->hasta ?? date('Y-m-t')) . ' 23:59:59';
        $asofarma = ($request->asofarma) ?? '0';
        $funcionario = ($request->funcionario) ?? '0';

        if (!$estado_id) {
            $results = SolicitudCredito::where('solicitud_creditos.tipo', '>', 0)
                ->whereBetween('solicitud_creditos.created_at', [$desde, $hasta])
                ->join('clientes as c', 'c.id', '=', 'solicitud_creditos.cliente_id')
                ->join('users as u', 'u.cliente_id', '=', 'c.id')
                ->select($this->camposSolicitud);
        }
        if ($estado_id) {
            $results = SolicitudCredito::where('solicitud_creditos.tipo', '>', 0)
                ->where('estado_id', $estado_id)
                ->whereBetween('solicitud_creditos.created_at', [$desde, $hasta])
                ->join('clientes as c', 'c.id', '=', 'solicitud_creditos.cliente_id')
                ->join('users as u', 'u.cliente_id', '=', 'c.id')
                ->select($this->camposSolicitud);
        }

        return response()->json([
            'tipo' => $request->tipo,
            'success' => true,
            'total' => $results->count(),
            'results' => $results->get()
        ]);
    }


}
