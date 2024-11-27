<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Venta;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function misDescuentos(Request $req){
        $user = $req->user();


        $descuentos = Venta::where('cliente_id',$user->cliente->id)
        ->orderByDesc('id')
        ->select('id','factura_numero','importe','importe_final','descuento','sucursal','fecha');
       /*  $descuentoTotal = $cliente->ventas()
        ->sum('descuento'); */

        return response()->json([
            'success'=>true,
            'results'=>[
                'descuentos'=>$descuentos->get(),
                'descuentosTotales'=>(int) $descuentos->sum('descuento')
            ]
        ]);
    }

    public function misAdicionales(Request $req){
        $user = $req->user();
        $cliente = $user->cliente;
        $results = $cliente->adicional;

        return response()->json([
            'success'=>true,
            'results'=>$results
        ]);
    }
}
