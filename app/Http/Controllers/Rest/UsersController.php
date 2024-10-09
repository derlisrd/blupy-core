<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Jobs\UpdatePerfilJobs;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UsersController extends Controller
{
    public function restablecerContrasena(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => ['required','exists:users,id'],
            'password'=>['required','string','min:8']
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=> $validator->errors()->first()
            ], 400);
        }

        $user = User::find($request->id);
        if(!$user){
            return response()->json([
                'success'=>false,
                'message'=>'User no existe'
            ],404);
        }
        $user->password = Hash::make($request->password);
        $user->save();
        return response()->json([
            'success'=>true,
            'message'=>'Restablecido'
        ]);
    }


    public function actualizarPerfiles(){
        UpdatePerfilJobs::dispatch();
        return response()->json([
            'success'=>true,
            'message'=>'Actualizando...'
        ]);
    }
}
