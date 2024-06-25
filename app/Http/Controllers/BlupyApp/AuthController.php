<?php

namespace App\Http\Controllers\BlupyApp;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class AuthController extends Controller
{
    public function register(Request $req) {
        $validator = Validator::make($req->all(),trans('validation.auth.register'), trans('validation.auth.register.messages'));

        if($validator->fails()){
            return response()->json(['success'=>false,'messages'=>$validator->errors()->first() ], 400);
        }
        $fotoCiFrente = null; $fotoCiDorso = null;
        try {
            DB::beginTransaction();
            $cliente = Cliente::create([
                'cedula'=>$req->cedula,
                'nombre_primero'=>$req->nombrePrimero,
                'nombre_segundo'=>$req->nombreSegundo,
                'apellido_primero'=>$req->apellidoPrimero,
                'apellido_segundo'=>$req->apellidoSegundo,
                'fecha_nacimiento'=>$req->fechaNacimiento,
                'celular'=>$req->celular,
                'foto_ci_frente'=>$fotoCiFrente,
                'foto_ci_dorso'=>$fotoCiDorso,
            ]);
            $user = User::create([
                'cliente_id'=>$cliente->id,
                'name'=>$req->name,
                'email'=>$req->email,
                'password'=> bcrypt($req->password)
            ]);
            DB::commit();
            return response()->json(['success'=>true,], 201);

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);
            return response()->json(['success'=>false, 'message'=>'Error de servidor']);
        }
    }








    public function login(Request $req){
        try {
            $validator = Validator::make($req->all(),trans('validation.auth.login'), trans('validation.auth.login.messages'));

            if($validator->fails()){
                return response()->json(['success'=>false,'messages'=>$validator->errors()->first() ], 400);
            }
            $cedula = $req->cedula; $password = $req->password;

            $cliente = Cliente::where('cedula',$cedula)->first();
            if($cliente){
                $user = $cliente->user;
                $credentials = ['email'=>$user->email, 'password'=>$password];
                $token = JWTAuth::attempt($credentials);

                if($token){
                    return response()->json([
                        'success'=>true,
                        'results'=>$this->userInfo($cliente,$token)
                        ]
                    );
                }
            }

            return response()->json([
                'success'=>false, 'message'=>"Error de credenciales"
            ],401);

        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'success'=>false,'message'=>"Error de servidor"
            ],500);
        }
    }



    public function check(){
        try {
            JWTAuth::check(JWTAuth::getToken());
            return response()->json([
                'success'=>true, 'message'=>'valid'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['success'=>false,'message'=>'Error de servidor'],500);
        }
    }



    public function logout(){
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['success'=>true]);
        } catch (\Throwable $th) {
            return response()->json(['success'=>false,'message'=>'Error de servidor'],500);
        }
    }




    public function refreshToken(){
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            return response()->json(['token'=>$token]);
        } catch (\Throwable $th) {
            return response()->json(['success'=>false,'message'=>'Error de servidor'],500);
        }
    }





    protected function userInfo($cliente,$token){
        return [
            'name'=>$cliente->user->name,
            'nombres'=>trim($cliente->nombre_primero . ' ' . $cliente->nombre_segundo),
            'apellidos'=>trim($cliente->apellido_primero . ' ' . $cliente->apellido_segundo),
            'cedula'=>$cliente->cedula,
            'fechaNacimiento'=>$cliente->fecha_nacimiento,
            'email'=>$cliente->user->email,
            'telefono'=>$cliente->celular,
            'celular'=>$cliente->celular,
            'solicitudCredito'=>$cliente->solicitud_credito,
            'funcionario'=>$cliente->funcionario,
            'aso'=>$cliente->asofarma,
            'token'=>$token
        ];
    }



}
