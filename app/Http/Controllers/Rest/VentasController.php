<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VentasController extends Controller
{
    public function ventasMesDigital(){
        try {
            $queries = [

            ];
            $res = SupabaseService::obtenerVentas($queries);
            if ($res) {
                return response()->json(['success' => true, 'results'=>$res]);
            }
            return response()->json(['success' => true, 'results'=>null],400);
        } catch (\Throwable $th) {
            Log::error('Error en ventasMesDigital: ' . $th->getMessage());
            return response()->json(['success'=>false,'message'=>'Error de servidor'],500);
        }
    }
}
