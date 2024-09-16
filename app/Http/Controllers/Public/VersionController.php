<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Version;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VersionController extends Controller
{
    public function verificarVersion(Request $req){

        $validator = Validator::make($req->all(),[
            'id'=>'required|numeric|min:1|max:2'
        ],[
            'required'=>'id es obligatorio',
            'numeric'=>'id es numerico',
            'min'=>'id es minimo 1',
            'max'=>'id es maximo 2'
        ]);
        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

        // 1 android
        // 2 ios
        $version = Version::find($req->id);


        return response()->json([
            'success'=>true,
            'results'=>[
                'number'=> $version->numero,
                'version'=> $version->version,
                'link' => $version->link,
                'device' => $version->dispositivo
            ]
        ]);
    }
}
