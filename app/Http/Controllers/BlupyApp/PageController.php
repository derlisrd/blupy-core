<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show(string $sku)
    {
        // Clave única en cache para este SKU
        $cacheKey = "page_sku:{$sku}";

        // Guarda en cache por 1 día (86400 segundos)
        // Si no existe en cache, ejecuta la consulta SQL una sola vez y la guarda
        $page = Cache::remember($cacheKey, 86400, function () use ($sku) {
            return Page::where('sku', $sku)->first();
        });

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Página no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'results' => $page
        ]);
    }
}
