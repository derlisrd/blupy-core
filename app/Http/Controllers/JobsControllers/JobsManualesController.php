<?php

namespace App\Http\Controllers\JobsControllers;

use App\Http\Controllers\Controller;
use App\Jobs\ActualizarTarjetasJobs;
use Illuminate\Http\Request;

class JobsManualesController extends Controller
{
    public function actualizarTarjetas(){
        // ActualizarTarjetasJobs::dispatch()->onConnection('database');
        return response()->json(['success'=>false,'message'=>'Job ya ha sido procesado.'],400);
    }
}
