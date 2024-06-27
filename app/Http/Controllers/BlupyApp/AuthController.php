<?php

namespace App\Http\Controllers\BlupyApp;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\SolicitudCredito;
use App\Models\User;
use App\Services\FarmaService;
use App\Services\InfinitaService;
use App\Traits\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;


class AuthController extends Controller
{
    use Helpers;
    public function register(Request $req) {

        $validator = Validator::make($req->all(),trans('validation.auth.register'), trans('validation.auth.register.messages'));
        if($validator->fails())return response()->json(['success'=>false,'messages'=>$validator->errors()->first() ], 400);

        $fotoCiFrente = null; $fotoCiDorso = null;


        try {

            DB::beginTransaction();
            $nombres = $this->separarNombres( $req->nombres );
            $apellidos = $this->separarNombres( $req->apellidos );
            $datosCliente = [
                'cedula'=>$req->cedula,
                'nombre_primero'=>$nombres[0],
                'nombre_segundo'=>$nombres[1],
                'apellido_primero'=>$apellidos[0],
                'apellido_segundo'=>$apellidos[1],
                'fecha_nacimiento'=>$req->fecha_nacimiento,
                'cedula'=>$req->cedula,
                'celular'=>$req->celular,
                'foto_ci_frente'=>$fotoCiFrente,
                'foto_ci_dorso'=>$fotoCiDorso,
                'email'=>$req->email
            ];


            $registrarEnInfinita = (object) $this->registrarInfinita((object) $datosCliente);

            if(!$registrarEnInfinita->register){
                return response()->json(['success'=>false,'message'=>'Intente mas adelante'],500);
            }

            return response()->json(['results'=>$registrarEnInfinita]);

            $cliente = Cliente::create($datosCliente);
            $user = User::create([
                'cliente_id'=>$cliente->id,
                'name'=>$req->nombres . ' '.$req->apellidos,
                'email'=>$req->email,
                'password'=> bcrypt($req->password)
            ]);

            DB::commit();
            $token = JWTAuth::fromUser($user);
            return response()->json([
                'success'=>true,
                'results'=>[
                    'token'=>$token,
                    'cedula'=>$cliente->cedula,
                    'nombre'=>$user->name,
                    'email'=>$user->email,
                    'fecha_nacimiento'=>$cliente->fecha_nacimiento
                ]
            ], 201);

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
            $ip = $req->ip();
            $executed = RateLimiter::attempt($ip,$perTwoMinutes = 5,function() {});
            if (!$executed) return response()->json(['success'=>false, 'message'=>'Demasiadas peticiones. Espere 1 minuto.' ],500);
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



    public function checkToken(){
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





    private function userInfo($cliente,$token){
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


    private function separarNombres(String $cadena) : Array{
        $nombresArray = explode(' ', $cadena);
        if (count($nombresArray) >= 2) {
            $nombre1 = $nombresArray[0];
            $nombre2 = implode(' ', array_slice($nombresArray, 1));
        } else {
            $nombre1 = $cadena;
            $nombre2 = '';
        }
        return [$nombre1,$nombre2];
    }



}
