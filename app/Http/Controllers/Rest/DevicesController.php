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
            'version'=>$newDevice->version
        ]);

        return response()->json([
            'success'=>true,
            'results'=>null
        ]);
    }

    public function listado(){
        $results = DeviceNewRequest::where('aprobado',0)->get();
        return response()->json([
            'success'=>true,
            'message'=>'',
            'results'=>$results
        ]);
    }
}
