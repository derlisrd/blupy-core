<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Services\InfinitaService;
use App\Services\TigoSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;

class UserController extends Controller
{
    private $infinitaService;
    private $tigoService;

    public function __construct()
    {
        $this->infinitaService = new InfinitaService();
        $this->tigoService = new TigoSmsService();
    }

    public function cambiarContrasena(Request $req){
        $user = $req->user();
        $cliente = $user->cliente;
        $validator = Validator::make($req->all(), trans('validation.user.newpassword'),trans('validation.user.newpassword.messages'));

        if($validator->fails())
            return response()->json(['success'=>false,'messages'=>$validator->errors()->first() ], 400);

        $ip = $req->ip();
        $executed = RateLimiter::attempt($ip,$perTwoMinutes = 3,function() {});
        if (!$executed)
            return response()->json(['success'=>false, 'message'=>'Demasiadas peticiones. Espere 1 minuto.' ],500);

        if(!$cliente)
            return response()->json(['success'=>false,'message'=>'No existe cliente'],404);

        if (!Hash::check($req->old_password, $user->password))
            return response()->json(['success'=>false,'message'=>'Contraseña incorrecta.'],401);


        $user->update(['password' => Hash::make($req->password)]);

        return response()->json(['success' => true,'message' => 'Contraseña actualizada!']);
    }




}
