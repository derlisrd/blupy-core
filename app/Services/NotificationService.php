<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Firebase\JWT\JWT;

class NotificationService
{

    public function __construct(){

    }


    public function sendNotification($title, $body, $tokenDevice){
        $payload = [
            'aps' => [
                'alert' => [
                    'title' => 'Titulo',
                    'body' => 'Body',
                ],
                'sound' => 'default',
            ],
        ];
        $response = Http::withHeaders([
            'content-type' => 'application/json',
            'apns-topic' => 'py.com.blupy',
            'authorization' => "bearer {$this->generarTokenAutorizacion()}"
        ])->post(
            "https://api.push.apple.com/3/device/$tokenDevice",
            json_encode($payload)
        );

        if ($response->successful()) {
            return $response->body() ?: 'Notificación enviada correctamente';
        }

        return $response->throw();
    }

    private function generarTokenAutorizacion()
    {
        // Cargar el archivo de clave privada .p8
        $privateKey = file_get_contents('ruta/a/tu/archivo.p8');

        // Crear el payload
        $payload = [
            'iss' => '', // Tu Team ID de Apple Developer
            'iat' => time(), // Tiempo actual en segundos
        ];

        // Opciones de la cabecera
        $header = [
            'alg' => 'ES256',
            'kid' => '', // Tu Key ID
        ];

        // Generar el token
        $authorizationToken = JWT::encode($payload, $privateKey, 'ES256', null, $header);

        return $authorizationToken;
    }

}
