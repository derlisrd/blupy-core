<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Factory;

class PushService
{
    private Messaging $messaging;
    private array $invalidTokens = [];
    private array $validTokens = [];

    /**
     * Constructor: inicializa la conexión con Firebase una sola vez
     */
    public function __construct()
    {
        $this->initializeFirebase();
    }

    /**
     * Inicializa la conexión con Firebase
     */
    private function initializeFirebase(): void
    {
        $factory = (new Factory)->withServiceAccount(storage_path('app/firebase.json'));
        $this->messaging = $factory->createMessaging();
    }

    public function sendPushMulti(array $deviceTokens, string $title, string $body, array $data = [])
    {
        $factory = (new Factory)->withServiceAccount(storage_path('/app/firebase.json'));
        $messaging = $factory->createMessaging();
        $message = CloudMessage::new()->withNotification([
            'title' => $title,
            'body' => $body,
        ])->withData($data);


        try {
            $messaging->sendMulticast($message, $deviceTokens);
        } catch (MessagingException $e) {
            throw new \Exception('Error al enviar la notificación: ' . $e->getMessage());
        }
    }

    public function sendPushNotification(
        string $token,
        string $title,
        string $body,
        array $data = []
    ): array {
        if (empty($token)) {
            throw new \InvalidArgumentException('El token del dispositivo es requerido');
        }

        $message = CloudMessage::new()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData($data)
            ->withToken($token);

        try {
            $result = $this->messaging->send($message);

            return [
                'success' => true,
                'message_id' => $result,
                'token' => $token
            ];
        } catch (MessagingException $e) {
            Log::error('Error en envío individual FCM', [
                'error' => $e->getMessage(),
                'token' => $token
            ]);

            // Si el token es inválido, lo registramos
            if ($this->isInvalidTokenError($e)) {
                $this->invalidTokens[] = $token;
            }

            throw new \RuntimeException('Error al enviar la notificación: ' . $e->getMessage(), 0, $e);
        }
    }

    private function processMulticastResult($result): void
    {
        $this->invalidTokens = [];

        foreach ($result->failures() as $failure) {
            $error = $failure->error();
            $token = $failure->target()->value();

            if ($this->isInvalidTokenError($error)) {
                $this->invalidTokens[] = $token;
                Log::warning('Token FCM inválido detectado', ['token' => $token]);
            }
        }
    }


    private function isInvalidTokenError($error): bool
    {
        $errorMessage = $error->getMessage();
        $invalidErrors = [
            'NOT_FOUND',
            'INVALID_ARGUMENT',
            'UNREGISTERED',
            'INVALID_REGISTRATION_TOKEN'
        ];

        foreach ($invalidErrors as $invalidError) {
            if (strpos($errorMessage, $invalidError) !== false) {
                return true;
            }
        }

        return false;
    }
}
