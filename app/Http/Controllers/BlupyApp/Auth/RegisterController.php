<?php

namespace App\Http\Controllers\BlupyApp\Auth;

use App\Http\Controllers\Controller;
use App\Models\Adicional;
use App\Models\Adjunto;
use App\Models\Cliente;
use App\Models\Device;
use App\Models\TerminosAceptados;
use App\Models\User;
use App\Services\FarmaService;
use App\Services\SupabaseService;
use App\Traits\Helpers;
use App\Traits\RegisterTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class RegisterController extends Controller
{
    use RegisterTraits, Helpers;

    public function register(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), trans('validation.auth.login'), trans('validation.auth.login.messages'));

            if ($validator->fails()) return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

            $ip = $req->ip();
            $rateKey = "login:$ip";

            if (RateLimiter::tooManyAttempts($rateKey, 5))
                return response()->json(['success' => false, 'message' => 'Demasiadas peticiones. Espere 1 minuto.'], 429);

            RateLimiter::hit($rateKey, 60);

            /// aqui debe subir imagenes en 2do plano para no bloquear todo
            $imagesData = [];
            $imageFields = [
                'fotocedulafrente' => 'frente',
                'fotoceduladorso' => 'dorso',
                'fotoselfie' => 'selfie'
            ];

            foreach ($imageFields as $key => $value) {
                //string $imagenBase64, string $imageName, string $path
                //SubirImages2doPlanoJob::dispatch($req->$key, ($req->cedula . '_' . $value), 'clientes')->onConnection('database');
                $this->subirBase64ToWebp($req->$key, ($req->cedula . '_' . $value), 'clientes');
                //formato '.webp'
                $imagesData[$value] = $req->cedula . '_' . $value . '.webp';
            }


            // 4. Obtener datos adicionales de farma
            $userInfoDatosFarma = $this->getDataInfoFarma($req->cedula);

            // 5. Ejecutar registro en transacción
            DB::beginTransaction();

            $registroResult = $this->ejecutarRegistro($req, $imagesData, $userInfoDatosFarma);

            if (!$registroResult['success']) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => $registroResult['message']], 500);
            }

            DB::commit();

            // 6. Procesos post-registro (async si es posible)
            $this->enviarFotosAInfinita($req, $registroResult['cliente']);

            $results = $this->userInformacion($registroResult['cliente'], $registroResult['token'], $userInfoDatosFarma['esAdicional']);

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado correctamente',
                'results' => $results
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            SupabaseService::LOG('Error Registro. Ci: ' . $req->cedula . ' tel: ' . $req->celular, $th->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno del servidor. BCR800'], 500);
        }
    }






    private function getDataInfoFarma(string $cedula): array
    {
        $adicional = Adicional::where('cedula', $cedula)->first();
        $esAdicional = (bool) $adicional;
        //$clienteFarma = $this->clienteFarma($cedula);
        $farmaResponse = (new FarmaService())->esAlianzaOFuncionario($cedula);
        $farmaData = $farmaResponse['data'];
        $direccionCompletado = 0;
        $asofarma = 0;
        $funcionario = 0;
        if ($farmaData && isset($farmaData['result'])) {
            $result = $farmaData['result'];
            if ($result['alianza'] === true) {
                $asofarma = 1;
            }
            if ($result['funcionario'] === true) {
                $funcionario = 1;
            }
        }
        $direccionCompletado = ($funcionario == 1 || $esAdicional || $asofarma == 1) ? 1 : 0;

        return [
            'esAdicional' => $esAdicional,
            'asofarma' => $asofarma,
            'funcionario' => $funcionario,
            'direccionCompletado' => $direccionCompletado
        ];
    }

    private function ejecutarRegistro(Request $req, array $images, array $additionalData): array
    {
        try {
            // Preparar datos del cliente
            $nombres = $this->separarNombres($req->nombres);
            $apellidos = $this->separarNombres($req->apellidos);

            $clienteData = [
                'cedula' => $req->cedula,
                'foto_ci_frente' => $images['frente'],
                'foto_ci_dorso' => $images['dorso'],
                'selfie' => $images['selfie'],
                'nombre_primero' => $nombres[0],
                'nombre_segundo' => $nombres[1] ?? null,
                'apellido_primero' => $apellidos[0],
                'apellido_segundo' => $apellidos[1] ?? null,
                'fecha_nacimiento' => $req->fecha_nacimiento,
                'celular' => $req->celular,
                'email' => $req->email,
                'funcionario' => $additionalData['funcionario'],
                'linea_farma' => null,
                'asofarma' => $additionalData['asofarma'],
                'importe_credito_farma' => 0,
                'direccion_completado' => $additionalData['direccionCompletado'],
                'cliid' => 0,
                'solicitud_credito' => 0,
                'aceptado'=>true
            ];
            // Registrar en sistema externo (Infinita)
            $infinitaResult = $this->registrarInfinita((object) $clienteData);
            if (!$infinitaResult['register']) {
                return [
                    'success' => false,
                    'message' => 'Error al registrar en sistema externo. Intente más tarde.'
                ];
            }

            $clienteData['cliid'] = $infinitaResult['cliId'];
            unset($clienteData['email']); // Email va en la tabla users

            $cliente = Cliente::create($clienteData);
            $telefono = $req->device . $req->model;
            TerminosAceptados::create([
                'cliente_id' => $cliente->id,
                'cedula' => $req->cedula,
                'telefono' => $telefono ?? null,
                'termino_tipo' => 'Terminos y Condiciones del usuario',
                'version' => 'v1.0',
                'enlace' => 'https://core.blupy.com.py/terminos',
                'aceptado' => 1,
                'aceptado_fecha' => now()
            ]);

            $user = User::create([
                'cliente_id' => $cliente->id,
                'name' => trim($req->nombres . ' ' . $req->apellidos),
                'email' => $req->email,
                'password' => Hash::make($req->password),
                'vendedor_id' => $req->vendedor_id ?? null,
                'email_verified_at' => now(),
            ]);
            // registrar dispositivo
            Device::create([
                'user_id' => $user->id,
                'notitoken' => $req->notitoken ?? null,
                'os' => $req->os ?? null,
                'devicetoken' => $req->devicetoken ?? null,
                'version' => $req->version ?? null,
                'device' => $req->device ?? null,
                'model' => $req->model ?? null,
                'ip' => $req->ip(),
                'web' => $req->web ?? false,
                'desktop' => $req->desktop ?? false
            ]);
            $this->crearAdjuntos($cliente->id, $images);
            $token = JWTAuth::fromUser($user);
            return [
                'success' => true,
                'cliente' => $cliente,
                'user' => $user,
                'token' => $token
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error en el proceso de registro: ' . $e->getMessage()
            ];
        }
    }


    private function crearAdjuntos(int $clienteId, array $images): void
    {
        $attachments = [
            ['image' => $images['frente'], 'tipo' => 'cedula_frente'],
            ['image' => $images['dorso'], 'tipo' => 'cedula_dorso'],
            ['image' => $images['selfie'], 'tipo' => 'selfie']
        ];

        foreach ($attachments as $attachment) {
            Adjunto::create([
                'cliente_id' => $clienteId,
                'nombre' => $attachment['image'],
                'tipo' => $attachment['tipo'],
                'path' => 'clientes',
                'url' => 'clientes/' . $attachment['image'],
                'created_at' => now()
            ]);
        }
    }

    private function enviarFotosAInfinita(Request $req): void
    {
        // Estas operaciones podrían ser asíncronas (jobs/queues)
        try {
            $this->enviarFotoCedulaInfinita($req->cedula, $req->fotocedulafrente, $req->fotoceduladorso);
            $this->enviarSelfieInfinita($req->cedula, $req->fotoselfie);

            // Opcional: Enviar email de bienvenida
            // $this->sendWelcomeEmail($cliente);

            // Opcional: Crear logs de auditoría
            // $this->logUserRegistration($cliente);

        } catch (\Exception $e) {
            // Log pero no fallar el registro
        }
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

    private function subirBase64ToWebp(string $imagenBase64, string $imageName, string $path)
    {
        try {
            // Validar que sea una imagen base64 válida
            if (!preg_match('/^data:image\/(\w+);base64,/', $imagenBase64, $matches)) {
                throw new \Exception("Formato base64 no válido");
            }

            $originalExtension = strtolower($matches[1]);

            // Validar que la extensión sea permitida
            $allowedExtensions = ['jpg', 'jpeg', 'png'];
            if (!in_array($originalExtension, $allowedExtensions)) {
                throw new \Exception("Formato de imagen no permitido: {$originalExtension}");
            }

            // Remover el prefijo data:image/...;base64, 
            $imageData = substr($imagenBase64, strpos($imagenBase64, ',') + 1);

            // Decodificar la imagen base64
            $decodedImage = base64_decode($imageData);

            if ($decodedImage === false) {
                throw new \Exception("Error al decodificar la imagen base64");
            }

            // Nombre del archivo con extensión .webp
            $filename = $imageName . '.webp';

            // Crear el directorio si no existe
            $fullDirectory = public_path($path);
            if (!file_exists($fullDirectory)) {
                mkdir($fullDirectory, 0755, true);
            }

            // Ruta completa del archivo
            $publicPath = public_path($path . '/' . $filename);

            $manager = new ImageManager(new Driver());

            // Procesar y convertir la imagen a WebP usando Intervention Image v3
            $imageProcessor = $manager->read($decodedImage);

            // Redimensionar manteniendo proporción (máximo 800 en cualquier lado)
            $imageProcessor->scaleDown(width: 800, height: 800);

            // Guardar la imagen procesada directamente como WebP
            $imageProcessor->toWebp(quality: 90)->save($publicPath);

            // Retornar solo el nombre del archivo (o la ruta relativa)
            return $filename;
        } catch (\Throwable $th) {
            SupabaseService::LOG('Error al subir imagen base64 a WebP: ' . $imageName, $th->getMessage());
            throw $th;
            //return null;
        }
    }
}
