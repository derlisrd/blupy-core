<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Services\InfinitaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClienteController extends Controller
{

    public function actualizarSelfieCedula(Request $req){
        $validator = Validator::make($req->all(), [
            'fotoSelfie' => 'required|string', // Se requiere y debe ser una cadena (Base64)
            // Agrega aquí otras validaciones si son necesarias, por ejemplo, para 'cedula' si viniera en el request
        ], [
            'fotoSelfie.required' => 'La foto selfie es obligatorio.',
            'fotoSelfie.string' => 'El formato de la foto selfie es inválido.'
        ]);

        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        $user = $req->user();
        $infinitaService = new InfinitaService();

        $cliente = $user->cliente;
        $fotoEnBase64 = $req->fotoSelfie;
        $fotoRecibida = preg_replace('#data:image/[^;]+;base64,#', '', $fotoEnBase64);
        $infinitaService->enviarSelfie($cliente->cedula, $fotoRecibida);

        return response()->json(['success'=>true,'message'=>'Ingresado correctamente']);
    }


    public function actualizarCedulaFrente(Request $req){
        $validator = Validator::make($req->all(), [
            'fotoFrente' => 'required|string', // Se requiere y debe ser una cadena (Base64)
            // Agrega aquí otras validaciones si son necesarias, por ejemplo, para 'cedula' si viniera en el request
        ], [
            'fotoFrente.required' => 'La foto de la cedula es obligatoria.',
            'fotoFrente.string' => 'El formato de la foto selfie es inválido.'
        ]);

        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        $user = $req->user();
        $infinitaService = new InfinitaService();

        $cliente = $user->cliente;
        $fotoEnBase64 = $req->fotoFrente;
        $fotoRecibida = preg_replace('#data:image/[^;]+;base64,#', '', $fotoEnBase64);
        $infinitaService->enviarFoto($cliente->cedula,'Cedula frente', 'Cedula Frente', $fotoRecibida);

        return response()->json(['success'=>true,'message'=>'Ingresado correctamente']);
    }



    public function actualizarCedulaDorso(Request $req){
        $validator = Validator::make($req->all(), [
            'fotoDorso' => 'required|string', // Se requiere y debe ser una cadena (Base64)
            // Agrega aquí otras validaciones si son necesarias, por ejemplo, para 'cedula' si viniera en el request
        ], [
            'fotoDorso.required' => 'La foto de la cedula es obligatoria.',
            'fotoDorso.string' => 'El formato de la foto selfie es inválido.'
        ]);

        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        $user = $req->user();
        $infinitaService = new InfinitaService();

        $cliente = $user->cliente;
        $fotoEnBase64 = $req->fotoDorso;
        $fotoRecibida = preg_replace('#data:image/[^;]+;base64,#', '', $fotoEnBase64);
        $infinitaService->enviarFoto($cliente->cedula,'Cedula dorso', 'Cedula dorso', $fotoRecibida);

        return response()->json(['success'=>true,'message'=>'Ingresado correctamente']);
    }



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
        $results = $cliente->adicionales;

        return response()->json([
            'success'=>true,
            'results'=>$results
        ]);
    }

    public function tarjeta(Request $req){

        $results = [];
        $cedula = $req->cedula;
        $infinitaService = new InfinitaService();
        $resInfinita = $infinitaService->ListarTarjetasPorDoc($cedula);
        $infinita = (object)$resInfinita['data'];
        if(property_exists( $infinita,'Tarjetas')){
            foreach ($infinita->Tarjetas as $val) {
                array_push($results, [
                    'id'=>2,
                    'descripcion'=>'Blupy crédito digital',
                    'otorgadoPor'=>'Mi crédito S.A.',
                    'tipo'=>1,
                    'bloqueo'=> $val['MTBloq'] === "" ? false : true,
                    'condicion'=>'Contado',
                    'condicionVenta'=>1,
                    'cuenta' => $val['MaeCtaId'],
                    'numeroTarjeta'=>$val['MTNume'],
                    'linea' => (int)$val['MTLinea'],
                    'pagoMinimo'=> (int) $val['MCPagMin'],
                    'deuda' => (int) $val['MTSaldo'],
                    'disponible' => (int) $val['MTLinea'] - (int) $val['MTSaldo'],
                    'alianzas' => []
                ]);
            }
        }
        $resultado = count($results) > 0 ? $results[0] : [];
        return response()->json([
            'success'=>true,
            'results'=>$resultado
        ]);
    }
}
