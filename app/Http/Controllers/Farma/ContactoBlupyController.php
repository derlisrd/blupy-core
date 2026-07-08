<?php

namespace App\Http\Controllers\Farma;

use App\Http\Controllers\Controller;
use App\Jobs\LocalEnviarSmsMorosoJob;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactoBlupyController extends Controller
{
    public function getNroTelefono(Request $req){
        try {
            $cedula = $req->cedula;

            $cliente =  Cliente::where('cedula','=',$cedula,false)->first();

            if($cliente){
                return response()->json([
                    'success'=>true,
                    'results' =>$cliente->celular
                ]);
            }
            return response()->json([
                'success' => true,
                'results' => null
            ],400);

        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function sendSmsMorosos(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'texto' => 'required|string',
            'file'  => 'required|file|mimes:csv,txt',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $texto = $request->texto;

        // Leer CSV
        $cedulas = [];

        if (($handle = fopen($request->file('file')->getRealPath(), "r")) !== false) {

            while (($row = fgetcsv($handle, 1000, ",")) !== false) {

                if (isset($row[0])) {

                    $cedula = trim($row[0]);

                    if ($cedula != '') {
                        $cedulas[] = $cedula;
                    }
                }
            }

            fclose($handle);
        }

        $cedulas = array_unique($cedulas);

        $clientes = Cliente::select('cedula', 'celular')
            ->whereIn('cedula', $cedulas)
            ->whereNotNull('celular')
            ->where('celular', '!=', '')
            ->get();

        $delay = 0;

        foreach ($clientes as $cliente) {

            LocalEnviarSmsMorosoJob::dispatch(
                $cliente->celular,
                $texto
            )->delay(now()->addSeconds($delay));

            $delay += 3;
        }

        return response()->json([
            'success' => true,
            'clientes_encontrados' => $clientes->count(),
            'mensajes_encolados' => $clientes->count(),
            'cedulas_csv' => count($cedulas)
        ]);
    }
}
