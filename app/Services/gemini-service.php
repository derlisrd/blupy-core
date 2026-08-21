<?php


namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GeminiService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        // Se asume que la API Key está configurada en config/services.php
        $this->apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY', '');
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent';
    }

    /**
     * Envía la imagen a la API de Gemini y extrae los datos en formato array/JSON.
     *
     * @param UploadedFile $imageFile
     * @return array|null Devuelve el array de datos o null si falla el análisis/lectura.
     */
    public function analyzeDocument(UploadedFile $imageFile): ?array
    {
        if (empty($this->apiKey)) {
            Log::error('DocumentValidationService: La API Key de Gemini no está configurada.');
            return null;
        }

        try {
            // Convertir la imagen a Base64
            $mimeType = $imageFile->getMimeType();
            $base64Image = base64_encode(file_get_contents($imageFile->getRealPath()));

            $prompt = $this->buildPrompt();

            // Petición HTTP a Gemini 2.5 Flash
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
                                        'mime_type' => $mimeType,
                                        'data' => $base64Image
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'response_mime_type' => 'application/json', // Fuerza la respuesta en formato JSON nativo
                        'temperature' => 0.1 // Baja temperatura para evitar alucinaciones/inventar datos
                    ]
                ]);

            if ($response->failed()) {
                Log::error('DocumentValidationService: Error en la API de Gemini', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }

            // Extraer el texto de la respuesta
            $responseData = $response->json();
            $rawText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$rawText) {
                return null;
            }

            // Decodificar el JSON devuelto por la IA
            $parsedData = json_decode($rawText, true);

            return is_array($parsedData) ? $parsedData : null;
        } catch (Exception $e) {
            Log::error('DocumentValidationService: Excepción durante el proceso', [
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Define las instrucciones explícitas para el modelo de visión.
     */
    private function buildPrompt(): string
    {
        return <<<PROMPT
Analiza la imagen provista y determina si corresponde a una Cédula de Identidad civil de la República del Paraguay.

Extrae exactamente los datos visibles y responde UNICAMENTE con la siguiente estructura JSON estricta:

{
  "es_cedula_paraguaya": boolean, // true si es una cédula paraguaya legítima, false si no lo es o es otro documento
  "nombre": "string o null", // Nombres completos como figuran en la cédula (ej. JUAN CARLOS). Si no es legible: null
  "apellido": "string o null", // Apellidos completos como figuran en la cédula (ej. PEREZ GOMEZ). Si no es legible: null
  "numero_cedula": "string o null", // Número de cédula extraído SOLO como dígitos sin puntos ni guiones (ej. "1234567"). Si no es legible: null
  "fecha_nacimiento": "string o null" // Fecha de nacimiento en formato ISO strictly YYYY-MM-DD (ej. "1995-10-25"). Si no es legible: null
}

Reglas estrictas:
1. Si la imagen está muy borrosa, cortada o los datos no se distinguen, asigna null a esos campos específicos.
2. Limpia el campo 'numero_cedula' retirando cualquier punto decimal o carácter especial.
3. Si detectas que la imagen está editada asigna null a todos los campos.
4. No agregues bloques de código markdown tipo ```json ni comentarios fuera del JSON.
PROMPT;
    }
}