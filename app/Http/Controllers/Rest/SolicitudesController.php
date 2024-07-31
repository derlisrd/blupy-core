<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\SolicitudCredito;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SolicitudesController extends Controller
{

    /*
    ==============================================================================================================
    SOLICITUDES Y REGISTROS TOTALES DEL MES, POR DIA Y SEMANA
    ==============================================================================================================
    */
    public function totales (Request $request){


        $desde = $request->desde ?? Carbon::now()->startOfMonth()->format('Y-m-d H:i:s');
        $hasta = $request->hasta ?? Carbon::now()->format('Y-m-d');

        $ayer = Carbon::yesterday()->format('Y-m-d');
        $fechaInicioMes =  $desde;
        $hoy = $hasta;
        $fechaHoy = $hoy;
        $primeraHora = '00:00:00';
        $ultimaHora = '23:59:59';

        $lunes = Carbon::now()->startOfWeek()->format('Y-m-d H:i:s');
        $domingo = Carbon::now()->endOfWeek()->format('Y-m-d');

        $registros = Cliente::count();



        $funcionarios = Cliente::where('funcionario',1)->where('asofarma',0)->count();
        $externos = Cliente::where('funcionario',0)->where('asofarma',0)->count();
        $asociaciones = Cliente::where('funcionario',0)->where('asofarma',1)->count();

        $registrosDelMes = Cliente::whereBetween('created_at',[$fechaInicioMes,$fechaHoy])->count();
        $registrosSemana = Cliente::whereBetween('created_at',[$lunes,$domingo])->count();
        $registrosHoy = Cliente::whereBetween('created_at',[$hoy.' 00:00:00',$fechaHoy])->count();
        $registrosAyer = Cliente::whereBetween('created_at',[$ayer.$primeraHora, $ayer . $ultimaHora])->count();


        $solicitudesRechazadas = SolicitudCredito::where('tipo',1)->where('estado_id',11)->count();
        $rechazadosHoy = SolicitudCredito::whereBetween('created_at',[$hoy.' 00:00:00',$fechaHoy])->where('tipo',1)->where('estado_id',11)->count();
        $rechazadosSemana = SolicitudCredito::whereBetween('created_at',[$lunes,$domingo])->where('tipo',1)->where('estado_id',11)->count();
        $rechazadosMes = SolicitudCredito::whereBetween('created_at',[$fechaInicioMes,$fechaHoy])->where('tipo',1)->where('estado_id',11)->count();

        $solicitudesTotales = SolicitudCredito::where('tipo',1)->where('estado_id','<>',null)->count();
        $solicitudesHoy = SolicitudCredito::whereBetween('created_at',[$hoy.' 00:00:00',$fechaHoy])->where('tipo',1)->count();
        $solicitudesAyer = SolicitudCredito::whereBetween('created_at',[$ayer.$primeraHora, $ayer . $ultimaHora])->where('tipo',1)->count();
        $solicitudesSemana = SolicitudCredito::whereBetween('created_at',[$lunes,$domingo])->where('tipo',1)->count();
        $solicitudesMes = SolicitudCredito::where('tipo',1)->whereBetween('created_at',[$fechaInicioMes,$fechaHoy])->count();

        $solicitudesPendientes = SolicitudCredito::where('tipo',1)->where('estado_id',5)->count();
        $pendientesHoy = SolicitudCredito::whereBetween('created_at',[$hoy.' 00:00:00',$fechaHoy])->where('tipo',1)->where('estado_id',5)->count();
        $pendientesSemana = SolicitudCredito::whereBetween('created_at',[$lunes,$domingo])->where('tipo',1)->where('estado_id',5)->count();
        $pendientesMes = SolicitudCredito::where('tipo',1)->whereBetween('created_at',[$fechaInicioMes,$fechaHoy])->where('estado_id',5)->count();


        $solicitudVigentes = SolicitudCredito::where('tipo',1)->where('estado_id',7)->count();
        $vigentesHoy = SolicitudCredito::whereBetween('updated_at',[$hoy.' 00:00:00',$fechaHoy])->where('tipo',1)->where('estado_id',7)->count();
        $vigentesSemana = SolicitudCredito::whereBetween('updated_at',[$lunes,$domingo])->where('tipo',1)->where('estado_id',7)->count();
        $vigentesMes = SolicitudCredito::whereBetween('updated_at',[$fechaInicioMes,$fechaHoy])->where('tipo',1)->where('estado_id',7)->count();
        $porcentajeDeRechazo = $solicitudesTotales * 100 / $solicitudesRechazadas;

        return response()->json([
            'success'=>true,
            'results'=>[
                'registrosTotales'=>$registros,
                'registrosAyer'=>$registrosAyer,
                'registrosHoy'=>$registrosHoy,
                'registrosSemana'=>$registrosSemana,
                'registrosMes'=>$registrosDelMes,

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

                'solicitudesRechazadas'=>$solicitudesRechazadas,
                'rechazadosHoy'=>$rechazadosHoy,
                'rechazadosSemana'=>$rechazadosSemana,
                'rechazadosMes'=>$rechazadosMes,

                'porcentajeRechazo'=>$porcentajeDeRechazo,

                'solicitudesTotales'=>$solicitudesTotales,
                'solicitudesAyer'=>$solicitudesAyer,
                'solicitudesHoy'=>$solicitudesHoy,
                'solicitudesSemana'=>$solicitudesSemana,
                'solicitudesMes'=>$solicitudesMes,

            ]
        ]);
    }
}
