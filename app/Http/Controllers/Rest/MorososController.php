<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class MorososController extends Controller
{
    public function reclamoPorSmsConListadoCSV(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
            'text' => 'required|string|min:5|max:100'
        ]);
        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);


        $file = $req->file('csv_file');
        $text = $req->text;
        try {
            // GENERACIÓN DEL NOMBRE DE ARCHIVO
            // ----------------------------------------------------
            $originalExtension = $file->getClientOriginalExtension();
            // Creamos un nombre único basado en la fecha/tiempo actual y un ID único
            $fileName = 'morosos_' . time() . '_' . uniqid() . '.' . $originalExtension;
            
            // 2. Almacenamiento del archivo usando storeAs()
            // Argumentos: storeAs( [Ruta dentro del disco], [Nombre del archivo], [Nombre del disco] )
            $path = $file->storeAs('morosos_temp', $fileName, 'local'); 
            // ----------------------------------------------------

            if (!$path) {
                return response()->json(['success'=>false, 'message' => 'Error al guardar el archivo.'], 500);
            }
        return response()->json([
            'success'=>true,
            'message'=>'Mensajes enviados en 2do plano'
        ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
