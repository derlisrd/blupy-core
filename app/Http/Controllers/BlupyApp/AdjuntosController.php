<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdjuntosController extends Controller
{
    public function subirComprobantes(Request $req)
    {
        

        $validator = Validator::make($req->all(), [
            'files' => 'required|array|size:6',
            'files.*' => 'required|file|mimes:jpg,jpeg,png,pdf|max:8192', // Máximo 8MB por archivo);
        ]);
        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        $cliente = $req->user->cliente;

        $cliente = $req->user()->cliente; // Asegúrate de accederlo como método o propiedad según tu modelo
        $cedula = $cliente->cedula ?? $cliente->numero_documento;

        if (!$cedula) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la cédula asociada al cliente.',
            ], 400);
        }

        $uploadedFileNames = [];
        $uploadedUrls = [];

        try {
            foreach ($req->file('files') as $file) {
                $fileContent = file_get_contents($file->getRealPath());
                $extension = strtolower($file->getClientOriginalExtension());

                // Si la extensión viene vacía por picker del OS, la deducimos del mimeType
                if (!$extension) {
                    $mime = $file->getMimeType();
                    $extension = match ($mime) {
                        'application/pdf' => 'pdf',
                        'image/png' => 'png',
                        default => 'jpg',
                    };
                }

                $fileName = Str::uuid() . '.' . $extension;

                // Subir usando la estructura por cédula
                $url = SupabaseService::uploadComprobante($cedula, $fileName, $fileContent, $extension);

                $uploadedFileNames[] = $fileName;
                $uploadedUrls[] = $url;
            }

            return response()->json([
                'success' => true,
                'urls' => $uploadedUrls,
            ]);
        } catch (\Throwable $th) {
            // Rollback: eliminar archivos subidos en este intento si falla alguno
            foreach ($uploadedFileNames as $fileName) {
                try {
                    SupabaseService::deleteComprobante($cedula, $fileName);
                } catch (\Throwable $inner) {
                    SupabaseService::LOG("Rollback Fallido: " . $fileName, $inner->getMessage());
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Error subiendo comprobantes: ' . $th->getMessage(),
            ], 500);
        }
    }
}
