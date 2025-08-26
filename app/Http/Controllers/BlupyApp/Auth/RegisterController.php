<?php

namespace App\Http\Controllers\BlupyApp\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SubirImages2doPlanoJob;
use App\Models\Adicional;
use App\Models\Adjunto;
use App\Models\Cliente;
use App\Models\Device;
use App\Models\User;
use App\Services\FarmaService;
use App\Traits\Helpers;
use App\Traits\RegisterTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
            // 1. Validación inicial
            $validator = $this->validateRegistrationRequest($req);
            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first(), 400);
            }

            // 2. Verificar si el usuario ya existe
            $existingUser = $this->checkExistingUser($req->cedula, $req->email);
            if ($existingUser) {
                return $existingUser;
            }

            // 3. Procesar y validar imágenes

            /// aqui debe subir imagenes en 2do plano para no bloquear todo
            $imagesData = [];
            $imageFields = [
                'fotocedulafrente' => 'frente',
                'fotoceduladorso' => 'dorso',
                'fotoselfie' => 'selfie'
            ];

            foreach ($imageFields as $key => $value) {
                $imagePath = 'clientes/' . $req->cedula . '_' . $value;
                //string $imagenBase64, string $imageName, string $path
                SubirImages2doPlanoJob::dispatch($req->$key, ($req->cedula . '_' . $value), 'clientes')->onConnection('database');
                $imagesData[$value] = $imagePath;
            }


            // 4. Obtener datos adicionales de farma
            $userInfoDatosFarma = $this->getDataInfoFarma($req->cedula);

            // 5. Ejecutar registro en transacción
            DB::beginTransaction();

            $registroResult = $this->ejecutarRegistro($req, $imagesData, $userInfoDatosFarma);

            if (!$registroResult['success']) {
                DB::rollBack();
                return $this->errorResponse($registroResult['message'], 500);
            }

            DB::commit();

            // 6. Procesos post-registro (async si es posible)
            $this->enviarFotosAInfinita($req, $registroResult['cliente']);

            return $this->successResponse(
                'Usuario registrado correctamente',
                $this->userInformacion(
                    $registroResult['cliente'],
                    $registroResult['token'],
                    $userInfoDatosFarma['esAdicional']
                ),
                201
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->logRegistrationError($th, $req);
            return $this->errorResponse('Error interno del servidor', 500);
        }
    }

    // Métodos auxiliares para mejor organización

    private function validateRegistrationRequest(Request $req)
    {
        return Validator::make($req->all(), [
            'cedula' => 'required|string|unique:clientes,cedula',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'fecha_nacimiento' => 'required|date|before:today',
            'celular' => 'required|string|max:20',
            'fotocedulafrente' => 'required|string', // base64
            'fotoceduladorso' => 'required|string', // base64
            'fotoselfie' => 'required|string', // base64
            'vendedor_id' => 'nullable|integer|exists:vendedores,id',
            'notitoken' => 'nullable|string',
            'os' => 'sometimes|string',
            'devicetoken' => 'sometimes|string',
            'version' => 'sometimes|string',
            'device' => 'sometimes|string',
            'model' => 'sometimes|string',
            'web' => 'sometimes|boolean',
            'desktop' => 'sometimes|boolean'
        ]);
    }

    private function checkExistingUser(string $cedula, string $email)
    {
        $existingCliente = Cliente::where('cedula', $cedula)->first();
        if ($existingCliente) {
            return $this->errorResponse('La cédula ya está registrada', 409);
        }

        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            return $this->errorResponse('El email ya está registrado', 409);
        }

        return null;
    }



    private function getDataInfoFarma(string $cedula): array
    {
        $adicional = Adicional::where('cedula', $cedula)->first();
        $esAdicional = (bool) $adicional;
        //$clienteFarma = $this->clienteFarma($cedula);
        $farmaService = new FarmaService();
        $farmaResponse = $farmaService->esAlianzaOFuncionario($cedula);
        $farmaData = (object) $farmaResponse['data'];
        $direccionCompletado = 0;
        $asofarma = 0;
        $funcionario = 0;
        if (property_exists($farmaData, 'result')) {
            $result = $farmaData->result;
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
            $user = $this->createUserRecord($req, $cliente->id);
            $this->createDeviceRecord($req, $user->id);
            $this->createAttachmentRecords($cliente->id, $images);

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


    private function createClienteRecord(array $clienteData, int $cliId): Cliente
    {
        $clienteData['cliid'] = $cliId;
        unset($clienteData['email']); // Email va en la tabla users

        return Cliente::create($clienteData);
    }

    private function createUserRecord(Request $req, int $clienteId): User
    {
        return User::create([
            'cliente_id' => $clienteId,
            'name' => trim($req->nombres . ' ' . $req->apellidos),
            'email' => $req->email,
            'password' => Hash::make($req->password),
            'vendedor_id' => $req->vendedor_id ?? null,
            'email_verified_at' => null, // Considerar verificación por email
        ]);
    }

    private function createDeviceRecord(Request $req, int $userId): void
    {
        Device::create([
            'user_id' => $userId,
            'notitoken' => $req->notitoken ?? null,
            'os' => $req->os ?? null,
            'devicetoken' => $req->devicetoken ?? null,
            'version' => $req->version ?? null,
            'device' => $req->device ?? null,
            'model' => $req->model ?? null,
            'ip' => $req->ip(),
            'web' => $req->web ?? false,
            'desktop' => $req->desktop ?? false,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    private function createAttachmentRecords(int $clienteId, array $images): void
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
            Log::warning('Error en tareas post-registro', [
                'cedula' => $req->cedula,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function logRegistrationError(\Throwable $th, Request $req): void
    {
        Log::error('Error en registro de usuario', [
            'cedula' => $req->cedula ?? 'N/A',
            'email' => $req->email ?? 'N/A',
            'error' => $th->getMessage(),
            'file' => $th->getFile(),
            'line' => $th->getLine(),
            'trace' => $th->getTraceAsString(),
            'request_data' => $req->except(['password', 'password_confirmation', 'fotocedulafrente', 'fotoceduladorso', 'fotoselfie'])
        ]);
    }

    private function errorResponse(string $message, int $code)
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $code);
    }

    private function successResponse(string $message, $data = null, int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'results' => $data
        ], $code);
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
            Log::error('Error al subir imagen base64 a WebP: ' . $th->getMessage(), [
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'imageName' => $imageName,
                'directory_base' => $path,
                'trace' => $th->getTraceAsString(),
            ]);
            return null;
        }
    }
}
