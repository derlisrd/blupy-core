<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Venta;
use App\Services\InfinitaService;
use Illuminate\Http\Request;

class ClienteController extends Controller
{

    public function actualizarSelfieCedula(Request $req){
        $user = $req->user();
        $infinitaService = new InfinitaService();

        $cliente = $user->cliente;
        $fotoEnBase64 = $req->fotoSelfie;
        $infinitaService->enviarSelfie($cliente->cedula, $fotoEnBase64);

        return response()->json(['success'=>true,'message'=>'Ingresado correctamente']);
    }
    public function actualizarCedulaFrente(Request $req){
        $user = $req->user();
        $infinitaService = new InfinitaService();

        $cliente = $user->cliente;
        $fotoEnBase64 = $req->fotoFrente;
        $infinitaService->enviarFoto($cliente->cedula,'Cedula frente', 'Cedula Frente', $fotoEnBase64);

        return response()->json(['success'=>true,'message'=>'Ingresado correctamente']);
    }
    public function actualizarCedulaDorso(Request $req){
        $user = $req->user();
        $infinitaService = new InfinitaService();

        $cliente = $user->cliente;
        $fotoEnBase64 = $req->fotoDorso;
        $infinitaService->enviarFoto($cliente->cedula,'Cedula dorso', 'Cedula dorso', $fotoEnBase64);

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
