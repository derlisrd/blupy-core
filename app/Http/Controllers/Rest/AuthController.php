<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $req){
        $validator = Validator::make($req->all(),trans('validation.rest.login'), trans('validation.rest.login.messages'));
        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

        $ip = $req->ip();
        $executed = RateLimiter::attempt($ip,$perTwoMinutes = 5,function() {});
        if (!$executed)
            return response()->json(['success'=>false, 'message'=>'Demasiadas peticiones. Espere 1 minuto.' ],500);

        $user = User::where('email',$req->email)->where('rol',1)->first();
        if(!$user)
            return response()->json(['success'=>false,'message'=>'Error de credenciales'],401);

        $credentials = ['email'=>$req->email, 'password'=>$req->password];
        $token = JWTAuth::attempt($credentials);

        if($token){
            return response()->json([
                'success'=>true,
                'results'=>[
                    'token'=>$token
                ],
            ]);
        }
        return response()->json([
            'success'=>false, 'message'=>"Error de credenciales"
        ],401);
    }

}
