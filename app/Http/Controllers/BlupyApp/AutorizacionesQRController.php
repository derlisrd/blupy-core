<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Services\BlupyQrService;
use App\Services\FarmaService;
use App\Services\PushExpoService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AutorizacionesQRController extends Controller
{
    public function solicitarAutorizacion(Request $req){
        try{
            $user = $req->user();
            $cliente = $user->cliente;

            $blupyQrService = new BlupyQrService();

            $blupy = $blupyQrService->consultarPorDocumento($cliente->cedula);
            $data = (object) $blupy['data'];

            if ($data->success) {
                return response()->json([
                    'success' => $data->success,
                    'message' => '',
                    'results' => $data->results,
                ], $blupy['status']);
            }

            return response()->json([
                'success' => $data->success,
                'message' => $data->message
            ], $blupy['status']);
            ///return response()->json(['cedula'=>$cliente->cedula]);

        }catch(\Exception $e){
            throw $e;
            return response()->json([
                'success' => false,
                'message' => 'Error al solicitar autorización',
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function autorizar(Request $req){
        $validator = Validator::make($req->all(), [
            'id' => 'required',
            'numeroCuenta' => 'required',
            'numeroTarjeta' => 'nullable',
            'password' => 'required',
        ],[
            'id.required' => 'ID requerido.',

            'password.required' => 'Contraseña requerida.'
        ]);

        if ($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first()],400);


        try{
            $user = $req->user();
            $cliente = $user->cliente;

            $cedula = $cliente->cedula;

            if (!Hash::check($req->password, $user->password))
            return response()->json(['success'=>false,'message'=>'Contraseña incorrecta.'],401);

            $parametrosPorArray = [
                'id' => $req->id,
                'documento' => $cliente->cedula,
                'numeroCuenta' => $req->numeroCuenta,
                'numeroTarjeta' => $req->numeroTarjeta,
                'telefono' => $req->telefono,
                'ip' => $req->ip(),
                'localizacion' => $req->localizacion,
                'adicional' => $req->adicional,
            ];
            $blupyQrService = new BlupyQrService();
            $blupy = $blupyQrService->autorizarQR($parametrosPorArray);
            $data = (object) $blupy['data'];

            if (property_exists($data, 'results')) {
                $noti = new PushExpoService();
                $tokens = $user->notitokens();
                $noti->send($tokens, 'Compra en comercio', 'Se ha registrado una compra en comercio', []);
                SupabaseService::LOG('Compra commercio', 'Por autorizacion' . $cedula);
                Notificacion::create([
                    'user_id' => $user->id,
                    'title' => 'Compra en comercio',
                    'body' => $data->results['info']
                ]);

                $datasResults = $data->results;
                if ($datasResults['web'] === 0 && $datasResults['farma'] === 1) {
                    $farmaService = new FarmaService();
                    $farmaService->actualizarPedidosQR(
                        (string)$datasResults['id'],
                        $datasResults['numero_cuenta'],
                        $datasResults['numero_tarjeta'],
                        $datasResults['numero_movimiento']
                    );
                }
            }


            return response()->json([
                'success' => $data->success,
                'message' => $data->message,
                'results' => $data->results
            ], $blupy['status']);

            return response()->json();

        }catch(\Exception $e){
            throw $e;
            return response()->json([
                'success' => false,
                'message' => 'Error al solicitar autorización',
                'error' => $e->getMessage()
            ],500);
        }
    }

}
