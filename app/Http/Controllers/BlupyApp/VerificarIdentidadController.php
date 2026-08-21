<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class VerificarIdentidadController extends Controller
{
    public function validateDocument(Request $request, GeminiService $documentService)
    {
        // 1. Validar que la petición HTTP venga completa
        $request->validate([
            'cedula' => 'required|string',
            'fecha_nacimiento' => 'required|date_format:Y-m-d',
            'nombres' => 'required|string',
            'apellidos' => 'required|string',
            'imagen' => 'required|image|max:10240', // máx 10MB
        ]);

        // 2. Procesar imagen con el modelo de visión (Gemini/OCR)
        $extractedData = $documentService->analyzeDocument($request->file('imagen'));

        // Si la IA falla totalmente o no detecta una cédula
        if (!$extractedData || !($extractedData['es_cedula_paraguaya'] ?? false)) {
            return response()->json([
                'success' => false,
                'results' => [
                    'apellidos' => false,
                    'nombres' => false,
                    'nacimiento' => false,
                    'cedula' => false,
                ],
                'message' => 'No se pudo detectar una cédula de identidad paraguaya legible. Tome una foto con buena iluminación y sin reflejos.'
            ], 400);
        }

        // 3. Normalizar datos para comparación segura
        $userInputCi = preg_replace('/[^0-9]/', '', $request->input('cedula'));
        $extractedCi = preg_replace('/[^0-9]/', '', $extractedData['numero_cedula'] ?? '');

        $userBirth = $request->input('fecha_nacimiento'); // Formato YYYY-MM-DD
        $extractedBirth = $extractedData['fecha_nacimiento'] ?? '';

        $userName = $this->normalizeText($request->input('nombres'));
        $extractedName = $this->normalizeText($extractedData['nombre'] ?? '');

        $userLastName = $this->normalizeText($request->input('apellidos'));
        $extractedLastName = $this->normalizeText($extractedData['apellido'] ?? '');

        // 4. Comparaciones individuales
        $isCedulaValid = (!empty($extractedCi) && $userInputCi === $extractedCi);
        $isBirthValid = (!empty($extractedBirth) && $userBirth === $extractedBirth);

        // Para nombres/apellidos verificamos contención lógica (evita fallos por 2do nombre omitido)
        $isNameValid = (!empty($extractedName) && (str_contains($extractedName, $userName) || str_contains($userName, $extractedName)));
        $isLastNameValid = (!empty($extractedLastName) && (str_contains($extractedLastName, $userLastName) || str_contains($userLastName, $extractedLastName)));

        // 5. Acumular mensajes de error específicos
        $errors = [];
        if (!$isCedulaValid) {
            $errors[] = 'El número de cédula no coincide con la foto o no es legible.';
        }
        if (!$isBirthValid) {
            $errors[] = 'La fecha de nacimiento no coincide o no es legible.';
        }
        if (!$isNameValid) {
            $errors[] = 'El nombre ingresado no coincide con el documento.';
        }
        if (!$isLastNameValid) {
            $errors[] = 'El apellido ingresado no coincide con el documento.';
        }

        $success = empty($errors);
        $status = $success ? 200 : 400;

        // Construir el mensaje final (Unifica errores si hay varios)
        $finalMessage = $success
            ? 'Documento verificado con éxito.'
            : implode(' ', $errors);

        // 6. Respuesta JSON exacta para React Native
        return response()->json([
            'success' => $success,
            'results' => [
                'apellidos' => $isLastNameValid,
                'nombres' => $isNameValid,
                'nacimiento' => $isBirthValid,
                'cedula' => $isCedulaValid,
            ],
            'message' => $finalMessage
        ], $status);
    }

    /**
     * Helper para limpiar cadenas: Mayúsculas, sin tildes, sin espacios extra.
     */
    private function normalizeText(string $text): string
    {
        $text = mb_strtoupper(trim($text));
        $unwantedArray = [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ä' => 'A',
            'Ë' => 'E',
            'Ï' => 'I',
            'Ö' => 'O',
            'Ü' => 'U'
        ];
        return strtr($text, $unwantedArray);
    }
}
