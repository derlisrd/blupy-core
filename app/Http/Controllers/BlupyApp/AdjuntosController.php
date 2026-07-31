<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdjuntosController extends Controller
{
    public function subirComprobantes(Request $req)
    {
        $validated = $req->validate([
            'comprobantes' => 'required|array|size:6', // ajusta si no siempre son exactamente 6
            'comprobantes.*.base64' => 'required|string',
            'comprobantes.*.extension' => 'required|string|in:jpg,jpeg,png',
        ]);

        $uploadedPaths = [];
        $uploadedUrls = [];

        try {
            foreach ($validated['comprobantes'] as $key => $comprobante) {
                $fileContent = base64_decode($comprobante['base64'], true);

                if ($fileContent === false) {
                    throw new \Exception('Uno de los archivos no es base64 válido');
                }

                // límite de tamaño real, no confíes solo en post_max_size de php.ini
                if (strlen($fileContent) > 8 * 1024 * 1024) { // 8MB por imagen
                    throw new \Exception('Una de las imágenes supera el tamaño permitido (8MB)');
                }

                $fileName = Str::uuid() . '.' . $comprobante['extension'];

                $url = SupabaseService::uploadImage($fileName, $fileContent, $comprobante['extension']);

                $uploadedPaths[] = $fileName;
                $uploadedUrls[] = $url;
            }

            return response()->json([
                'success' => true,
                'urls' => $uploadedUrls,
            ]);
        } catch (\Throwable $th) {
            // rollback de lo que sí se alcanzó a subir
            foreach ($uploadedPaths as $path) {
                try {
                    SupabaseService::deleteImage($path);
                } catch (\Throwable $inner) {
                    SupabaseService::LOG("No se pudo eliminar {$path} en rollback: " , $inner->getMessage());
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Error subiendo comprobantes: ' . $th->getMessage(),
            ], 500);
        }
    }
}
