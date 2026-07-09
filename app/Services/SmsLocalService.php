<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsLocalService
{



    public function __construct()
    {

    }

    public function enviarSms(string $numero, string $texto)
    {
        $url = env('SMS_API_URL_LOCAL');
        try {
            return Http::timeout(10)
                ->retry(2, 100)
                ->post($url, [
                    'message' => $texto,
                    'phone' => $numero
                ]);
        } catch (\Throwable $th) {
            Log::error('Error en enviarSms', [
                'numero' => $numero,
                'error' => $th->getMessage()
            ]);
            throw $th;
        }
    }
}
