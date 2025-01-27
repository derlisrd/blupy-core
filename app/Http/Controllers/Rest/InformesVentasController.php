<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InformesVentasController extends Controller
{
    public function compararFechas (Request $req){

        $validator = Validator::make($req->all(), [
            'fecha1' => ['required', 'regex:/^(0[1-9]|1[0-2])\/\d{4}$/'], // MM/YYYY
            'fecha2' => ['required', 'regex:/^(0[1-9]|1[0-2])\/\d{4}$/']  // MM/YYYY
        ]);

        if($validator->fails())
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()->first()
            ]);

        $fecha1 = explode('/', $req->fecha1);
        $fecha2 = explode('/', $req->fecha2);

        $fecha1_inicio = Carbon::createFromDate($fecha1[1], $fecha1[0], 1)->startOfDay(); // Primer día
        $fecha1_fin = Carbon::createFromDate($fecha1[1], $fecha1[0], 1)->endOfMonth()->endOfDay(); // Último día

        $fecha2_inicio = Carbon::createFromDate($fecha2[1], $fecha2[0], 1)->startOfDay();
        $fecha2_fin = Carbon::createFromDate($fecha2[1], $fecha2[0], 1)->endOfMonth()->endOfDay();

        $ventas1 = Venta::whereBetween('fecha', [$fecha1_inicio, $fecha1_fin]);
        $ventas2 = Venta::whereBetween('fecha', [$fecha2_inicio, $fecha2_fin]);

        $tickets1 = $ventas1->count();
        $tickets2 = $ventas2->count();

        $total1 = $ventas1->sum('total');
        $total2 = $ventas2->sum('total');

        return response()->json([
            'success'=>true,
            'results'=>[
                'ventas1'=>$ventas1->get(),
                'ventas2'=>$ventas2->get(),
                'tickets1'=>$tickets1,
                'total1'=>$total1,
                'tickets2'=>$tickets2,
                'total2'=>$total2
            ]
        ]);
    }
}
