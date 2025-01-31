<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PreRegistroController extends Controller
{
    public function preRegistro()
    {
        return view('web.preregistro');
    }

    public function store(Request $request){

        $request->validate([
            'cedula' => 'required',
            'nombres' => 'required',
            'apellidos' => 'required'
        ]);

        try {
            Http::withHeaders([
                'apikey' => env('SUPABASE_API_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post(env('SUPABASE_URL') . '/rest/v1/preregistros', [
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'cedula' => $request->cedula,
            ]);
            return back()->with('success', 'Listo. La alianza se comunicará contigo.');
        } catch (\Throwable $th) {
            Log::error($th);
            return false;
        }
    }
}
