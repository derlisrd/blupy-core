<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Jobs\ActualizarSucursalesFarmaJobs;
use App\Jobs\ProcesarVentasDelDiaFarmaJobs;
use App\Jobs\UpdateClienteDigitalJob;
use App\Jobs\UpdatePerfilJobs;
use App\Jobs\UpdateSolicitudesJobs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobsManualesController extends Controller
{
    public function updatePerfilFuncionarios()
    {
        UpdatePerfilJobs::dispatch();

        return response()->json(['success'=>true,'message' => 'Proceso en 2do. para actualizar perfiles']);
    }
    public function updatePerfilAlianzas()
    {
        UpdatePerfilJobs::dispatch()->onConnection('database');

        return response()->json(['success'=>true,'message' => 'Proceso en 2do. para actualizar perfiles de alianzas']);
    }
    public function updateSucursalesFarma(){
        ActualizarSucursalesFarmaJobs::dispatch();
        return response()->json(['success'=>true,'message' => 'Proceso en 2do. para actualizar sucursales']);
    }

    public function updateVentasFarma(Request $request){
        $validator = Validator::make($request->all(), [
            'fecha' => 'required|date_format:Y-m-d',
        ]);
        if ($validator->fails())
            return response()->json(['success'=>false,'message' => $validator->errors()->first()], 400);

        ProcesarVentasDelDiaFarmaJobs::dispatch($request->fecha);
        return response()->json(['success'=>true,'message' => 'Proceso en 2do. para actualizar ventas']);
    }

    public function updateClienteDigital(){
        UpdateClienteDigitalJob::dispatch()->onConnection('database');
        return response()->json(['success'=>true,'message' => 'Proceso en 2do. para actualizar perfiles de digital']);
    }
    public function updateSolicitudesPendientes(){
        UpdateSolicitudesJobs::dispatch()->onConnection('database');
        return response()->json(['success'=>true,'message' => 'Proceso en 2do. para actualizar perfiles de digital']);
    }
}
