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
            Log::error($th->getMessage());
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
