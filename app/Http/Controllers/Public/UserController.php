<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Validacion;
use App\Services\EmailService;
use App\Services\TigoSmsService;
use App\Services\WaService;
use App\Traits\Helpers;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;

class UserController extends Controller
{

    use Helpers;










    private function enviarMensajeDeTextoRecuperacion(String $celular, int $code){
        try {
            //$hora = Carbon::now()->format('H:i');
            $mensaje = "Blupy te ha enviado el código $code para restablecer tu contraseña";
            $numero = str_replace('+595', '0', $celular);
            $tigoService = new TigoSmsService();
            $tigoService->enviarSms($numero,$mensaje);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
