<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Private\CuentasController as CuentasPrivate;
use App\Models\Adicional;
use App\Models\Cliente;
use App\Models\Device;
use App\Models\SolicitudCredito;
use App\Models\User;
use App\Models\Validacion;
use App\Services\EmailService;
use App\Services\SupabaseService;
use App\Traits\Helpers;
use App\Traits\RegisterTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;


class AuthController extends Controller
{
    use RegisterTraits, Helpers;

    /* ----------------------------
    REGISTRO
    ----------------------------*/
    public function register(Request $req)
    {

        $validator = Validator::make($req->all(), trans('validation.auth.register'), trans('validation.auth.register.messages'));
        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        try {
            // consulta si tiene ficha en farma
            $clienteFarma = $this->clienteFarma($req->cedula);

            // guardar cedula en nuestros servidores las fotos estan en base64
            $fotoCiFrente = $this->guardarCedulaImagenBase64($req->fotocedulafrente, $req->cedula . '_frente', 1);
            $fotoCiDorso = $this->guardarCedulaImagenBase64($req->fotocedulafrente, $req->cedula . '_dorso', 1);

            if ($fotoCiDorso == null || $fotoCiFrente == null)
                return response()->json(['success' => false, 'message' => 'Hay un error con la imagen de la cedula'], 400);


            DB::beginTransaction();

            $adicional = Adicional::whereCedula($req->cedula)->first();
            $esAdicional = $adicional ? true : false;

            $nombres = $this->separarNombres($req->nombres);
            $apellidos = $this->separarNombres($req->apellidos);

            $direccionCompletado = 0;
            if ($clienteFarma->completado == 1 || $esAdicional) {
                $direccionCompletado = 1;
            }

            $datosCliente = [
                'cedula' => $req->cedula,
                'foto_ci_frente' => $fotoCiFrente,
                'foto_ci_dorso' => $fotoCiDorso,
                'nombre_primero' => $nombres[0],
                'nombre_segundo' => $nombres[1],
                'apellido_primero' => $apellidos[0],
                'apellido_segundo' => $apellidos[1],
                'fecha_nacimiento' => $req->fecha_nacimiento,
                'cedula' => $req->cedula,
                'celular' => $req->celular,
                'email' => $req->email,
                'funcionario' => $clienteFarma->funcionario,
                'linea_farma' => $clienteFarma->lineaFarma,
                'asofarma' => $clienteFarma->asofarma,
                'importe_credito_farma' => $clienteFarma->credito,
                'direccion_completado' => $direccionCompletado,
                'cliid' => 0,
                'solicitud_credito' => 0
            ];

            // ver si tiene ficha en infinita, sino lo crea
            $resRegistrarInfinita = (object)  $this->registrarInfinita((object) $datosCliente);

            if (!$resRegistrarInfinita->register) {
                return response()->json(['success' => false, 'message' => 'Intente mas adelante. Error infinita.'], 500);
            }

            $codigoSolicitud = $resRegistrarInfinita->solicitudId;



            $datosCliente['cliid'] = $resRegistrarInfinita->cliId;

            unset($datosCliente['email']);
            $cliente = Cliente::create($datosCliente);

            $user = User::create([
                'cliente_id' => $cliente->id,
                'name' => $req->nombres . ' ' . $req->apellidos,
                'email' => $req->email,
                'password' => bcrypt($req->password),
                'vendedor_id' => $req->vendedor_id,
            ]);
            Device::create([
                'user_id' => $user->id,
                'notitoken' => $req->notitoken,
                'os' => $req->os,
                'devicetoken' => $req->devicetoken,
                'version' => $req->version,
                'device' => $req->device,
                'model' => $req->model,
                'ip' => $req->ip(),
                'version' => $req->version,
                'web' => $req->web ?? 0,
                'desktop' => $req->desktop ?? 0,
            ]);

            SolicitudCredito::create([
                'codigo' => $codigoSolicitud,
                'estado' => 'Vigente',
                'cliente_id' => $cliente->id,
                'estado_id' => 7,
                'tipo' => 0
            ]);

            DB::commit();
            // enviar foto de cedula a infinita
            $this->enviarFotoCedulaInfinita($req->cedula, $req->fotocedulafrente, $req->fotoceduladorso);
            $this->enviarEmailRegistro($req->email, $nombres[0]);

            $token = JWTAuth::fromUser($user);
            $tarjetasConsultas = new CuentasPrivate();
            $tarjetas = $tarjetasConsultas->tarjetas($req->cedula, 0, '');
            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado correctamente',
                'results' => $this->userInfo($cliente, $token, $tarjetas, $esAdicional)
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            SupabaseService::LOG('register', $th);
            return response()->json(['success' => false, 'message' => 'Error de servidor'], 500);
        }
    }




    /* ----------------------------
    INGRESO O LOGIN
    ----------------------------*/

