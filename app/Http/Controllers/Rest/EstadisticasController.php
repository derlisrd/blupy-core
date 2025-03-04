<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Informacion;
use App\Models\SolicitudCredito;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EstadisticasController extends Controller
{
     /*
    ==============================================================================================================
    SOLICITUDES Y REGISTROS TOTALES DEL MES, POR DIA Y SEMANA
    ==============================================================================================================
    */
    public function totales(Request $request)
    {
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
        $mesSeleccionado = $fechaCarbon->month;

        $lunes = Carbon::now()->startOfWeek()->format('Y-m-d H:i:s');
        $domingo = Carbon::now()->endOfWeek()->format('Y-m-d');

        $registros = Cliente::count();



        $funcionarios = Cliente::where('funcionario', 1)->where('asofarma', 0)->count();
        $externos = Cliente::where('funcionario', 0)->where('asofarma', 0)->count();
        $asociaciones = Cliente::where('funcionario', 0)->where('asofarma', 1)->count();

        $registrosFuncionarioMes = Cliente::whereBetween('created_at', [$fechaInicioMes, $finMes])->where('funcionario', 1)->where('asofarma', 0)->count();
        $registrosAsoMes = Cliente::whereBetween('created_at', [$fechaInicioMes, $finMes])->where('funcionario', 0)->where('asofarma', 1)->count();
        $registrosDigitalMes = Cliente::whereBetween('created_at', [$fechaInicioMes, $finMes])->where('funcionario', 0)->where('asofarma', 0)->count();
        $registrosDelMes = Cliente::whereBetween('created_at', [$fechaInicioMes, $finMes])->count();
        $registrosSemana = Cliente::whereBetween('created_at', [$lunes, $domingo])->count();
        $registrosHoy = Cliente::whereBetween('created_at', [$hoy . ' 00:00:00', $hoy . ' 23:59:59'])->count();
        $registrosAyer = Cliente::whereBetween('created_at', [$ayer . $primeraHora, $ayer . $ultimaHora])->count();
        $registroDelAnio = Cliente::whereYear('created_at', $yearDeMes)->count();

        $solicitudesRechazadas = SolicitudCredito::where('tipo', 1)->where('estado_id', 11)->count();
        $rechazadosHoy = SolicitudCredito::whereBetween('created_at', [$hoy . ' 00:00:00', $hoy . ' 23:59:59'])->where('tipo', 1)->where('estado_id', 11)->count();
        $rechazadosSemana = SolicitudCredito::whereBetween('created_at', [$lunes, $domingo])->where('tipo', 1)->where('estado_id', 11)->count();
        $rechazadosMes = SolicitudCredito::whereBetween('created_at', [$fechaInicioMes, $finMes])->where('tipo', 1)->where('estado_id', 11)->count();
        $rechazadosDelAnio = SolicitudCredito::whereYear('created_at', $yearDeMes)->where('tipo', 1)->where('estado_id', 11)->count();

        $solicitudesFuncionarios = Cliente::where('s.tipo', 1)->where('clientes.funcionario', 1)->join('solicitud_creditos as s', 'clientes.id', '=', 's.cliente_id')->count();
        $solicitudesFuncionariosVigentes = Cliente::where('s.tipo', 1)->where('s.estado_id', 7)->where('clientes.funcionario', 1)->join('solicitud_creditos as s', 'clientes.id', '=', 's.cliente_id')->count();
        $solicitudesFuncionariosVigentesMes = Cliente::where('s.tipo', 1)
            ->where('s.estado_id', 7)
            ->where('clientes.funcionario', 1)
            ->whereBetween('s.updated_at', [$fechaInicioMes, $finMes])
            ->join('solicitud_creditos as s', 'clientes.id', '=', 's.cliente_id')
            ->count();

        $solicitudesAsociaciones = Cliente::where('s.tipo', 1)->where('clientes.asofarma', 1)->join('solicitud_creditos as s', 'clientes.id', '=', 's.cliente_id')->count();
        $solicitudesAsociacionesVigentes = Cliente::where('s.tipo', 1)->where('s.estado_id', 7)->where('clientes.asofarma', 1)->join('solicitud_creditos as s', 'clientes.id', '=', 's.cliente_id')->count();
        $solicitudesAsociacionesVigentesMes = Cliente::where('s.tipo', 1)
            ->where('s.estado_id', 7)
            ->where('clientes.asofarma', 1)
            ->whereBetween('s.updated_at', [$fechaInicioMes, $finMes])
            ->join('solicitud_creditos as s', 'clientes.id', '=', 's.cliente_id')
            ->count();


        $solicitudesTotales = SolicitudCredito::where('tipo', 1)->where('estado_id', '<>', null)->count();
        $solicitudesHoy = SolicitudCredito::whereBetween('created_at', [$hoy . ' 00:00:00', $hoy . ' 23:59:59'])->where('tipo', 1)->count();
        $solicitudesAyer = SolicitudCredito::whereBetween('created_at', [$ayer . $primeraHora, $ayer . $ultimaHora])->where('tipo', 1)->count();
        $solicitudesSemana = SolicitudCredito::whereBetween('created_at', [$lunes, $domingo])->where('tipo', 1)->count();
        $solicitudesMes = SolicitudCredito::where('tipo', 1)->whereBetween('created_at', [$fechaInicioMes, $finMes])->count();

        $solicitudesPendientes = SolicitudCredito::whereBetween('created_at', [$fechaHace60Dias . ' 00:00:00', $hoy . ' 23:59:59'])->where('tipo', 1)->where('estado_id', 5)->count();
        $pendientesHoy = SolicitudCredito::whereBetween('created_at', [$hoy . ' 00:00:00', $hoy . ' 23:59:59'])->where('tipo', 1)->where('estado_id', 5)->count();
        $pendientesSemana = SolicitudCredito::whereBetween('created_at', [$lunes, $domingo])->where('tipo', 1)->where('estado_id', 5)->count();
        $pendientesMes = SolicitudCredito::where('tipo', 1)->whereBetween('created_at', [$fechaInicioMes, $finMes])->where('estado_id', 5)->count();


        $solicitudVigentes = SolicitudCredito::where('tipo', 1)->where('estado_id', 7)->count();

        $solicitudVigentesExternosMes = SolicitudCredito::join('clientes', 'clientes.id', '=', 'solicitud_creditos.cliente_id')
            ->where('clientes.funcionario', 0)
            ->where('clientes.asofarma', 0)
            ->whereBetween('solicitud_creditos.updated_at', [$fechaInicioMes, $finMes])
            ->where('tipo', 1)->where('estado_id', 7)->count();

        $vigentesHoy = SolicitudCredito::whereBetween('updated_at', [$hoy . ' 00:00:00', $hoy . ' 23:59:59'])->where('tipo', 1)->where('estado_id', 7)->count();
        $vigentesSemana = SolicitudCredito::whereBetween('updated_at', [$lunes, $domingo])->where('tipo', 1)->where('estado_id', 7)->count();
        $vigentesMes = SolicitudCredito::whereBetween('updated_at', [$fechaInicioMes, $finMes])->where('tipo', 1)->where('estado_id', 7)->count();
        $vigentesDelAnio = SolicitudCredito::whereYear('created_at', $yearDeMes)->where('tipo', 1)->where('estado_id', 7)->count();
        $porcentajeDeRechazo = number_format(($solicitudesRechazadas  * 100 / $solicitudesTotales), 2);

        return response()->json([
            'success' => true,
            'results' => [
                'registrosTotales' => $registros,
                'registrosAyer' => $registrosAyer,
                'registrosHoy' => $registrosHoy,
                'registrosSemana' => $registrosSemana,
                'registrosMes' => $registrosDelMes,
                'registrosMesFuncionarios' => $registrosFuncionarioMes,
                'registrosMesAso' => $registrosAsoMes,
                'registrosMesDigital' => $registrosDigitalMes,

                'registroDelAnio' => $registroDelAnio,

                'funcionarios' => $funcionarios,
                'asociaciones' => $asociaciones,
                'externos' => $externos,

                'solicitudesPendientes' => $solicitudesPendientes,
                'pendientesHoy' => $pendientesHoy,
                'pendientesSemana' => $pendientesSemana,
                'pendientesMes' => $pendientesMes,

                'solicitudesVigentes' => $solicitudVigentes,
                'vigentesHoy' => $vigentesHoy,
                'vigentesSemana' => $vigentesSemana,
                'vigentesMes' => $vigentesMes,
                'vigentesDelAnio' => $vigentesDelAnio,

                'solicitudesRechazadas' => $solicitudesRechazadas,
                'rechazadosHoy' => $rechazadosHoy,
                'rechazadosSemana' => $rechazadosSemana,
                'rechazadosMes' => $rechazadosMes,
                'rechazadosDelAnio' => $rechazadosDelAnio,

                'porcentajeRechazo' => $porcentajeDeRechazo,

                'solicitudesTotales' => $solicitudesTotales,
                'solicitudesAyer' => $solicitudesAyer,
                'solicitudesHoy' => $solicitudesHoy,
                'solicitudesSemana' => $solicitudesSemana,
                'solicitudesMes' => $solicitudesMes,

                'solicitudesFuncionariosVigentesMes' => $solicitudesFuncionariosVigentesMes,

                'solicitudesAsociacionesVigentesMes' => $solicitudesAsociacionesVigentesMes,

                'solicitudVigentesExternosMes' => $solicitudVigentesExternosMes,

                'solicitudesFuncionarios' => $solicitudesFuncionarios,
                'solicitudesFuncionariosVigentes' => $solicitudesFuncionariosVigentes,
                'solicitudesAsociaciones' => $solicitudesAsociaciones,
                'solicitudesAsociacionesVigentes' => $solicitudesAsociacionesVigentes,

            ]
        ]);
    }
}
