<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GeminiService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY', '');
        // Usamos la versión de API y modelo estable correcta
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent';
    }

    public function analyzeDocument(string $base64String): ?array
    {
        if (empty($this->apiKey)) {
            Log::error('GeminiService: La API Key de Gemini no está configurada.');
            return null;
        }

        try {
            // Detectar MimeType y limpiar la cadena Base64 si viene con prefijo "data:image/..."
            $mimeType = 'image/jpeg';
            if (preg_match('/^data:(image\/\w+);base64,/', $base64String, $type)) {
                $base64Image = substr($base64String, strpos($base64String, ',') + 1);
                $mimeType = strtolower($type[1]);
            } else {
                $base64Image = trim($base64String); // Mantiene la cadena Base64 intacta sin decode
            }

            $prompt = $this->buildPrompt();

            $response = Http::timeout(20)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}?key={$this->apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType, // 'image/jpeg' o 'image/png'
                                        'data'      => $base64Image // Cadena Base64 pura en texto
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'response_mime_type' => 'application/json',
                        'temperature'        => 0.1
                    ]
                ]);

            if ($response->failed()) {
                Log::error('GeminiService: Error en la API de Gemini', [
                    'status'   => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }

            $responseData = $response->json();
            $rawText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$rawText) {
                return null;
            }

            $parsedData = json_decode($rawText, true);

            return is_array($parsedData) ? $parsedData : null;
        } catch (Exception $e) {
            Log::error('GeminiService: Excepción durante el proceso', [
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function buildPrompt(): string
    {
        return <<<PROMPT
Analiza la imagen provista y determina si corresponde a una Cédula de Identidad civil de la República del Paraguay.

Extrae exactamente los datos visibles y responde UNICAMENTE con la siguiente estructura JSON estricta:

{
  "es_cedula_paraguaya": boolean,
  "nombre": "string o null",
  "apellido": "string o null",
  "numero_cedula": "string o null",
  "fecha_nacimiento": "string o null"
}

Reglas estrictas:
1. Si la imagen está borrosa, cortada o los datos no se distinguen, asigna null a esos campos.
2. Limpia el campo 'numero_cedula' retirando cualquier punto decimal o carácter especial.
3. Si detectas que la imagen es una fotocopia de muy mala calidad o presenta ediciones evidentes, asigna false a 'es_cedula_paraguaya'.
4. Formatea la fecha de nacimiento estrictamente como YYYY-MM-DD (ejemplo: 1995-10-25).
5. No agregues bloques de código markdown tipo ```json ni comentarios fuera del JSON.
PROMPT;
    }
}
