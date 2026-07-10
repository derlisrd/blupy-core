<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Jobs\ReclamarDeudaMorososSmsJob;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;


class MorososController extends Controller
{

    public function morosos()
    {
        try {


            $resultados = DB::connection('pgsql_externa')->select("
            SELECT 
                m.maectaid, 
                c.clidocu, 
                c.clinombre, 
                m.mcsalact, 
                m.mcpagmin, 
                m.mclincre, 
                m.mcatraso  
            FROM maecta m 
            INNER JOIN cliente c ON c.cliid = m.cliid 
            WHERE m.mcsitu = 'A' 
            AND m.mcatraso > 0 
            ORDER BY m.maectaid ASC
        ");

            return response()->json([
                'success' => true,
                'results' => $resultados
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function reclamoPorSmsConListadoCSV(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
            'text' => 'required|string|min:5|max:120'
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
            if (!Storage::disk('local')->exists($path)) {
                return response()->json(['success' => false, 'message' => 'El archivo no se guardó correctamente.'], 500);
            }

            if (!$path) {
                return response()->json(['success' => false, 'message' => 'Error al guardar el archivo.'], 500);
            }
            ReclamarDeudaMorososSmsJob::dispatch($path, $text)->onConnection('database');
            return response()->json([
                'success' => true,
                'message' => 'Mensajes enviados en 2do plano'
            ]);
        } catch (\Throwable $th) {
            SupabaseService::LOG('error', 'Error al procesar el reclamo por SMS con listado CSV: ' . $th->getMessage());
            throw $th;
        }
    }
}
