<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Jobs\ProcesarVentasDelDiaFarmaJobs;
use App\Models\Cliente;
use App\Models\Venta;
use App\Services\FarmaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
