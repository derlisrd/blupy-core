<?php

namespace App\Http\Controllers\BlupyApp\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\DispositivoInusualJob;
use App\Models\Adicional;
use App\Models\Cliente;
use App\Models\Device;
use App\Models\Validacion;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class LoginController extends Controller
{
    public function login(Request $req)
    {
        try {
            // 1. Validación de entrada
            $validator = $this->validateLoginRequest($req);
            if ($validator->fails()) 
                return $this->errorResponse($validator->errors()->first(), 400);
            

            // 2. Control de rate limiting
            $ip = ($req->ip());


                $rateKey = "login:$ip";
                if (RateLimiter::tooManyAttempts($rateKey, 5)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Demasiadas peticiones. Espere 1 minuto.'
                    ], 400);
                }

                RateLimiter::hit($rateKey, 60);
            

            // 3. Buscar cliente
            $cliente = Cliente::with(['user'])->where('cedula', $req->cedula)->first();
            if (!$cliente)
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no existe. Regístrese'
                ], 400);
            

            // 4. Verificar estado de cuenta
            $accountStatusResponse = $this->checkAccountStatus($cliente->user);
            if ($accountStatusResponse) 
                return $accountStatusResponse;
            

            // 5. Intentar autenticación
            $credentials = ['email' => $cliente->user->email, 'password' => $req->password];
            $token = JWTAuth::attempt($credentials);

            if (!$token) {
                $this->incrementLoginAttempts($cliente->user);
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ], 401);
            }
            

            // 6. Verificar dispositivo de confianza (solo para rol 0)
            if ($cliente->user->rol === 0) {
                $user = $cliente->user;
                $dispositoDeConfianza = Device::where('user_id', $user->id)
                        ->where('desktop', $req->desktop)
                        ->where('web', $req->web)
                        ->where('device', $req->device)
                        ->where('devicetoken', $req->devicetoken)
                        ->first();
                        if (!$dispositoDeConfianza) {
                            $pistaEmail =  $user->email; 
                            //$this->ocultarParcialmenteEmail($user->email);
                            $pistaDeNumero = $this->ocultarParcialmenteTelefono($cliente->celular);
                            $idValidacion = $this->enviarSMSyEmaildispositivoInusual($user->email, $cliente->celular, $cliente->id, $req);
                            return response()->json([
                                'success' => true,
                                'results' => null,
                                'id' => $idValidacion,
                                'message' => 'Valida el dispositivo. PIN enviado a ' . $pistaDeNumero . '. y al correo ' . $pistaEmail . '. Verifica tu whatsapp.'
                            ]);
                        }
                        $dispositoDeConfianza->update([
                            'version' => $req->version,
                            'ip' => $req->ip()
                        ]);
                        $dispositoDeConfianza->touch();
            } 
            $cliente->user->update([
                'intentos' => 0,
                'ultimo_ingreso' => now(),
                'version' => $req->version
            ]);
    
            // Verificar si es adicional
            $esAdicional = Adicional::where('cedula', $req->cedula)->exists();
            $telefono = $req->device ?? 'Telefono Desconocido. '; ;
            $model = $req->model ?? 'Modelo Desconocido. ';
            $web = $req->web ? 'web' : 'Dispositivo movil';
            $version = $req->version ?? 'sin version';
            $deviceInfo = $version .' | ' . $telefono . ' | ' . $model . ' | ' . $web;
            SupabaseService::registrarSesion($req->cedula,$deviceInfo .' | ' . $req->ip());
            return response()->json([
                'success' => true,
                'message' => 'Ingreso exitoso',
                'id' => null,
                'results' => $this->userInformacion($cliente, $token, $esAdicional)
            ]);
            
        } catch (\Throwable $th) {
            SupabaseService::LOG('loginError',$th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error de servidor. Contacte con soporte.'
            ], 400);
        }
    }

    // Métodos auxiliares para mejorar la legibilidad y mantenibilidad

    private function validateLoginRequest(Request $req)
    {
        return Validator::make($req->all(), [
            'cedula' => 'required|string|regex:/^[0-9\-]+$/',
            'password' => 'required|string|min:6',
            'desktop' => 'nullable|boolean',
            'web' => 'nullable|boolean',
            'device' => 'nullable|string',
            'devicetoken' => 'nullable|string',
            'version' => 'nullable|string'
        ],[
            'cedula.required' => 'Ingrese la cédula por favor.',
            'cedula.regex' => 'La cédula no debe contener puntos ni comas.',
            'password.required'=>'Ingrese la contraseña por favor.'
        ]);
    }

  
    private function checkAccountStatus($user)
    {
        if (!$user->active) {
            return $this->errorResponse(
                'Cuenta inhabilitada o registro eliminado. Contacte con soporte.',
                403
            );
        }
        return null;
    }








    private function incrementLoginAttempts($user)
    {
        $user->increment('intentos');

        // Opcional: Bloquear cuenta después de X intentos
        if ($user->intentos >= 5) {
            $user->update(['blocked_until' => now()->addMinutes(15)]);
        }
    }


    private function errorResponse(string $message, int $code)
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $code);
    }

    private function enviarSMSyEmailDispositivoInusual($email, $celular, $clienteId, $req) : int
    {
        $codigo = random_int(1000, 9999);

        $datosEmail = [
            'code' => $codigo,
            'device' => $req->device,
            'model' => $req->model,
            'ip' => $req->ip(),
        ];

        $mensaje = "Utiliza el código ". $codigo." para confirmar tu dispositivo en Blupy.";
        $numeroTelefonoWa = '595' . substr($celular, 1);

        DispositivoInusualJob::dispatch($celular, $mensaje, $email, $codigo, $datosEmail, $numeroTelefonoWa)->onConnection('database');

        // Guardar validación
        $validacion = Validacion::create([
            'codigo' => $codigo,
            'forma' => 0,
            'celular' => $celular,
            'email' => $email,
            'cliente_id' => $clienteId,
            'origen' => 'dispositivo',
        ]);

        return $validacion->id;
    }

    private function userInformacion($cliente, string $token, bool $esAdicional)
    {
        return [
            'aceptado'=>$cliente->aceptado,
            'adicional' => $esAdicional,
            'cliid' => $cliente->cliid,
            'name' => $cliente->user->name,
            'primerNombre' => $cliente->nombre_primero,
            'nombres' => trim($cliente->nombre_primero . ' ' . $cliente->nombre_segundo),
            'apellidos' => trim($cliente->apellido_primero . ' ' . $cliente->apellido_segundo),
            'cedula' => $cliente->cedula,
            'fechaNacimiento' => $cliente->fecha_nacimiento,
            'email' => $cliente->user->email,
            'telefono' => $cliente->celular,
            'celular' => $cliente->celular,
            'solicitudCredito' => $cliente->solicitud_credito,
            'solicitudCompletada' => $cliente->direccion_completado,
            'funcionario' => $cliente->funcionario,
            'aso' => $cliente->asofarma,
            'vendedorId' => $cliente->user->vendedor_id,
            'tokenType' => 'Bearer',
            'token' => 'Bearer ' . $token,
            'tokenRaw' => $token,
            'changepass' => $cliente->user->changepass,
            'digital' => $cliente->digital,
        ];
    }

    private function ocultarParcialmenteTelefono($phoneNumber) {
        if (strlen($phoneNumber) < 7) {
            return $phoneNumber;
        }
        $obfuscatedPhoneNumber = substr($phoneNumber, 0, 3) . str_repeat('*', strlen($phoneNumber) - 6) . substr($phoneNumber, -2);
        return $obfuscatedPhoneNumber;
    }



    public function logOut(Request $req){
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'success'=>true,
            'message'=>'out'
        ]);

    }
}