    public function login(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), trans('validation.auth.login'), trans('validation.auth.login.messages'));

            if ($validator->fails()) return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

            $ip = $req->ip();
            $executed = RateLimiter::attempt($ip, $perTwoMinutes = 6, function () {});
            if (!$executed) return response()->json(['success' => false, 'message' => 'Demasiadas peticiones. Espere 1 minuto.'], 500);

            $cedula = $req->cedula;
            $password = $req->password;
            $cliente = Cliente::where('cedula', $cedula)->first();

            if (!$cliente) return response()->json(['success' => false, 'message' => 'Usuario no existe. Registrese'], 404);

            if($req->version !== '2.6.4'){
                return response()->json(['success' => false, 'message' => 'Por favor actualice su app para mayor seguridad'], 400);
            }


            $user =  $cliente->user;

            if ($user->active == 0) return response()->json(['success' => false, 'message' => 'Cuenta inhabilitada o bloqueada temporalmente. Contacte con soporte.'], 400);

            $adicional = Adicional::whereCedula($req->cedula)->first();
            $esAdicional = $adicional ? true : false;

            $credentials = ['email' => $user->email, 'password' => $password];
            $token = JWTAuth::attempt($credentials);
            if ($token) {
                if ($user->rol == 0) {
                    $dispositoDeConfianza = Device::where('user_id', $user->id)
                        ->where('desktop', $req->desktop)
                        ->where('web', $req->web)
                        ->where('device', $req->device)
                        ->where('devicetoken', $req->devicetoken)
                        ->first();
                    if (!$dispositoDeConfianza) {
                        SupabaseService::LOG('newDevice', $req->cedula);
                        $pistaEmail =  $user->email; //$this->ocultarParcialmenteEmail($user->email);
                        $idValidacion = $this->enviarEmaildispositivoInusual($user->email, $cliente->id, $req);
                        return response()->json([
                            'success' => true,
                            'results' => null,
                            'id' => $idValidacion,
                            'message' => 'Código enviado a ' . $pistaEmail . ' , puede tardar unos minutos. Revisa también el spam o correo no deseado.'
                        ]);
                    }
                }

                $version = $req->version ?? null;
                $user->update(['intentos' => 0, 'ultimo_ingreso' =>  date('Y-m-d H:i:s'), 'version' => $version]);
                $dispositoDeConfianza->update([ 'version' => $version]);


                $tarjetasConsultas = new CuentasPrivate();
                $tarjetas = $tarjetasConsultas->tarjetas($cliente->cedula, $cliente->extranjero, $cliente->codigo_farma ?? '');
                return response()->json(
                    [
                        'success' => true,
                        'message' => 'Ha ingresado',
                        'id' => null,
                        'results' => $this->userInfo($cliente, $token, $tarjetas, $esAdicional)
                    ]
                );
            }


            $user->update(['intentos' => $user->intentos + 1]);
            return response()->json([
                'success' => false,
                'message' => 'Error. La contraseña es incorrecta.'
            ], 401);
        } catch (\Throwable $th) {
            SupabaseService::LOG('login_core', $th->getMessage());
            throw $th;
            return response()->json(['success' => false, 'message' => "Error de servidor"], 500);
        }
    }




    /* ----------------------------
    CHECK TOKEN VALIDO
    ----------------------------*/

    public function checkToken(Request $req)
    {
        try {
            JWTAuth::check(JWTAuth::getToken());
            $tokenHeader = $req->header('Authorization');
            $token = str_replace("Bearer ", "", $tokenHeader);
            $user = $req->user();
            $cliente = $user->cliente;
            $tarjetasConsultas = new CuentasPrivate();
            $adicional = Adicional::whereCedula($cliente->cedula)->first();
            $esAdicional = $adicional ? true : false;
            $tarjetas = $tarjetasConsultas->tarjetas($cliente->cedula, 0, '');
            return response()->json([
                'success' => true,
                'message' => 'Valido',
                'results' => $this->userInfo($cliente, $token, $tarjetas, $esAdicional)
            ]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => 'Error de servidor'], 500);
        }
    }



    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['success' => true]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => 'Error de servidor'], 500);
        }
    }




    public function refreshToken()
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            return response()->json(['token' => $token]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => 'Error de servidor'], 500);
        }
    }


    public function eliminarCuenta(Request $req)
    {
        return response()->json(['success' => true, 'message' => 'Su cuenta ha sido desactivada correctamente']);
    }

    private function enviarEmaildispositivoInusual($email, $clienteId, $req)
    {
        $randomNumber = random_int(100000, 999999);
        $emailService = new EmailService();
        $datos = [
            'code' => $randomNumber,
            'device' => $req->device,
            'model' => $req->model,
            'ip' => $req->ip()
        ];
        $emailService->enviarEmail($email, "[" . $randomNumber . "]Blupy confirmar dispositivo", 'email.validarDispositivo', $datos);
        $validacion = Validacion::create(['codigo' => $randomNumber, 'forma' => 0, 'email' => $email, 'cliente_id' => $clienteId, 'origen' => 'dispositivo']);
        return $validacion->id;
    }
}
