<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Services\GeminiService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon; // Importante para manejar la fecha
use Exception;

class VerificarIdentidadController extends Controller
{
    public function escanearCedula(Request $request, GeminiService $documentService)
    {
        // 1. Validar request (especificamos que el formato de fecha esperado es d/m/Y)
        $validator = Validator::make($request->all(), [
            'fotofrontal64' => 'required|string',
            'cedula'        => 'required',
            'nombres'       => 'required|string',
            'apellidos'     => 'required|string',
            'nacimiento'    => 'required|date_format:d/m/Y', // Valida que envíen "14/03/1996"
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        $ip = $request->ip();
        $rateKey = "scanCedula:$ip";

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Demasiadas peticiones. Espere 1 minuto.'
            ], 429);
        }
        RateLimiter::hit($rateKey, 60);

        // 2. Procesar imagen con Gemini
        $extractedData = $documentService->analyzeDocument($request->fotofrontal64);
        SupabaseService::LOG('Gemini api: linea 47','Procesado la imagen');
        if (!$extractedData || !($extractedData['es_cedula_paraguaya'] ?? false)) {
            return response()->json([
                'success' => false,
                'results' => [
                    'apellidos'  => false,
                    'nombres'    => false,
                    'nacimiento' => false,
                    'cedula'     => false,
                ],
                'message' => 'No se pudo detectar una cédula de identidad paraguaya legible. Tome una foto con buena iluminación y sin reflejos.'
            ], 400);
        }

        // 3. Normalizar datos para comparación
        $userInputCi = preg_replace('/[^0-9]/', '', $request->input('cedula'));
        $extractedCi = preg_replace('/[^0-9]/', '', $extractedData['numero_cedula'] ?? '');

        // --- CONVERSIÓN DE FECHA DE "14/03/1996" A "1996-03-14" ---
        try {
            $userBirthIso = Carbon::createFromFormat('d/m/Y', trim($request->input('nacimiento')))->format('Y-m-d');
        } catch (Exception $e) {
            $userBirthIso = null;
        }
        $extractedBirthIso = trim($extractedData['fecha_nacimiento'] ?? '');

        $userName = $this->normalizeText($request->input('nombres'));
        $extractedName = $this->normalizeText($extractedData['nombre'] ?? '');

        $userLastName = $this->normalizeText($request->input('apellidos'));
        $extractedLastName = $this->normalizeText($extractedData['apellido'] ?? '');

        // 4. Comparaciones
        $isCedulaValid = (!empty($extractedCi) && $userInputCi === $extractedCi);

        // Compara las dos fechas formateadas como "YYYY-MM-DD"
        $isBirthValid = (!empty($userBirthIso) && !empty($extractedBirthIso) && $userBirthIso === $extractedBirthIso);

        $isNameValid = (!empty($extractedName) && (str_contains($extractedName, $userName) || str_contains($userName, $extractedName)));
        $isLastNameValid = (!empty($extractedLastName) && (str_contains($extractedLastName, $userLastName) || str_contains($userLastName, $extractedLastName)));

        // 5. Acumular mensajes de error
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

        $finalMessage = $success
            ? 'Documento verificado con éxito.'
            : implode(' ', $errors);
        SupabaseService::LOG('final mensaje de gemini', $finalMessage);
        // 6. Respuesta JSON para React Native
        return response()->json([
            'success' => $success,
            'results' => [
                'apellidos'  => $isLastNameValid,
                'nombres'    => $isNameValid,
                'nacimiento' => $isBirthValid,
                'cedula'     => $isCedulaValid,
            ],
            'message' => $finalMessage
        ], $status);
    }

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
