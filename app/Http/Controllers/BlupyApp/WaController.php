<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Validacion;
use App\Services\WaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class WaController extends Controller
{

    // para verificacion de nuevo dispositivo solicitandolo
    public function codigoVincularNuevoDispositivo(Request $req){
        try {
            $validator = Validator::make($req->all(), ['id' => 'required'], ['id.required' => 'El id obligatorio']);
            if ($validator->fails())
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

            $ip = $req->ip();
            $rateKey = $ip . '|' . $req->id;

            if (RateLimiter::tooManyAttempts($rateKey, 3))
                return response()->json(['success' => false, 'message' => 'Demasiadas peticiones. Espere 1 minuto.'], 400);

            RateLimiter::hit($rateKey, 60);

            $validacion = Validacion::where('id', $req->id)->where('validado', 0)->first();

            if (!$validacion)
                return response()->json(['success' => false, 'message' => 'No existe codigo'], 404);

            $mensaje = "Tu código de verificación para Blupy es " . $validacion->codigo . "  Este código es válido por 10 minutos.";
            $numeroTelefonoWa = '595' . substr($validacion->celular, 1);
            (new WaService())->send($numeroTelefonoWa, $mensaje);
            return response()->json(['success'=>true,'message'=>'Enviado correctamente.']);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function reEnviameElCodigoDeRecuperacion(Request $req){
        try {
            $validator = Validator::make($req->all(), ['id' => 'required'], ['id.required' => 'El id obligatorio']);
            if ($validator->fails())
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

            $ip = $req->ip();
            $rateKey = $ip . '|' . $req->id;

            if (RateLimiter::tooManyAttempts($rateKey, 3))
                return response()->json(['success' => false, 'message' => 'Demasiadas peticiones. Espere 1 minuto.'], 400);

            RateLimiter::hit($rateKey, 60);

            $validacion = Validacion::where('id', $req->id)->where('validado', 0)->first();
            if (!$validacion)
                return response()->json(['success' => false, 'message' => 'No existe codigo'], 404);
            
            $randomNumber = random_int(1000, 9999);

            // Actualizar el registro existente — mismo ID, código nuevo
            $validacion->codigo     = $randomNumber;
            $validacion->timestamps = false;
            $validacion->created_at = Carbon::now();
            $validacion->save();

            

            $mensaje = "Tu código de recuperación para Blupy es " . $validacion->codigo . "  Este código es válido por 10 minutos.";
            $numeroTelefonoWa = '595' . substr($validacion->celular, 1);
            (new WaService())->send($numeroTelefonoWa, $mensaje);
            return response()->json(['success'=>true,'message'=>'Enviado correctamente.']);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
