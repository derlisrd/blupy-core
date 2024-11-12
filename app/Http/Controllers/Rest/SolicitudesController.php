<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Jobs\ActualizarSolicitudesJobs;
use App\Models\Cliente;
use App\Models\SolicitudCredito;
use App\Models\User;
use App\Traits\SolicitudesInfinitaTraits;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            'c.solicitud_credito'
        ];
    }
    /*
    ==============================================================================================================
    ACTUALIZAR SOLICITUD DESDE INFINITA
    ==============================================================================================================
    */

    public function actualizarSolicitudes(Request $req){

      try {

        $pendientes = SolicitudCredito::where('estado_id', 5)->pluck('codigo')->toArray();
        ActualizarSolicitudesJobs::dispatch($pendientes)->onConnection('database');
        return response()->json([
            'success'=>true,
            'message'=>'Actualizando solicitudes en segundo plano.'
        ]);
      } catch (\Throwable $th) {
        throw $th;
        return response()->json(['success'=>false,'message'=>'Error de servidor. BQS64'],500);
      }
    }


    public function actualizarSolicitud(Request $request){

        $codigo = $request->codigo;
        $solicitud = SolicitudCredito::where('codigo',$codigo)->first();
        if(!$solicitud){
            return response()->json([
                'success'=>false,
                'message'=>'Solicitud no existe.'
            ],404);
        }

        $res = $this->actualizarSolicitudInfinita($codigo);

        if($res->success){
            $solicitud->estado = $res->estado;
            $solicitud->estado_id = $res->id;
            $solicitud->save();
        }


        return response()->json([
            'success'=>true,
            'message'=>'Actualizada',
            'results'=>$solicitud
        ]);

    }

    /*
    ==============================================================================================================
    SOLICITUDES DEL MES, POR DIA Y SEMANA
    ==============================================================================================================
    */
    public function index(Request $request){
        try {
            $desde = ($request->desde ?? date('Y-m-01')).' 00:00:00';
            $hasta = ($request->hasta ?? date('Y-m-t')).' 23:59:59';

            $data = SolicitudCredito::orderBy('solicitud_creditos.id', 'desc')
                ->join('clientes as c','c.id', '=', 'solicitud_creditos.cliente_id')
                ->join('users as u','u.cliente_id','=','c.id')
                ->where('solicitud_creditos.tipo','>',0)
                ->whereBetween('solicitud_creditos.created_at',[$desde,$hasta])
                ->select($this->camposSolicitud);

            return response()->json([
                'success'=>true,
                'total'=>$data->count(),
                'results'=>$data->get()
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
    public function buscar(Request $request){

        $buscar = $request->buscar;

        $limite = 25;
        $page =  0;
        $soli = SolicitudCredito::orderBy('solicitud_creditos.id', 'desc')
        ->where('u.name', 'like', '%' . $buscar. '%')
        ->orWhere('c.cedula', 'like', '%' . $buscar . '%')
        ->join('clientes as c','c.id', '=', 'solicitud_creditos.cliente_id')
        ->join('users as u','u.cliente_id','=','c.id')
        ->offset($page * $limite )
        ->limit($limite)
        ->select($this->camposSolicitud)
        ->get();

        $total = SolicitudCredito::count();
        $pages = (int)($total / $limite) - 1;

        return response()->json([
            'success'=>true,
            'total'=>$total,
            'pages'=>$pages,
            'current'=>(int)$page,
            'limit'=> (int) $limite,
            'results'=>$soli
        ]);
    }
    /*
    ==============================================================================================================
    FILTROS
    ==============================================================================================================
    */

    public function filtros (Request $request){

        $estado_id = $request->estado_id;
        $desde = ($request->desde ?? date('Y-m-01')).' 00:00:00';
        $hasta = ($request->hasta ?? date('Y-m-t')).' 23:59:59';
        $asofarma = ($request->asofarma) ?? '0';
        $funcionario = ($request->funcionario) ?? '0';

        if(!$estado_id){
            $results = SolicitudCredito::where('solicitud_creditos.tipo','>',0)
            ->whereBetween('solicitud_creditos.created_at',[$desde,$hasta])
            ->join('clientes as c','c.id', '=', 'solicitud_creditos.cliente_id')
            ->join('users as u','u.cliente_id','=','c.id')
            ->select($this->camposSolicitud);
        }
        if($estado_id){
            $results = SolicitudCredito::where('solicitud_creditos.tipo','>',0)
            ->where('estado_id',$estado_id)
            ->whereBetween('solicitud_creditos.created_at',[$desde,$hasta])
            ->join('clientes as c','c.id', '=', 'solicitud_creditos.cliente_id')
            ->join('users as u','u.cliente_id','=','c.id')
            ->select($this->camposSolicitud);
        }

        return response()->json([
            'tipo'=>$request->tipo,
            'success'=>true,
            'total'=>$results->count(),
            'results'=>$results->get()
        ]);
    }

    /*
    ==============================================================================================================
    SOLICITUDES Y REGISTROS TOTALES DEL MES, POR DIA Y SEMANA
    ==============================================================================================================
    */
    public function totales (Request $request){
        $inicioMes = $request->input('desde') ?? Carbon::now()->startOfMonth()->format('Y-m-d H:i:s');
        $finMes = $request->input('hasta') ?? Carbon::now()->endOfDay()->format('Y-m-d');

        $ayer = Carbon::yesterday()->format('Y-m-d');
        $fechaInicioMes =  $inicioMes;
        $hoy = Carbon::now()->format('Y-m-d');
        $primeraHora = '00:00:00';
        $ultimaHora = '23:59:59';

        $fechaHace60Dias = Carbon::now()->subDays(60)->format('Y-m-d');

        $fechaCarbon = Carbon::parse($inicioMes);
        $yearDeMes = $fechaCarbon->year;

        $lunes = Carbon::now()->startOfWeek()->format('Y-m-d H:i:s');
        $domingo = Carbon::now()->endOfWeek()->format('Y-m-d');

        $registros = Cliente::count();



        $funcionarios = Cliente::where('funcionario',1)->where('asofarma',0)->count();
        $externos = Cliente::where('funcionario',0)->where('asofarma',0)->count();
        $asociaciones = Cliente::where('funcionario',0)->where('asofarma',1)->count();

        $registrosFuncionarioMes = Cliente::whereBetween('created_at',[$fechaInicioMes,$finMes ])->where('funcionario',1)->where('asofarma',0)->count();
        $registrosAsoMes = Cliente::whereBetween('created_at',[$fechaInicioMes,$finMes ])->where('funcionario',0)->where('asofarma',1)->count();
        $registrosDigitalMes = Cliente::whereBetween('created_at',[$fechaInicioMes,$finMes  ])->where('funcionario',0)->where('asofarma',0)->count();
        $registrosDelMes = Cliente::whereBetween('created_at',[$fechaInicioMes,$finMes])->count();
        $registrosSemana = Cliente::whereBetween('created_at',[$lunes,$domingo])->count();
        $registrosHoy = Cliente::whereBetween('created_at',[$hoy.' 00:00:00',$hoy . ' 23:59:59'])->count();
        $registrosAyer = Cliente::whereBetween('created_at',[$ayer.$primeraHora, $ayer . $ultimaHora])->count();
        $registroDelAnio = Cliente::whereYear('created_at', $yearDeMes)->count();

        $solicitudesRechazadas = SolicitudCredito::where('tipo',1)->where('estado_id',11)->count();
        $rechazadosHoy = SolicitudCredito::whereBetween('created_at',[$hoy.' 00:00:00',$hoy . ' 23:59:59'])->where('tipo',1)->where('estado_id',11)->count();
        $rechazadosSemana = SolicitudCredito::whereBetween('created_at',[$lunes,$domingo])->where('tipo',1)->where('estado_id',11)->count();
        $rechazadosMes = SolicitudCredito::whereBetween('created_at',[$fechaInicioMes,$finMes])->where('tipo',1)->where('estado_id',11)->count();
        $rechazadosDelAnio = SolicitudCredito::whereYear('created_at', $yearDeMes)->where('tipo',1)->where('estado_id',11)->count();

        $solicitudesFuncionarios = Cliente::where('s.tipo',1)->where('clientes.funcionario',1)->join('solicitud_creditos as s','clientes.id','=','s.cliente_id')->count();
        $solicitudesFuncionariosVigentes = Cliente::where('s.tipo',1)->where('s.estado_id',7)->where('clientes.funcionario',1)->join('solicitud_creditos as s','clientes.id','=','s.cliente_id')->count();
        $solicitudesAsociaciones = Cliente::where('s.tipo',1)->where('clientes.asofarma',1)->join('solicitud_creditos as s','clientes.id','=','s.cliente_id')->count();
        $solicitudesAsociacionesVigentes = Cliente::where('s.tipo',1)->where('s.estado_id',7)->where('clientes.asofarma',1)->join('solicitud_creditos as s','clientes.id','=','s.cliente_id')->count();

        $solicitudesTotales = SolicitudCredito::where('tipo',1)->where('estado_id','<>',null)->count();
        $solicitudesHoy = SolicitudCredito::whereBetween('created_at',[$hoy.' 00:00:00',$hoy . ' 23:59:59'])->where('tipo',1)->count();
        $solicitudesAyer = SolicitudCredito::whereBetween('created_at',[$ayer.$primeraHora, $ayer . $ultimaHora])->where('tipo',1)->count();
        $solicitudesSemana = SolicitudCredito::whereBetween('created_at',[$lunes,$domingo])->where('tipo',1)->count();
        $solicitudesMes = SolicitudCredito::where('tipo',1)->whereBetween('created_at',[$fechaInicioMes,$finMes])->count();

        $solicitudesPendientes = SolicitudCredito::whereBetween('created_at',[$hoy.' 00:00:00',$fechaHace60Dias . ' 23:59:59'])->where('tipo',1)->where('estado_id',5)->count();
        $pendientesHoy = SolicitudCredito::whereBetween('created_at',[$hoy.' 00:00:00',$hoy . ' 23:59:59'])->where('tipo',1)->where('estado_id',5)->count();
        $pendientesSemana = SolicitudCredito::whereBetween('created_at',[$lunes,$domingo])->where('tipo',1)->where('estado_id',5)->count();
        $pendientesMes = SolicitudCredito::where('tipo',1)->whereBetween('created_at',[$fechaInicioMes,$finMes])->where('estado_id',5)->count();


        $solicitudVigentes = SolicitudCredito::where('tipo',1)->where('estado_id',7)->count();

        $vigentesHoy = SolicitudCredito::whereBetween('updated_at',[$hoy.' 00:00:00',$hoy . ' 23:59:59'])->where('tipo',1)->where('estado_id',7)->count();
        $vigentesSemana = SolicitudCredito::whereBetween('updated_at',[$lunes,$domingo])->where('tipo',1)->where('estado_id',7)->count();
        $vigentesMes = SolicitudCredito::whereBetween('updated_at',[$fechaInicioMes,$finMes])->where('tipo',1)->where('estado_id',7)->count();
        $vigentesDelAnio = SolicitudCredito::whereYear('created_at', $yearDeMes)->where('tipo',1)->where('estado_id',7)->count();
        $porcentajeDeRechazo = number_format( ($solicitudesRechazadas  * 100 / $solicitudesTotales),2 );

        return response()->json([
            'success'=>true,
            'results'=>[
                'registrosTotales'=>$registros,
                'registrosAyer'=>$registrosAyer,
                'registrosHoy'=>$registrosHoy,
                'registrosSemana'=>$registrosSemana,
                'registrosMes'=>$registrosDelMes,
                'registrosMesFuncionarios'=>$registrosFuncionarioMes,
                'registrosMesAso'=>$registrosAsoMes,
                'registrosMesDigital'=>$registrosDigitalMes,

                'registroDelAnio' =>$registroDelAnio,

                'funcionarios'=>$funcionarios,
                'asociaciones'=>$asociaciones,
                'externos'=>$externos,

                'solicitudesPendientes'=>$solicitudesPendientes,
                'pendientesHoy'=>$pendientesHoy,
                'pendientesSemana'=>$pendientesSemana,
                'pendientesMes'=>$pendientesMes,

                'solicitudesVigentes'=>$solicitudVigentes,
                'vigentesHoy'=>$vigentesHoy,
                'vigentesSemana'=>$vigentesSemana,
                'vigentesMes'=>$vigentesMes,
                'vigentesDelAnio'=>$vigentesDelAnio,

                'solicitudesRechazadas'=>$solicitudesRechazadas,
                'rechazadosHoy'=>$rechazadosHoy,
                'rechazadosSemana'=>$rechazadosSemana,
                'rechazadosMes'=>$rechazadosMes,
                'rechazadosDelAnio'=>$rechazadosDelAnio,

                'porcentajeRechazo'=>$porcentajeDeRechazo,

                'solicitudesTotales'=>$solicitudesTotales,
                'solicitudesAyer'=>$solicitudesAyer,
                'solicitudesHoy'=>$solicitudesHoy,
                'solicitudesSemana'=>$solicitudesSemana,
                'solicitudesMes'=>$solicitudesMes,

                'solicitudesFuncionarios'=>$solicitudesFuncionarios,
                'solicitudesFuncionariosVigentes'=>$solicitudesFuncionariosVigentes,
                'solicitudesAsociaciones'=>$solicitudesAsociaciones,
                'solicitudesAsociacionesVigentes'=>$solicitudesAsociacionesVigentes,

            ]
        ]);
    }
}
