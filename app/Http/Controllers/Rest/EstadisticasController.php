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
        // Configuración de fechas
        $inicioMes = $request->input('desde') ?? Carbon::now()->startOfMonth()->format('Y-m-d H:i:s');
        $finMes = $request->input('hasta') ?? Carbon::now()->endOfDay()->format('Y-m-d');

        $fechaCarbon = Carbon::parse($inicioMes);
        $yearDeMes = $fechaCarbon->year;
        $mesSeleccionado = $fechaCarbon->month;

        $hoy = Carbon::now()->format('Y-m-d');
        $ayer = Carbon::yesterday()->format('Y-m-d');
        $lunes = Carbon::now()->startOfWeek()->format('Y-m-d H:i:s');
        $domingo = Carbon::now()->endOfWeek()->format('Y-m-d');
        $fechaHace60Dias = Carbon::now()->subDays(60)->format('Y-m-d');

        // Rangos de fechas preformateados para evitar concatenaciones repetidas
        $rangoHoy = [$hoy.' 00:00:00', $hoy.' 23:59:59'];
        $rangoAyer = [$ayer.' 00:00:00', $ayer.' 23:59:59'];
        $rangoSemana = [$lunes, $domingo];
        $rangoMes = [$inicioMes, $finMes];
        $rango60Dias = [$fechaHace60Dias.' 00:00:00', $hoy.' 23:59:59'];

        // Cachear consultas comunes
        $clientesQuery = Cliente::query();
        $solicitudesQuery = SolicitudCredito::where('tipo', 1);

        // Conteos de clientes por tipo
        $clientesPorTipo = $clientesQuery->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN funcionario = 1 AND asofarma = 0 THEN 1 ELSE 0 END) as funcionarios,
            SUM(CASE WHEN funcionario = 0 AND asofarma = 0 THEN 1 ELSE 0 END) as externos,
            SUM(CASE WHEN funcionario = 0 AND asofarma = 1 THEN 1 ELSE 0 END) as asociaciones
        ')->first();

        // Registros por período
        $registrosDelMes = Cliente::whereBetween('created_at', $rangoMes)->count();
        $registrosSemana = Cliente::whereBetween('created_at', $rangoSemana)->count();
        $registrosHoy = Cliente::whereBetween('created_at', $rangoHoy)->count();
        $registrosAyer = Cliente::whereBetween('created_at', $rangoAyer)->count();
        $registroDelAnio = Cliente::whereYear('created_at', $yearDeMes)->count();

        // Registros del mes por tipo
        $registrosMesPorTipo = Cliente::whereBetween('created_at', $rangoMes)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN funcionario = 1 AND asofarma = 0 THEN 1 ELSE 0 END) as funcionarios,
                SUM(CASE WHEN funcionario = 0 AND asofarma = 0 THEN 1 ELSE 0 END) as digital,
                SUM(CASE WHEN funcionario = 0 AND asofarma = 1 THEN 1 ELSE 0 END) as aso
            ')->first();

        // Solicitudes rechazadas
        $rechazadasPorPeriodo = $solicitudesQuery->where('estado_id', 11)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as hoy,
                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as semana,
                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as mes,
                SUM(CASE WHEN YEAR(created_at) = ? THEN 1 ELSE 0 END) as anio
            ', [$hoy, $lunes, $domingo, $inicioMes, $finMes, $yearDeMes])->first();

        // Solicitudes pendientes
        $pendientesPorPeriodo = $solicitudesQuery->where('estado_id', 5)
            ->selectRaw('
                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as total,
                SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as hoy,
                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as semana,
                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as mes
            ', [$fechaHace60Dias.' 00:00:00', $hoy.' 23:59:59', $hoy, $lunes, $domingo, $inicioMes, $finMes])->first();

        // Solicitudes vigentes
        $vigentesPorPeriodo = $solicitudesQuery->where('estado_id', 7)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN DATE(updated_at) = ? THEN 1 ELSE 0 END) as hoy,
                SUM(CASE WHEN updated_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as semana,
                SUM(CASE WHEN updated_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as mes,
                SUM(CASE WHEN YEAR(created_at) = ? THEN 1 ELSE 0 END) as anio
            ', [$hoy, $lunes, $domingo, $inicioMes, $finMes, $yearDeMes])->first();

        // Solicitudes totales por período
        $solicitudesPorPeriodo = $solicitudesQuery
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as hoy,
                SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as ayer,
                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as semana,
                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as mes
            ', [$hoy, $ayer, $lunes, $domingo, $inicioMes, $finMes])->first();

        // Solicitudes por tipo de cliente
        $solicitudesFuncionarios = Cliente::where('clientes.funcionario', 1)
            ->join('solicitud_creditos as s', 'clientes.id', '=', 's.cliente_id')
            ->where('s.tipo', 1)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN s.estado_id = 7 THEN 1 ELSE 0 END) as vigentes,
                SUM(CASE WHEN s.estado_id = 7 AND s.updated_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as vigentesMes
            ', [$inicioMes, $finMes])->first();

        $solicitudesAsociaciones = Cliente::where('clientes.asofarma', 1)
            ->join('solicitud_creditos as s', 'clientes.id', '=', 's.cliente_id')
            ->where('s.tipo', 1)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN s.estado_id = 7 THEN 1 ELSE 0 END) as vigentes,
                SUM(CASE WHEN s.estado_id = 7 AND s.updated_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as vigentesMes
            ', [$inicioMes, $finMes])->first();

        // Solicitudes vigentes externas del mes
        $solicitudVigentesExternosMes = SolicitudCredito::join('clientes', 'clientes.id', '=', 'solicitud_creditos.cliente_id')
            ->where('clientes.funcionario', 0)
            ->where('clientes.asofarma', 0)
            ->whereBetween('solicitud_creditos.updated_at', $rangoMes)
            ->where('tipo', 1)
            ->where('estado_id', 7)
            ->count();

        // Cálculo de porcentaje de rechazo
        $porcentajeDeRechazo = $solicitudesPorPeriodo->total > 0
            ? number_format(($rechazadasPorPeriodo->total * 100 / $solicitudesPorPeriodo->total), 2)
            : 0;

        return response()->json([
            'success' => true,
            'results' => [
                'registrosTotales' => $clientesPorTipo->total,
                'registrosAyer' => $registrosAyer,
                'registrosHoy' => $registrosHoy,
                'registrosSemana' => $registrosSemana,
                'registrosMes' => $registrosDelMes,
                'registrosMesFuncionarios' => $registrosMesPorTipo->funcionarios,
                'registrosMesAso' => $registrosMesPorTipo->aso,
                'registrosMesDigital' => $registrosMesPorTipo->digital,
                'registroDelAnio' => $registroDelAnio,

                'funcionarios' => $clientesPorTipo->funcionarios,
                'asociaciones' => $clientesPorTipo->asociaciones,
                'externos' => $clientesPorTipo->externos,

                'solicitudesPendientes' => $pendientesPorPeriodo->total,
                'pendientesHoy' => $pendientesPorPeriodo->hoy,
                'pendientesSemana' => $pendientesPorPeriodo->semana,
                'pendientesMes' => $pendientesPorPeriodo->mes,

                'solicitudesVigentes' => $vigentesPorPeriodo->total,
                'vigentesHoy' => $vigentesPorPeriodo->hoy,
                'vigentesSemana' => $vigentesPorPeriodo->semana,
                'vigentesMes' => $vigentesPorPeriodo->mes,
                'vigentesDelAnio' => $vigentesPorPeriodo->anio,

                'solicitudesRechazadas' => $rechazadasPorPeriodo->total,
                'rechazadosHoy' => $rechazadasPorPeriodo->hoy,
                'rechazadosSemana' => $rechazadasPorPeriodo->semana,
                'rechazadosMes' => $rechazadasPorPeriodo->mes,
                'rechazadosDelAnio' => $rechazadasPorPeriodo->anio,

                'porcentajeRechazo' => $porcentajeDeRechazo,

                'solicitudesTotales' => $solicitudesPorPeriodo->total,
                'solicitudesAyer' => $solicitudesPorPeriodo->ayer,
                'solicitudesHoy' => $solicitudesPorPeriodo->hoy,
                'solicitudesSemana' => $solicitudesPorPeriodo->semana,
                'solicitudesMes' => $solicitudesPorPeriodo->mes,

                'solicitudesFuncionariosVigentesMes' => $solicitudesFuncionarios->vigentesMes,
                'solicitudesAsociacionesVigentesMes' => $solicitudesAsociaciones->vigentesMes,
                'solicitudVigentesExternosMes' => $solicitudVigentesExternosMes,

                'solicitudesFuncionarios' => $solicitudesFuncionarios->total,
                'solicitudesFuncionariosVigentes' => $solicitudesFuncionarios->vigentes,
                'solicitudesAsociaciones' => $solicitudesAsociaciones->total,
                'solicitudesAsociacionesVigentes' => $solicitudesAsociaciones->vigentes,
            ]
        ]);
    }
}
