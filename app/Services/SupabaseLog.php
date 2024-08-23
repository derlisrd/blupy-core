<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseLog
{

    public function __construct(){
    }

    public function insertLog($origen, $detalles){
        try {
            $response = Http::withHeaders([
                'apikey' => env('SUPABASE_API_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post(env('SUPABASE_URL') . '/rest/v1/logs', [
                'origin' => $origen,
                'details' => $detalles
            ]);
            Log::info($response);
            return true;
        } catch (\Throwable $th) {
            Log::error($th);
            return false;
        }
    }
}
