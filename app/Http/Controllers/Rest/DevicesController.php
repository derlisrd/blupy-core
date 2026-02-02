<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Jobs\PushNativeJobs;
use App\Models\Cliente;
use App\Models\Device;
use App\Models\DeviceNewRequest;
use App\Models\HistorialDato;
use App\Models\User;
use App\Services\InfinitaService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class DevicesController extends Controller
{
    public function aprobar(Request $req){

        $id = $req->id;
        $newDevice = DeviceNewRequest::find($id);

        if(!$newDevice){
            return response()->json(['success'=>false,'message'=>'No existe device'],404);
        }

        Device::create([
            'user_id'=>$newDevice->user_id,
            'device'=>$newDevice->device,
            'os'=>$newDevice->os,
            'devicetoken'=>$newDevice->devicetoken,
            'model'=>$newDevice->model,
            'web'=>$newDevice->web,
            'desktop'=>$newDevice->desktop,

            

            'ip'=>$newDevice->ip,
            'version'=>$newDevice->version,

            'device_id_app'=>$newDevice->device_id_app,
            'build_version'=>$newDevice->build_version,
            'time'=>$newDevice->time
        ]);

        $devices = Device::where('user_id',$newDevice->user_id)->get();

        if ($newDevice->os == 'android') {
            PushNativeJobs::dispatch("Dispositivo aprobado", 'Ya puedes ingresar con este dispositivo.', [$newDevice->devicetoken], 'android')
                ->onConnection('database');
        }

        if ($newDevice->os == 'ios') {
            PushNativeJobs::dispatch("Dispositivo aprobado", 'Ya puedes ingresar con este dispositivo.', [$newDevice->devicetoken], 'ios')
                ->onConnection('database');
        }


        $user = User::find($newDevice->user_id);
        $cliente = $user->cliente;

        HistorialDato::create([
            'user_id' => $newDevice->user_id,
            'celular' => $cliente->celular
        ]);

        

        Cliente::find($cliente->id)->update([
            'celular' => $newDevice->celular
        ]);
        $this->cambiosEnInfinita($user->cliente->cliid, null, $newDevice->celular);

        $newDevice->aprobado = 1;
        $newDevice->save();

        return response()->json([
            'success'=>true,
            'results'=>$devices
        ]);
    }

    public function listado(){
        $results = DeviceNewRequest::where('aprobado',0)
        ->join('users as u','u.id','=','device_new_requests.user_id')
        ->join('clientes as c','c.id','=','u.cliente_id') 
        ->select('device_new_requests.*','u.name','c.cedula')
        ->get();
        return response()->json([
            'success'=>true,
            'message'=>'',
            'results'=>$results
        ]);
    }

    public function destroyRequestDevice($id)
    {
        try {
            $device = DeviceNewRequest::find($id);
            $device->delete();

            return response()->json([
                'success' => true,
                'message' => 'Eliminado correctamente'
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    private function cambiosEnInfinita($cliid, $email, $telefono): void
    {

        $webserviceInfinita = new InfinitaService();

        $cliente =  $webserviceInfinita->TraerDatosCliente($cliid);
        $clienteDatos = (object) $cliente['data'];

        $cliObj = (object)$clienteDatos->wCliente;

        $telefonoNuevo = [
            (object)[
                'CliTelId' => $cliObj->Tel[0]['CliTelId'],
                'CliTelNot' => $cliObj->Tel[0]['CliTelNot'],
                'CliTelUb' => $cliObj->Tel[0]['CliTelUb'],
                'CliTelef' => $telefono ?  $telefono : $cliObj->Tel[0]['CliTelef']
            ]
        ];

        $clienteModificado = [
            'ActComId' => 0,
            'CliApe' => $cliObj->CliApe,
            'CliApe1' => $cliObj->CliApe1,
            'CliApe2' => $cliObj->CliApe2,
            'CliApe3' => $cliObj->CliApe3,
            'CliCobId' => $cliObj->CliCobId,
            'CliContrib' => $cliObj->CliContrib,
            'CliCumple' => $cliObj->CliCumple,
            'CliDocDv' => $cliObj->CliDocDv,
            'CliDocu' => $cliObj->CliDocu,
            'CliEdad' => $cliObj->CliEdad,
            'CliEmail' => $email ? $email :  $cliObj->CliEmail,
            'CliEsAso' => $cliObj->CliEsAso,
            'CliEstCiv' => $cliObj->CliEstCiv,
            'CliEstado' => $cliObj->CliEstado,
            'CliFecNac' => $cliObj->CliFecNac,
            'CliIVAEx' => $cliObj->CliIVAEx,
            'CliId' => $cliObj->CliId,
            'CliLabCar' => $cliObj->CliLabCar,
            'CliLabFecIng' => $cliObj->CliLabFecIng,
            'CliLabLug' => $cliObj->CliLabLug,
            'CliLabSal' => $cliObj->CliLabSal,
            'CliLabSec' => $cliObj->CliLabSec,
            'CliLisPreId' => $cliObj->CliLisPreId,
            'CliNacId' => $cliObj->CliNacId,
            'CliNom' => $cliObj->CliNom,
            'CliNom1' => $cliObj->CliNom1,
            'CliNom2' => $cliObj->CliNom2,
            'CliNomFan' => $cliObj->CliNomFan,
            'CliNombre' => $cliObj->CliNombre,
            'CliNro' => $cliObj->CliNro,
            'CliObs' => $cliObj->CliObs,
            'CliProfId' => $cliObj->CliProfId,
            'CliRUC' => $cliObj->CliRUC,
            'CliRazon' => $cliObj->CliRazon,
            'CliSepBi' => $cliObj->CliSepBi,
            'CliSexo' => $cliObj->CliSexo,
            'CliTipEst' => $cliObj->CliTipEst,
            'CliTipId' => $cliObj->CliTipId,
            'CliTipImg' => $cliObj->CliTipImg,
            'CliTipViv' => $cliObj->CliTipViv,
            'CliTipo' => $cliObj->CliTipo,
            'CliVenId' => $cliObj->CliVenId,
            'Dir' => $cliObj->Dir,
            'LugTrabDir' => $cliObj->LugTrabDir,
            'LugTrabId' => $cliObj->LugTrabId,
            'LugTrabTel' => $cliObj->LugTrabTel,
            'Tel' => $telefono ? $telefonoNuevo : $cliObj->Tel,
            'TipDocId' => $cliObj->TipDocId
        ];
        $webserviceInfinita->ModificarCliente($cliid, $clienteModificado);
    }
}
