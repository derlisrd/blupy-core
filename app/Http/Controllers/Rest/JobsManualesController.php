<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Jobs\ActualizarSucursalesFarmaJobs;
use App\Jobs\UpdatePerfilJobs;
// use Illuminate\Http\Request;

class JobsManualesController extends Controller
{
    public function updatePerfilFuncionarios()
    {
        UpdatePerfilJobs::dispatch();

        return response()->json(['success'=>true,'message' => 'Job encolado para actualizar perfiles']);
    }
    public function updatePerfilAlianzas()
    {
        //UpdatePerfilJobs::dispatch();

        return response()->json(['success'=>true,'message' => 'Job encolado para actualizar perfiles de alianzas']);
    }
    public function updateSucursalesFarma(){
        ActualizarSucursalesFarmaJobs::dispatch();
        return response()->json(['success'=>true,'message' => 'Job encolado para actualizar sucursales']);
    }
}
