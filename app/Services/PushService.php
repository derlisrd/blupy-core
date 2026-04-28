<?php

namespace App\Services;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;

class PushService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
    }

    public function sendPushMulti(array $deviceTokens, array $data = [], string $title, string $body){
        $messaging = app(Messaging::class);
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

    public function sendPushNotification($token, $title, $body, $data = [])
    {
        $messaging = app(Messaging::class); // ← Obtener la instancia de Messaging
        $message = CloudMessage::fromArray([
            'token' => $token,
            'notification' => [
                'title' => $title,
                'body'  => $body,
            ],
            'data' => $data,
        ]);

        try {
            $messaging->send($message); // ← $this->messaging

        } catch (MessagingException $e) {
            throw new \Exception('Error al enviar la notificación: ' . $e->getMessage());
        }
    }
}
