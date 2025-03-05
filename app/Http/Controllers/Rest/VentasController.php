<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VentasController extends Controller
{
    public function ventas(){
        try {
            $ventas = SupabaseService::obtenerVentas();
            Log::info($ventas);
            return response()->json($ventas);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
