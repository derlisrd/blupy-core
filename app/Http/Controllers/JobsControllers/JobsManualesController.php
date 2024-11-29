<?php

namespace App\Http\Controllers\JobsControllers;

use App\Http\Controllers\Controller;
use App\Jobs\ActualizarTarjetasJobs;
use App\Jobs\UpdatePerfilJobs;
use Illuminate\Http\Request;

class JobsManualesController extends Controller
{
    public function actualizarTarjetas(){
        // ActualizarTarjetasJobs::dispatch()->onConnection('database');
        return response()->json(['success'=>false,'message'=>'Job ya ha sido procesado.'],400);
    }
    public function actualizarPerfilFuncionario(){
        UpdatePerfilJobs::dispatch()->onConnection('database');
        return response()->json(['success'=>true,'message'=>'Actualizando perfiles en 2do plano.']);
    }
}
