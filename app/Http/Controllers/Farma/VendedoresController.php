<?php

namespace App\Http\Controllers\Farma;

use App\Http\Controllers\Controller;
use App\Models\Vendedor;
use App\Services\FarmaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class VendedoresController extends Controller
{
    public function generarQRVendedor(Request $r){

        $ip = $r->ip();
        $executed = RateLimiter::attempt($ip,$perTwoMinutes = 10,function() {});
        if (!$executed)
            return response()->json(['success'=>false, 'message'=>'Demasiadas peticiones. Espere 1 minuto.' ],500);

        $validator = Validator::make($r->only(['cedula']), [
            'cedula' => 'required',
        ]);
        if ($validator->fails())
            return response()->json([
                'success'=>false,
                'message'=> $validator->errors(),
                'results'=>null
            ], 400);


        $vendedor = Vendedor::where('cedula',$r->cedula)->first();
        if($vendedor){
            return response()->json([
                'success'=>true,
                'message'=> '',
                'results'=>$vendedor
            ]);
        }

        return response()->json([
            'success'=>false,
            'results'=> null,
            'message'=>'No existe registro.'
        ],404);
    }


    public function registrarVendedorQr(Request $r){
        $ip = $r->ip();
        $executed = RateLimiter::attempt($ip,$perTwoMinutes = 3,function() {});
        if (!$executed)
            return response()->json(['success'=>false, 'message'=>'Demasiadas peticiones. Espere 1 minuto.' ],500);

        $validator = Validator::make($r->all(), [
            'cedula' => 'required|unique:vendedores,cedula',
            'punto' => 'required|max:4',
        ],['cedula.unique'=>'Vendedor ya ingresado.']);

        if ($validator->fails())
            return response()->json([
                'success'=>false,
                'message'=> $validator->errors()->first()
            ], 400);


        $farmaService = new FarmaService();
        $res = (object) $farmaService->cliente($r->cedula);
        $data = (object)$res->data;

        if(property_exists($data,'result') && count($data->result) > 0){
            $ficha = $data->result[0];
            if($ficha['esFuncionario'] === 'S'){
                $datoInsertar = [
                'cedula' => $r->cedula,
                'nombre' =>$ficha['persNombre'],
                'punto' => $r->punto
                ];

                Vendedor::create($datoInsertar);

                return response()->json([
                    'success'=>true,
                    'message'=> 'Ingresado. Ya dispone de un QR vendedor.'
                ]);
            }
        }

        return response()->json([
            'success'=>true,
            'message'=>'Error al ingresar. Contacte con nosotros.',
        ],500);


    }
}
