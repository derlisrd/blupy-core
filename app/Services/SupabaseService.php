<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseService
{
    private string $url;
    private string $apiKey;

    public function __construct(){
        $this->url = env('SUPABASE_API_KEY');
        $this->apiKey = env('SUPABASE_API_KEY');
    }

    public static function LOG($origen, $detalles){
        try {
            Http::withHeaders([
                'apikey' => env('SUPABASE_API_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post(env('SUPABASE_URL') . '/rest/v1/logs', [
                'origin' => $origen,
                'details' => $detalles
            ]);
            return true;
        } catch (\Throwable $th) {
            Log::error($th);
            return false;
        }
    }
    public static function uploadImageSelfies($base64Image)
{

    // Eliminar prefijo si existe (ej: "data:image/jpeg;base64,")
    $base64Image = preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);

    // Asegurarse de que la cadena base64 esté limpia (sin espacios o caracteres no válidos)
    $base64Image = trim($base64Image);

    // Verificar que la cadena base64 sea válida
    if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $base64Image)) {
        return response()->json([
            'success' => false,
            'error' => 'Invalid base64 string'
        ], 400);
    }

    // Decodificar la imagen
    $imageData = base64_decode($base64Image, true);

    // Verificar si la decodificación fue exitosa
    if ($imageData === false) {
        return response()->json([
            'success' => false,
            'error' => 'Failed to decode base64 string'
        ], 400);
    }

    // Generar un nombre de archivo único
    $fileName = 'image_' . time() . '.jpg';

    // URL del endpoint de storage de Supabase
    $storageUrl = env('SUPABASE_URL') . '/storage/v1/object/';

    // Bucket donde se guardará la imagen
    $bucketName = 'selfies';

    try {
        // Subir la imagen como datos binarios sin intentar convertirlos en JSON
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('SUPABASE_API_KEY'),
            'Content-Type' => 'application/octet-stream'
        ])->withBody(
            $imageData, 'application/octet-stream'
        )->put(
            $storageUrl . $bucketName . '/' . $fileName
        );

        if ($response->successful()) {
            // Construir la URL pública de la imagen
            $publicUrl = env('SUPABASE_URL') . '/storage/v1/object/public/' . $bucketName . '/' . $fileName;

            return true;
        }
        return false;

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}

    public static function ingresarVentas(array $datas)
    {
        try {
            $response = Http::withHeaders([
                'apikey' => env('SUPABASE_API_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post(env('SUPABASE_URL') . '/rest/v1/ventas', $datas);

            if ($response->failed()) {
                Log::error('Error en Supabase: ' . $response->body());
                return false;
            }

            return true;
        } catch (\Throwable $th) {
            Log::error('Excepción en SupabaseService::ventas: ' . $th->getMessage());
            return false;
        }
    }

    public static function obtenerVentas(array $querys)
    {
        try {
            $response = Http::withHeaders([
                'apikey' => env('SUPABASE_API_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_API_KEY'),
                'Content-Type' => 'application/json',
            ])->get(env('SUPABASE_URL') . '/rest/v1/ventas',$querys);

            if ($response->failed()) {
                Log::error('Error al obtener ventas de Supabase: ' . $response->body());
                return null;
            }

            return $response->json();
        } catch (\Throwable $th) {
            Log::error('Excepción en SupabaseService::obtenerVentas: ' . $th->getMessage());
            return null;
        }
    }
}
