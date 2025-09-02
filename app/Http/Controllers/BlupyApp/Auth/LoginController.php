<?php

namespace App\Http\Controllers\BlupyApp\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\DispositivoInusualJob;
use App\Models\Adicional;
use App\Models\Cliente;
use App\Models\Validacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            $rateLimitResponse = $this->checkRateLimit($req->ip());
            if ($rateLimitResponse) 
                return $rateLimitResponse;
            

            // 3. Buscar cliente
            $cliente = Cliente::with(['user'])->where('cedula', $req->cedula)->first();
            if (!$cliente) 
                return $this->errorResponse('Usuario no existe. Regístrese', 404);
            

            // 4. Verificar estado de cuenta
            $accountStatusResponse = $this->checkAccountStatus($cliente->user);
            if ($accountStatusResponse) 
                return $accountStatusResponse;
            

            // 5. Intentar autenticación
            $credentials = ['email' => $cliente->user->email, 'password' => $req->password];
            $token = JWTAuth::attempt($credentials);

            if (!$token) {
                $this->incrementLoginAttempts($cliente->user);
                return $this->errorResponse('Credenciales incorrectas', 401);
            }
            

            // 6. Verificar dispositivo de confianza (solo para rol 0)
            if ($cliente->user->rol === 0) {
                $deviceVerificationResponse = $this->verifyTrustedDevice($cliente, $req);
                if ($deviceVerificationResponse) {
                    return $deviceVerificationResponse;
                }
            }
            $cliente->user->update([
                'intentos' => 0,
                'ultimo_ingreso' => now(),
                'version' => $req->version
            ]);
    
            // Verificar si es adicional
            $esAdicional = Adicional::where('cedula', $req->cedula)->exists();
    
            return response()->json([
                'success' => true,
                'message' => 'Ingreso exitoso',
                'id' => null,
                'results' => $this->userInformacion($cliente, $token, $esAdicional)
            ]);
            
        } catch (\Throwable $th) {
            Log::error('Login error', [
                'cedula' => $req->cedula ?? 'N/A',
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            return $this->errorResponse('Error interno del servidor', 500);
        }
    }

    // Métodos auxiliares para mejorar la legibilidad y mantenibilidad

    private function validateLoginRequest(Request $req)
    {
        return Validator::make($req->all(), [
            'cedula' => 'required|string',
            'password' => 'required|string|min:6',
            'desktop' => 'nullable|boolean',
            'web' => 'nullable|boolean',
            'device' => 'nullable|string',
            'devicetoken' => 'nullable|string',
            'version' => 'nullable|string'
        ],[
            'cedula.required' => 'Ingrese la cédula por favor.',
            'password.required'=>'Ingrese la contraseña por favor.'
        ]);
    }

    private function checkRateLimit(string $ip)
    {
        $rateKey = "login:$ip";

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return $this->errorResponse(
                'Demasiadas peticiones. Espere 1 minuto.',
                429
            );
        }

        RateLimiter::hit($rateKey, 60);
        return null;
    }

  
    private function checkAccountStatus($user)
    {
        if (!$user->active) {
            return $this->errorResponse(
                'Cuenta inhabilitada. Contacte con soporte.',
                403
            );
        }
        return null;
    }


    private function verifyTrustedDevice(Cliente $cliente, Request $req)
    {
        $trustedDevice = $cliente->user->devices()
            ->where([
                'desktop' => $req->desktop ?? false,
                'web' => $req->web ?? false,
                'device' => $req->device ?? '',
                'devicetoken' => $req->devicetoken ?? ''
            ])
            ->first();

        if (!$trustedDevice) {
            return $this->handleNewDevice($cliente, $req);
        }

        $this->updateDeviceInfo($trustedDevice, $req);
        return null;
    }

    private function handleNewDevice(Cliente $cliente, Request $req)
    {
        $maskedEmail = $this->maskEmail($cliente->user->email);
        $maskedPhone = $this->ocultarParcialmenteTelefono($cliente->celular);

        $validationId = $this->enviarSMSyEmaildispositivoInusual(
            $cliente->user->email,
            $cliente->celular,
            $cliente->id,
            $req
        );

        return response()->json([
            'success' => true,
            'results' => null,
            'id' => $validationId,
            'message' => "Valida el dispositivo. PIN enviado a $maskedPhone y al correo $maskedEmail"
        ]);
    }

    private function updateDeviceInfo($device, Request $req)
    {
        $device->update([
            'version' => $req->version,
            'ip' => $req->ip(),
            'last_used_at' => now()
        ]);
    }


    private function incrementLoginAttempts($user)
    {
        $user->increment('intentos');

        // Opcional: Bloquear cuenta después de X intentos
        if ($user->intentos >= 5) {
            $user->update(['blocked_until' => now()->addMinutes(15)]);
        }
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $username = $parts[0];
        $domain = $parts[1];

        $maskedUsername = substr($username, 0, 2) . str_repeat('*', strlen($username) - 2);
        return $maskedUsername . '@' . $domain;
    }

    private function errorResponse(string $message, int $code)
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $code);
    }

    private function enviarSMSyEmailDispositivoInusual($email, $celular, $clienteId, $req)
    {
        $codigo = random_int(1000, 9999);

        $datosEmail = [
            'code' => $codigo,
            'device' => $req->device,
            'model' => $req->model,
            'ip' => $req->ip(),
        ];

        $mensaje = "Utiliza el código _". $codigo."_ para confirmar tu dispositivo en Blupy.";
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
