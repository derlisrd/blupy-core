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
    public static function uploadImageSelfies($imagePath, $filename){
        try {
            if (!file_exists($imagePath)) {
                Log::error('El archivo de imagen no existe: ' . $imagePath);
                return false;
            }

            $bucketName = 'selfies';
            $url = env('SUPABASE_URL') . '/storage/v1/object/' . $bucketName . '/' . $filename;

            $imageContent = file_get_contents($imagePath);

            $response = Http::withHeaders([
                'apikey' => env('SUPABASE_API_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_API_KEY'),
                'Content-Type' => 'application/octet-stream',
            ])->put($url, $imageContent);

            if ($response->failed()) {
                Log::error('Error al subir imagen a Supabase: ' . $response->body());
                return false;
            }
            return true;
        } catch (\Throwable $th) {
            Log::error($th);
            return false;
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
