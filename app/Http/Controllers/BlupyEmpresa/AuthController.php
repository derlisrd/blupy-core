<?php

namespace App\Http\Controllers\BlupyEmpresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'ruc' => 'required',
            'doc_autorizado' => 'required',
            'password' => 'required',
        ]);
        if ($validator->fails())
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        $ip = $req->ip();
        $rateKey = "login:$ip";

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return response()->json(['success' => false, 'message' => 'Demasiadas peticiones. Espere 1 minuto.'], 400);
        }
        RateLimiter::hit($rateKey, 60);

        $empresa = Empresa::where('empresas.ruc', $req->ruc)
        ->where('empresas.doc_autorizado', $req->doc_autorizado)
        ->join('users', 'users.id', '=', 'empresas.user_id')
        ->select('users.id', 'users.email', 'users.password', 'empresas.ruc', 'empresas.razon_social', 'empresas.telefono')
        ->first();

        if (!$empresa) return response()->json(['success' => false, 'message' => 'Usuario no existe'], 404);

        $credentials = ['email' => $empresa->email, 'password' => $req->password];
        $token = JWTAuth::attempt($credentials);

        return response()->json([
            'success' =>true,
            'results' => [
                'token' => $token,
                'type' => 'Bearer',
                'empresa' => $empresa
            ]
        ]);
    }
}
