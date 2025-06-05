<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\PermisosOtorgado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $req){

    
        $validator = Validator::make($req->all(),trans('validation.rest.login'), trans('validation.rest.login.messages'));
        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

            $ip = $req->ip();
            $rateKey = "loginrest:$ip";

            if (RateLimiter::tooManyAttempts($rateKey, 5)) {
                return response()->json(['success' => false, 'message' => 'Demasiadas peticiones. Espere 1 minuto.'], 429);
            }
            RateLimiter::hit($rateKey, 60);

        $user = Admin::where('email',$req->email)->where('role','admin')->first();
        if(!$user)
            return response()->json(['success'=>false,'message'=>'Error de credenciales'],401);

        $credentials = ['email'=>$req->email, 'password'=>$req->password];
        $token = auth('admin')->attempt($credentials);
        
        if($token){
            return response()->json([
                'success'=>true,
                'results'=>[
                    'id'=>$user->id,
                    'email'=>$user->email,
                    'name'=>$user->name,
                    'token'=>$token
                ],
            ]);
        }
        return response()->json([
            'success'=>false, 'message'=>"Error de credenciales"
        ],401); 
    }


    public function checkToken(Request $req){
        try {
            // Intenta autenticar al usuario con el token
            $user = JWTAuth::parseToken()->authenticate();
            return response()->json(['success' => true, 'message' => 'Token válido', 'user' => $user]);
        } catch (TokenExpiredException $e) {
            return response()->json(['success' => false, 'message' => 'Token expirado'],401);
        } catch (TokenInvalidException $e) {
            return response()->json(['success' => false, 'message' => 'Token inválido'],401);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Token no encontrado'],401);
        }
    }

}
