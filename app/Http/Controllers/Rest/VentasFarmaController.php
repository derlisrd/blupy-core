<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Jobs\ProcesarVentasDelDiaFarmaJobs;
use App\Models\Cliente;
use App\Models\Sucursal;
use App\Models\Venta;
use App\Services\FarmaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VentasFarmaController extends Controller{




    public function ventasDelMes(Request $request){
        $inicioMes = $request->input('desde') ?? Carbon::now()->startOfMonth()->format('Y-m-d H:i:s');
        $finMes = $request->input('hasta') ?? Carbon::now()->endOfDay()->format('Y-m-d H:i:s');

        $results = Venta::whereBetween('fecha',[$inicioMes,$finMes])->get();
        return response()->json([
            'success'=>true,
            'results'=>$results
        ]);
    }

    public function ventasPorSucursal(Request $request){
        $validator = Validator::make($request->all(), [
            'punto' => 'required|integer',
            'desde' => 'nullable|date_format:Y-m-d',
            'hasta' => 'nullable|date_format:Y-m-d'
        ]);
        $fechaDesde = $request->input('desde') ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $fechaHasta = $request->input('hasta') ?? Carbon::now()->endOfDay()->format('Y-m-d');

        $inicio = $fechaDesde . ' 00:00:00';
        $fin = $fechaHasta . ' 23:59:59';

        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        $sucursal = Sucursal::where('punto',$request->punto)->first();

        if (!$sucursal)
            return response()->json(['success' => false, 'message' => 'La sucursal no existe'], 404);

        $results = Venta::whereBetween('fecha',[$inicio,$fin])
        ->where('codigo_sucursal', $sucursal->codigo)
        ->get();

        return response()->json([
            'success'=>true,
            'results'=>$results
        ]);
    }

    public function porcentajeDeUsoBlupy(Request $request){
        $inicioMes = $request->input('desde') ?? Carbon::now()->startOfMonth()->format('Y-m-d H:i:s');
        $finMes = $request->input('hasta') ?? Carbon::now()->endOfDay()->format('Y-m-d H:i:s');

        // Total de clientes
        $totalClientes = Cliente::count();
        $totalFuncionarios = Cliente::where('funcionario',1)->count();
        $totalAso = Cliente::where('asofarma',1)->count();
        $totalDigital = Cliente::join('solicitud_creditos as s','s.cliente_id','=','clientes.id')->where('s.tipo',1)->where('s.estado_id',7)->count();

        //Todos los clientes
        $clientesConVentas = Cliente::whereHas('ventas', function ($query) use ($inicioMes, $finMes) {
            $query->whereBetween('ventas.fecha', [$inicioMes, $finMes]);
        })->count();

        $FuncionariosConVentas = Cliente::where('funcionario', 1)
        ->whereHas('ventas', function ($query) use ($inicioMes, $finMes) {
            $query->whereBetween('ventas.fecha', [$inicioMes, $finMes])
            ->where('ventas.forma_codigo', 129);
        })->count();

        $AsoConVentas = Cliente::where('asofarma', 1)
        ->whereHas('ventas', function ($query) use ($inicioMes, $finMes) {
            $query->whereBetween('ventas.fecha', [$inicioMes, $finMes]);
        })->count();

        $DigitalConVentas = Cliente::whereHas('ventas', function ($query) use ($inicioMes, $finMes) {
            $query->whereBetween('ventas.fecha', [$inicioMes, $finMes])
            ->where('ventas.forma_codigo', 135);
        })->count();

        // Tasa de uso (en porcentaje)
        $tasaDeUso =  ($clientesConVentas / $totalClientes) * 100;
        $tasaDeUsoFuncionarios = ($FuncionariosConVentas / $totalFuncionarios) * 100 ;
        $tasaDeUsoAso = ($AsoConVentas / $totalAso) * 100 ;
        $tasaDeUsoDigital = ($DigitalConVentas / $totalDigital) * 100 ;

        return response()->json([
            'success'=>true,
            'results'=>[
                'tasaUsoTotal' => number_format($tasaDeUso,2) . '%',
                'tasaUsoFuncionario' => number_format($tasaDeUsoFuncionarios,2) . '%',
                'tasaUsoAsoc' => number_format($tasaDeUsoAso,2) . '%',
                'tasaUsoDigital' => number_format($tasaDeUsoDigital,2) . '%'
            ]
        ]);
    }

    public function tickets(Request $request){
        $inicioMes = $request->input('desde') ?? Carbon::now()->startOfMonth()->format('Y-m-d H:i:s');
        $finMes = $request->input('hasta') ?? Carbon::now()->endOfDay()->format('Y-m-d H:i:s');

        $fechaCarbon = Carbon::parse($inicioMes);
        $yearDeMes = $fechaCarbon->year;
        // $inicio = Carbon::parse($inicioMes); // Convierte la fecha de inicio
        // $fin = Carbon::parse($finMes);       // Convierte la fecha de fin

        // $diasContados =  $inicio->diffInDays($fin) + 1;

        $digital = Venta::whereBetween('fecha',[$inicioMes,$finMes])->where('forma_codigo',135)->count();
        $farma = Venta::whereBetween('fecha',[$inicioMes,$finMes])->where('forma_codigo',129)->whereNull('adicional')->count();
        $aso = Venta::whereBetween('fecha',[$inicioMes,$finMes])->where('forma_codigo',129)->whereNotNull('adicional')->count();

        $totalYear = Venta::whereYear('fecha', $yearDeMes)->count();

        return response()->json([
            'success'=>true,
            'results'=>[
                'farma' => $farma,
                'aso' => $aso,
                'digital' => $digital,
                'totalAnio' => $totalYear
            ]
        ]);
    }

    public function ventasDiaFarmaJob(Request $req){
        $validator = Validator::make($req->only(['fecha']), [
            'fecha' => 'required|date_format:Y-m-d'
        ]);

        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        ProcesarVentasDelDiaFarmaJobs::dispatch($req->fecha);
        return response()->json(['success' => true, 'message' => "Las ventas se estan registrando en segundo plano."]);
    }


    public function ventasDiaFarma(){
        $fechaHoy = Carbon::now()->format('Y-m-d');

        $farmaService = new FarmaService();
        $res = (object)$farmaService->ventasRendidas($fechaHoy);
        $data = (object) $res->data;
        $results = [];
        if (property_exists($data, 'result')) {
           $results = $data->result;
        }
        return response()->json([
            'success'=>true,
            'results'=>$results
        ]);

    }


    public function actualizarListaVentasDeHoy(){
        $fechaHoy = Carbon::now()->format('Y-m-d');
        ProcesarVentasDelDiaFarmaJobs::dispatch($fechaHoy);
        return response()->json(['success' => true, 'message' => "Las ventas se estan registrando en segundo plano."]);
    }

    public function ventasTotales(Request $request){
        $inicioMes = $request->input('desde') ?? Carbon::now()->startOfMonth()->format('Y-m-d H:i:s');
        $finMes = $request->input('hasta') ?? Carbon::now()->endOfDay()->format('Y-m-d H:i:s');

        $fechaCarbon = Carbon::parse($inicioMes);
        $yearDeMes = $fechaCarbon->year;

        $lunes = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $domingo = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        $ayer = Carbon::yesterday()->toDateString();

        $importeTotalAyer = Venta::whereDate('fecha', $ayer)
        ->sum('importe_final');
        $importeTotalSemana = Venta::whereBetween('fecha', [$lunes, $domingo])
        ->sum('importe_final');
        $importeFinalMes = Venta::whereBetween('fecha', [$inicioMes,$finMes])
        ->sum('importe_final');

        $importeTotalYear = Venta::whereYear('fecha', $yearDeMes)
        ->sum('importe_final');

        $importeTotalAyerDigital = Venta::whereDate('fecha', $ayer)
        ->where('forma_codigo',135)
        ->sum('importe_final');
        $importeTotalSemanaDigital = Venta::whereBetween('fecha', [$lunes, $domingo])
        ->where('forma_codigo',135)
        ->sum('importe_final');
        $importeTotalMesDigital = Venta::whereBetween('fecha', [$inicioMes,$finMes])
        ->where('forma_codigo',135)
        ->sum('importe_final');

        $importeTotalAyerFuncionario = Venta::whereDate('fecha', $ayer)
        ->where('forma_codigo',129)
        ->whereNull('adicional')
        ->sum('importe_final');
        $importeTotalSemanaFuncionario = Venta::whereBetween('fecha', [$lunes, $domingo])
        ->where('forma_codigo',129)
        ->whereNull('adicional')
        ->sum('importe_final');
        $importeTotalMesFuncionario = Venta::whereBetween('fecha', [$inicioMes,$finMes])
        ->where('forma_codigo',129)
        ->whereNull('adicional')
        ->sum('importe_final');

        $importeTotalAyerAso = Venta::whereDate('fecha', $ayer)
        ->where('forma_codigo',129)
        ->whereNotNull('adicional')
        ->sum('importe_final');
        $importeTotalSemanaAso = Venta::whereBetween('fecha', [$lunes, $domingo])
        ->where('forma_codigo',129)
        ->whereNotNull('adicional')
        ->sum('importe_final');
        $importeTotalMesAso = Venta::whereBetween('fecha', [$inicioMes,$finMes])
        ->where('forma_codigo',129)
        ->whereNotNull('adicional')
        ->sum('importe_final');

        return response()->json([
            'success'=>true,
            'results'=>[
                'descuentoTotalMes'=>'',
                'importeTotalAyer'=>(int)$importeTotalAyer,
                'importeTotalSemana'=>(int)$importeTotalSemana,
                'importeTotalMes'=>(int)$importeFinalMes,

                'importeTotalAyerDigital'=>(int)$importeTotalAyerDigital,
                'importeTotalSemanaDigital'=>(int)$importeTotalSemanaDigital,
                'importeTotalMesDigital'=>(int)$importeTotalMesDigital,


                'importeTotalAyerFuncionario'=>(int)$importeTotalAyerFuncionario,
                'importeTotalSemanaFuncionario'=>(int)$importeTotalSemanaFuncionario,
                'importeTotalMesFuncionario'=>(int)$importeTotalMesFuncionario,

                'importeTotalAyerAso'=>(int)$importeTotalAyerAso,
                'importeTotalSemanaAso'=>(int)$importeTotalSemanaAso,
                'importeTotalMesAso'=>(int)$importeTotalMesAso,

                'importeTotalAnio'=>(int)$importeTotalYear
            ]
        ]);
    }



}
