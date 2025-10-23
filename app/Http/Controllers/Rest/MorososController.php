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
            $path = $file->store('morosos_temp', 'local');

            if (!$path) {
                return response()->json(['message' => 'Error al guardar el archivo.'], 500);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
