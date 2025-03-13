<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SupabaseService
{
    private string $url;
    private string $apiKey;

    public function __construct()
    {
        $this->url = env('SUPABASE_URL');
        $this->apiKey = env('SUPABASE_API_KEY');
    }

    public static function LOG($origen, $detalles)
    {
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

        // Decodificar la imagen
        $imageData = base64_decode($base64Image);

        // Generar un nombre de archivo único
        $fileName = 'image_' . time() . '.jpg';

        // Guardar la imagen temporalmente en el almacenamiento local
        $tempPath = 'temp/' . $fileName;
        Storage::put($tempPath, $imageData);

        // Obtener la ruta completa del archivo
        $tempFilePath = Storage::path($tempPath);

        // URL del endpoint de storage de Supabase
        $storageUrl = env('SUPABASE_URL') . '/storage/v1/object/';

        // Bucket donde se guardará la imagen
        $bucketName = 'tu_bucket_name';

        try {
            // Crear un stream del archivo para subirlo
            $fileStream = fopen($tempFilePath, 'r');

            // Subir la imagen usando el stream del archivo
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('SUPABASE_API_KEY'),
                'Content-Type' => 'application/octet-stream'
            ])->put(
                $storageUrl . $bucketName . '/' . $fileName,
                $fileStream
            );

            // Cerrar el stream
            fclose($fileStream);

            // Eliminar el archivo temporal
            Storage::delete($tempPath);

            if ($response->successful()) {
                // Construir la URL pública de la imagen
                $publicUrl = env('SUPABASE_URL') . '/storage/v1/object/public/' . $bucketName . '/' . $fileName;

                return response()->json([
                    'success' => true,
                    'url' => $publicUrl
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $response->json() ?: $response->body()
            ], 400);
        } catch (\Exception $e) {
            // Asegúrate de eliminar el archivo temporal incluso si hay un error
            if (Storage::exists($tempPath)) {
                Storage::delete($tempPath);
            }

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
            ])->get(env('SUPABASE_URL') . '/rest/v1/ventas', $querys);

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
