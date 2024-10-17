<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Jobs\ProcesarVentasDelDiaFarmaJobs;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VentasFarmaController extends Controller
{
    public function ventasDiaFarma(Request $req){
        $validator = Validator::make($req->only(['fecha']), [
            'fecha' => 'required|date_format:Y-m-d'
        ]);

        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        ProcesarVentasDelDiaFarmaJobs::dispatch($req->fecha);
        return response()->json(['success' => true, 'message' => "Las ventas se estan registrando en segundo plano."]);
    }

    public function ventasTotales(){
        $lunes = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $domingo = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        $ayer = Carbon::yesterday()->toDateString();

        $importeTotalAyer = Venta::whereDate('fecha', $ayer)
        ->sum('importe_final');

        $descuentoTotalSemana = Venta::whereBetween('fecha', [$lunes, $domingo])
        ->sum('importe_final');

        $descuentoMes = Venta::whereMonth('fecha', Carbon::now()->month)
        ->whereYear('fecha', Carbon::now()->year)
        ->sum('descuento');
        $importeFinalMes = Venta::whereMonth('fecha', Carbon::now()->month)
        ->whereYear('fecha', Carbon::now()->year)
        ->sum('importe_final');
        $importeFinalMesDigital = Venta::whereMonth('fecha', Carbon::now()->month)
        ->whereYear('fecha', Carbon::now()->year)
        ->where('forma_codigo',135)
        ->sum('importe_final');
        $importeFinalMesFuncionario = Venta::whereMonth('fecha', Carbon::now()->month)
        ->whereYear('fecha', Carbon::now()->year)
        ->where('forma_codigo',129)
        ->whereNull('adicional')
        ->sum('importe_final');
        $importeFinalMesAso = Venta::whereMonth('fecha', Carbon::now()->month)
        ->whereYear('fecha', Carbon::now()->year)
        ->where('forma_codigo',129)
        ->whereNotNull('adicional')
        ->sum('importe_final');

        return response()->json([
            'success'=>true,
            'results'=>[
                'descuentoTotalMes'=>$descuentoMes,
                'importeTotalMes'=>$importeFinalMes,
                'importeTotalDigital'=>$importeFinalMesDigital,
                'importeTotalMesFuncionario'=>$importeFinalMesFuncionario,
                'importeTotalMesAso'=>$importeFinalMesAso,
                'descuentoTotalSemana'=>$descuentoTotalSemana,
                'importeTotalAyer'=>$importeTotalAyer
            ]
        ]);
    }



    /* public function VentasDelMes(Request $req){
        $validator = Validator::make($req->only(['fecha_inicio', 'fecha_fin']), [
            'fecha_inicio' => 'required|date_format:Y-m-d',
            'fecha_fin' => 'required|date_format:Y-m-d|after_or_equal:fecha_inicio'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        $fechaInicio = Carbon::parse($req->fecha_inicio);
        $fechaFin = Carbon::parse($req->fecha_fin);

        // Recorrer cada día del rango
        for ($date = $fechaInicio; $date->lte($fechaFin); $date->addDay()) {
            ProcesarVentasDelDiaFarmaJobs::dispatch($date->format('Y-m-d'));
        }


        return response()->json(['success' => true, 'message' => "El Job para procesar las ventas ha sido despachado."]);
    } */
}
