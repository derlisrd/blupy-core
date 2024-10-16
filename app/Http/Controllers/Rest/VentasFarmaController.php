<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Venta;
use App\Services\FarmaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VentasFarmaController extends Controller
{
    public function VentasDelDia(Request $req){
        $validator = Validator::make($req->only('fecha'),['fecha' => 'required|date_format:Y-m-d']);

        if ($validator->fails())
            return response()->json(['success'=>false,'message' => $validator->errors()->first()], 400);

        $farmaService = new FarmaService();

        $res = (object)$farmaService->ventasRendidas($req->fecha);
        $data = (object) $res->data;
        $results = [];
        if(property_exists($data,'result')){
            $ventas = $data->result;
            foreach($ventas as $v){
                $venta = Venta::where('codigo',$v['ventCodigo'])->first();
                if(!$venta){
                    $fechaFormateada = Carbon::parse($v['ventFecha'])->format('Y-m-d H:i:s');
                    $cliente = Cliente::where('cedula',$v['cedula'])->first();
                    $cliente_id = $cliente ? $cliente->id : null;
                    $ventaCreada = Venta::create([
                        'cliente_id'=>$cliente_id,
                        'codigo'=>$v['ventCodigo'],
                        'documento'=>$v['cedula'],
                        'adicional'=>$v['clieCodigoAdicional'],
                        'factura_numero'=>$v['ventNumero'],
                        'importe'=>$v['ventTotBruto'],
                        'descuento'=>$v['ventTotDescuento'],
                        'importe_final'=>$v['ventTotNeto'],
                        'forma_pago'=>$v['frpaAbreviatura'],
                        'forma_codigo'=>$v['frpaCodigo'],
                        'descripcion'=>$v['ventCodigo'],
                        'sucursal'=>$v['estrDescripcion'],
                        'fecha'=>$fechaFormateada,
                        'forma_venta'=>$v['ventTipo']
                    ]);
                    array_push($results,$ventaCreada);
                }
            }
        }

        return response()->json(['success'=>true,'message'=>"Ventas ingresadas", 'results'=>$results]);

    }
}
