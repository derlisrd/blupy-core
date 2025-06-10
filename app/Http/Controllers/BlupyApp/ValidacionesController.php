<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Validacion;
use App\Services\TigoSmsService;
use App\Services\WaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class ValidacionesController extends Controller
{

    public function enviameCodigoSMS(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), ['id' => 'required'], ['id.required' => 'El id obligatorio']);
            if ($validator->fails())
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

            $ip = $req->ip();
            $rateKey = "enviarCodigoSms:$ip";

            if (RateLimiter::tooManyAttempts($rateKey, 5)) {
                return response()->json(['success' => false, 'message' => 'Demasiadas peticiones. Espere 1 minuto.'], 429);
            }
            RateLimiter::hit($rateKey, 60);


            $results = Validacion::where('id', $req->id)->where('validado', 0)->first();
            if ($results) {
                $cliente = Cliente::find($results->cliente_id);
                $validacion = Validacion::find($req->id);
                $validacion->celular = $cliente->celular;
                $validacion->save();
                $this->enviarMensajeDeTexto($cliente->celular, $results->codigo);
                return response()->json(['success' => true, 'message' => 'Mensaje enviado al ' . $cliente->celular]);
            }

            return response()->json(['success' => false, 'message' => 'No existe codigo'], 404);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => 'Error en el servidor.'], 500);
        }
    }



    public function validarEmail(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), trans('validation.verificaciones.email'), trans('validation.verificaciones.email.messages'));
            if ($validator->fails())
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

            $ip = $req->ip();
            $executed = RateLimiter::attempt($ip, $perTwoMinutes = 10, function () {});
            if (!$executed)
                return response()->json(['success' => false, 'message' => 'Demasiadas peticiones. Espere 1 minuto.'], 500);

            $randomNumber = random_int(1000, 9999);
            $this->enviarEmail($req->email, $randomNumber);
            $validacion = Validacion::create(['codigo' => $randomNumber, 'forma' => 0, 'email' => $req->email, 'origen' => 'email']);

            return response()->json(['success' => true, 'results' => ['id' => $validacion->id], 'message' => 'Email enviado']);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => 'Error en el servidor'], 500);
        }
    }


    // cliente quiere registrarse y no le llega el sms y no le llega el sms entonces usa esta funcion
    public function reEnviarCodigoPorWa(Request $req){
        try{
            $ip = $req->ip();
            $rateKey = "codigowa:$ip";

            if (RateLimiter::tooManyAttempts($rateKey, 5)) {
                return response()->json(['success' => false, 'message' => 'Demasiadas peticiones. Espere 1 minuto.'], 429);
            }
            RateLimiter::hit($rateKey, 60);

            $validator = Validator::make($req->all(), ['validacion_id' => 'required']);
            if ($validator->fails())
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
            $validacion = Validacion::where('id', $req->validacion_id)->where('validado', 0)->first();
            if(!$validacion){
                return response()->json(['success' => false, 'message' => 'No existe codigo'], 404);
            }            
            $mensaje = "Tu código de verificación para Blupy es " . $validacion->codigo . ". Este código es válido por 10 minutos.";
            $numeroTelefonoWa = '595' . substr($validacion->celular, 1);
            (new WaService())->send($numeroTelefonoWa, $mensaje);

            return response()->json([
                'success' => true,
                'message' => 'Codigo enviado. Verifique su whatsapp',
            ]);

        }
        catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => 'Error en el servidor'], 500);
        }
    }




    public function confirmarEmail(Request $req)
    {
        $validator = Validator::make($req->all(), trans('validation.verificaciones.confirmar'), trans('validation.verificaciones.confirmar.messages'));
        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        $ip = $req->ip();
        $executed = RateLimiter::attempt($ip, $perTwoMinutes = 5, function () {});
        if (!$executed)
            return response()->json(['success' => false, 'message' => 'Demasiadas peticiones. Espere 1 minuto.'], 500);

        $validacion = Validacion::where('id', $req->id)->where('validado', 0)->where('codigo', $req->codigo)->first();
        if (!$validacion)
            return response()->json(['success' => false, 'message' => 'Codigo invalido'], 403);

        $fechaCreado = Carbon::parse($validacion->created_at);
        $fechaActual = Carbon::now();
        $diferenciaEnMinutos = $fechaCreado->diffInMinutes($fechaActual);

        if ($diferenciaEnMinutos >= 10)
            return response()->json(['success' => false, 'message' => 'Código ha expirado'], 401);

        $validacion->validado = 1;
        $validacion->save();

        return response()->json(['success' => true, 'message' => 'Email verificado.']);
    }






    public function validarTelefono(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), trans('validation.verificaciones.celular'), trans('validation.verificaciones.celular.messages'));
            if ($validator->fails())
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);


            $ip = $req->ip();
            $rateKey = $ip . '|' . $req->celular;

            if (RateLimiter::tooManyAttempts($rateKey, 3)) {
                return response()->json(['success' => false, 'message' => 'Demasiadas peticiones. Espere 1 minuto.'], 429);
            }
            RateLimiter::hit($rateKey, 60);


            $randomNumber = random_int(1000, 9999);

            $celularFormateado = $req->celular;

            $numeroTelefonoWa = $numeroTelefonoWa = '595' . substr($req->celular, 1);
            (new WaService())->send($numeroTelefonoWa, "Tu codigo de verificacion para Blupy es: $randomNumber. Verificacion de cuenta.");
            $this->enviarMensajeDeTexto($celularFormateado, $randomNumber);
            
            $validacion = Validacion::create(['codigo' => $randomNumber, 'forma' => 1, 'celular' => $req->celular, 'origen' => 'registro_celular']);

            return response()->json(['success' => true, 'results' => ['id' => $validacion->id], 'message' => 'Mensaje enviado']);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json(['success' => false, 'message' => 'Error en el servidor'], 500);
        }
    }





    public function confirmarTelefono(Request $req)
    {
        $validator = Validator::make($req->all(), trans('validation.verificaciones.confirmar'), trans('validation.verificaciones.confirmar.messages'));
        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        $validacion = Validacion::where('id', $req->id)->where('validado', 0)->where('codigo', $req->codigo)->first();
        if (!$validacion)
            return response()->json(['success' => false, 'message' => 'Codigo invalido'], 401);

        $fechaCreado = Carbon::parse($validacion->created_at);
        $fechaActual = Carbon::now();
        $diferenciaEnMinutos = $fechaCreado->diffInMinutes($fechaActual);

        if ($diferenciaEnMinutos >= 10)
            return response()->json(['success' => false, 'message' => 'Código ha expirado'], 401);

        $validacion->validado = 1;
        $validacion->save();

        return response()->json(['success' => true, 'message' => 'Telefono verificado.']);
    }


    private function enviarEmail(String $email, int $code)
    {
        $datos = [
            'email' => $email,
            'code' => $code
        ];
        try {
            Mail::send('email.validar', ['code' => $code], function ($message) use ($datos) {
                $message->subject('[' . $datos['code'] . '] Blupy confirmacion');
                $message->to($datos['email']);
            });
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    private function enviarMensajeDeTexto(String $celular, int $code)
    {
        try {
            //$hora = Carbon::now()->format('H:i');
            $mensaje = "Tu codigo de verificacion para Blupy es: $code. Verificacion de cuenta.";
            $numero = str_replace('+595', '0', $celular);
            $tigoService = new TigoSmsService();
            $tigoService->enviarSms($numero, $mensaje);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
