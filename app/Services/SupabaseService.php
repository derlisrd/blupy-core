<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;


class SupabaseService
{


    public static function registrarSesion($cedula,$telefono)
    {
        try {
            Http::withHeaders([
                'apikey' => env('SUPABASE_API_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post(env('SUPABASE_URL') . '/rest/v1/sesiones', [
                'cedula' => $cedula,
                'telefono' => $telefono
            ]);
            return true;
        } catch (\Throwable $th) {
            return false;
        }
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
            return false;
        }
    }


    public static function deleteComprobante(string $cedula, string $fileName): void
    {
        $supabaseUrl = env('SUPABASE_URL');
        $bucket = "adjuntos";
        $filePath = "{$cedula}/{$fileName}";
        $apiUrl = "{$supabaseUrl}/storage/v1/object/{$bucket}/{$filePath}";

        $response = Http::withHeaders([
            'apikey' => env('SUPABASE_API_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_API_KEY'),
        ])->delete($apiUrl);

        if (!$response->successful()) {
            throw new \Exception("Error eliminando {$filePath} de Supabase: " . $response->body());
        }
    }

    public static function uploadComprobante(string $cedula, string $fileName, string $fileContent, string $extension): string
    {
        $supabaseUrl = env('SUPABASE_URL');
        $bucket = "adjuntos";

        // Estructura de carpeta: adjuntos/{cedula}/{uuid}.{ext}
        $filePath = "{$cedula}/{$fileName}";
        $apiUrl = "{$supabaseUrl}/storage/v1/object/{$bucket}/{$filePath}";

        // Mapeo correcto de MIME type (evitamos el error de image/pdf)
        $ext = strtolower($extension);
        $contentType = match ($ext) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };

        $response = Http::withHeaders([
            'apikey' => env('SUPABASE_API_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_API_KEY'),
            'Content-Type' => $contentType,
        ])->withBody($fileContent, $contentType)
            ->post($apiUrl);

        if ($response->successful()) {
            return "{$supabaseUrl}/storage/v1/object/public/{$bucket}/{$filePath}";
        }

        throw new \Exception("Error subiendo a Supabase: " . $response->body());
    }


    public static function uploadImage($fileName, $fileContent, $imageType)
    {
        $supabaseUrl = env('SUPABASE_URL');
        $bucket = "selfies";
        $apiUrl = "{$supabaseUrl}/storage/v1/object/{$bucket}/{$fileName}";

        $response = Http::withHeaders([
            'apikey' => env('SUPABASE_API_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_API_KEY'),
            'Content-Type' => 'image/' . $imageType
        ])->withBody($fileContent, 'image/' . $imageType)
            ->post($apiUrl);

        if ($response->successful()) {
            // Retorna la URL pública (ajusta según tu config de Supabase)
            return "{$supabaseUrl}/storage/v1/object/public/{$bucket}/{$fileName}";
        }

        throw new \Exception("Error subiendo a Supabase: " . $response->body());
    }

    public static function deleteImage($fileName)
    {
        $supabaseUrl = env('SUPABASE_URL');
        $bucket = "selfies";
        $apiUrl = "{$supabaseUrl}/storage/v1/object/{$bucket}/{$fileName}";

        $response = Http::withHeaders([
            'apikey' => env('SUPABASE_API_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_API_KEY'),
        ])->delete($apiUrl);

        if (!$response->successful()) {
            throw new \Exception("Error eliminando {$fileName} de Supabase: " . $response->body());
        }
    }

    
    public static function uploadImageSelfies($fileName,$imagePath,$imageType)
    {
        try {
               $name = time().'_'.$fileName;
                $supabaseApiUrl = env('SUPABASE_URL'). "/storage/v1/object/selfies/".$name;

                $response = Http::withHeaders([
                    'apikey' => env('SUPABASE_API_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_API_KEY'),
                ])->attach(
                    'file',
                    file_get_contents($imagePath),
                    $fileName,
                    ['Content-Type' => 'image/' . $imageType]
                )->post($supabaseApiUrl);

            return true;
        } catch (\Throwable $th) {
            throw $th;
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
                return false;
            }

            return true;
        } catch (\Throwable $th) {
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
                return null;
            }

            return $response->json();
        } catch (\Throwable $th) {
            return null;
        }
    }
}
