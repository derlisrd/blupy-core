<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceNewRequest;
use App\Models\User;
use App\Models\Validacion;
use App\Services\EmailService;
use App\Services\SupabaseService;
//use App\Services\SupabaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;

class DeviceController extends Controller
{

    private function convertirImagenYSubir($imagenBase64, $imageName)
    {
        if (!$imagenBase64 || !str_contains($imagenBase64, ';base64,')) {
            return null;
        }

        // Extraer extensión y datos
        $parts = explode(";base64,", $imagenBase64);
        $imageType = str_replace("data:image/", "", $parts[0]);
        $image_binary = base64_decode($parts[1]);

        $imageFinalName = $imageName . '.' . $imageType;

        // Subir y obtener URL
        return SupabaseService::uploadImage($imageFinalName, $image_binary, $imageType);
    }

    public function requestNewDevice(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'validacion_id' => 'required',
            'device'      => 'required',
            'email'       => 'required|email',
            'celular'     => 'required',
            'devicetoken' => 'required',
            'model' => 'required',
            'cedula_frente' => 'required',
            'cedula_dorso' => 'required',
            'cedula_selfie' => 'required',
            'os' => 'required',
            'model' => 'required',
        ]);
        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        $key = 'request-device:' . $req->ip();
        $executed = RateLimiter::attempt($key, $maxAttempts = 2, function () {
            return true;
        }, 120);

        if (!$executed) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Demasiados intentos. Por favor espera $seconds segundos."
            ], 429);
        }

        try {
            $validacion = Validacion::find($req->validacion_id);
            if (!$validacion) {
                return response()->json(['success' => false, 'message' => 'No existe validacion'], 404);
            }
            $user = User::where('cliente_id', $validacion->cliente_id)->first();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'No existe usuario'], 404);
            }

            $time = time() . '_' . $user->id;

            // 1. Procesar y subir imágenes (Obtenemos las URLs de Supabase)
            $urlFrente = $this->convertirImagenYSubir($req->cedula_frente, "frente_{$time}");
            $urlDorso  = $this->convertirImagenYSubir($req->cedula_dorso, "dorso_{$time}");
            $urlSelfie = $this->convertirImagenYSubir($req->cedula_selfie, "selfie_{$time}");

            // 2. Guardar en BD con las URLs finales
            DeviceNewRequest::create([
                'user_id'           => $user->id,
                'ip'                => $req->ip(),
                'os'                => $req->os,
                'celular'           => $req->celular,
                'devicetoken'       => $req->devicetoken,
                'email'             => $req->email,
                'location'          => $req->location,
                'model'             => $req->model,
                'cedula_frente_url' => $urlFrente,
                'cedula_dorso_url'  => $urlDorso,
                'cedula_selfie_url' => $urlSelfie,
                'web'               => $req->web ? 1 : 0,
                'desktop'           => $req->desktop ? 1 : 0,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Solicitud enviada correctamente.'
            ], 201);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud.'
            ], 500);
        }
    }



    public function codigoNuevoDispositivo(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), trans('validation.device.codigo'), trans('validation.device.codigo.messages'));
            if ($validator->fails())
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
            $ip = $req->ip();
            $executed = RateLimiter::attempt($ip, $perTwoMinutes = 2, function () {});
            if (!$executed)
                return response()->json(['success' => false, 'message' => 'Demasiadas peticiones. Espere 1 minuto.'], 500);
            $id = null;
            $user = User::where('email', $req->email)->first();
            if ($user) {
                $randomNumber = random_int(1000, 9999);
                $emailService = new EmailService();
                $cliente = $user->cliente;
                $emailService->enviarEmail($req->email, "[" . $randomNumber . "]Blupy confirmar dispositivo", 'email.validarDispositivo', ['code' => $randomNumber]);
                $validacion = Validacion::create(['codigo' => $randomNumber, 'forma' => 0, 'email' => $req->email, 'cliente_id' => $cliente->id, 'origen' => 'dispositivo']);
                $id = $validacion->id;
            }

            return response()->json(['success' => true, 'results' => ['id' => $id], 'message' => 'Te hemos enviado un correo electronico para verificar tu cuenta']);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function confirmarNuevoDispositivo(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), trans('validation.device.confirmar'), trans('validation.device.confirmar.messages'));
            if ($validator->fails())
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

            $id = $req->id;
            $codigo = $req->codigo;
            $ip = $req->ip();
            $devicetoken = $req->devicetoken;
            $os = $req->os;
            $notitoken = $req->notitoken;
            $device = $req->device;
            $web = $req->web ? 1 : 0;
            $model = $req->model;
            $desktop = $req->desktop ? 1 : 0;
            $confianza = $req->confianza ? 1 : 0;

            $validacion = Validacion::where('id', $id)->where('codigo', $codigo)->where('validado', 0)->latest('created_at')->first();
            if (!$validacion)
                return response()->json(['success' => false, 'message' => 'Código inválido.'], 400);

            $fechaInicial = Carbon::parse($validacion->created_at);
            $fechaActual = Carbon::now();
            $diferenciaEnMinutos = $fechaInicial->diffInMinutes($fechaActual);

            if ($diferenciaEnMinutos >= 10)
                return response()->json(['success' => false, 'message' => 'Código ha expirado'], 401);

            $validacion->validado = 1;
            $validacion->save();
            $user = User::where('cliente_id', $validacion->cliente_id)->first();
            Device::create([
                'device' => $device,
                'notitoken' => $notitoken,
                'devicetoken' => $devicetoken,
                'os' => $os,
                'user_id' => $user->id,
                'web' => $web,
                'model' => $model,
                'ip' => $ip,
                'desktop' => $desktop,
                'confianza' => $confianza
            ]);

            return response()->json(['success' => true, 'message' => 'Dispositivo registrado correctamente']);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
