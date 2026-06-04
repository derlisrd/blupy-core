<?php

namespace App\Services;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Factory;
class PushService
{
    /**
     * Create a new class instance.
     */
    
    public function __construct()
    {
        
    }


    public function sendPushMulti(array $deviceTokens, string $title, string $body, array $data = []){
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

    public function sendPushNotification(string $token, string $title, string $body, $data = [])
    {

        $factory = (new Factory)->withServiceAccount(storage_path('/app/firebase.json'));
        $messaging = $factory->createMessaging();
        //$messaging = app(Messaging::class); // ← Obtener la instancia de Messaging
        $message = CloudMessage::fromArray([
            'token' => 'dA1EGxTwSbaw0vjnGPKzji:APA91bE2nhpDlAcSQKNaVp7gvcMrQJC8DWza-xLXwJbHQunAq8AYLuBTnUDIUw6FWTNdKxwyKMVtyCbitGiQ1v5DKpMpkERsvH2oHjI9AwPy6OKRkknQSeEcQZ0Ge8_WMuitkun4PX0s',
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
