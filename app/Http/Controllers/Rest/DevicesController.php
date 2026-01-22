<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceNewRequest;
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
            'notitoken'=>$newDevice->desktop,
            'ip'=>$newDevice->ip,
            'version'=>$newDevice->version,

            'device_id_app'=>$newDevice->device_id_app,
            'build_version'=>$newDevice->build_version,
            'time'=>$newDevice->time
        ]);

        $devices = Device::where('user_id',$newDevice->user_id)->get();

        return response()->json([
            'success'=>true,
            'results'=>$devices
        ]);
    }

    public function listado(){
        $results = DeviceNewRequest::where('aprobado',0)
        ->join('users as u','u.id','=','device_new_requests.user_id')
        ->join('clientes as c','c.id','=','u.cliente_id')
        ->get();
        return response()->json([
            'success'=>true,
            'message'=>'',
            'results'=>$results
        ]);
    }
}
