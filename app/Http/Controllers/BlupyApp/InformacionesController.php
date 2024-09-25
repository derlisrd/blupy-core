<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Informacion;
use Illuminate\Http\Request;

class InformacionesController extends Controller
{
    public function InfoPopUpInicial(Request $req){

        $results = Informacion::where('active',1)
        ->where('general',1)
        ->latest()->first();

        return response()->json([
            'success'=>true,
            'results'=>$results
        ]);
    }
}
