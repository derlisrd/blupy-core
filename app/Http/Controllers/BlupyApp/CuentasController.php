<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\User;
use App\Services\FarmaService;
use App\Services\InfinitaService;
use App\Services\SupabaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;

class CuentasController extends Controller
{
    
    

    public function eliminarCuenta(Request $req){
        $user = User::find($req->user()->id);

        $user->active = 0;

        $user->save();

        return response()->json([
            'success'=>true,
            'message'=>'Su cuenta ha sido eliminada'
        ]);
    }



    public function tarjetas2(Request $req){
        $user = $req->user();
        $cliente = $user->cliente;

        $tarjetasResults = [];
        
        $infinitaCards = $this->getTarjetasInfinita($cliente->cedula);
        $tarjetasResults = array_merge($tarjetasResults, $infinitaCards);

        $tarjetasFarma = $this->getTarjetasFarma($cliente->cedula);
        $tarjetasResults = array_merge($tarjetasResults, $tarjetasFarma);

        $tarjetasBlupyEmpresas = $this->getTarjetaBlupyEmpresas($cliente->cedula);
        $tarjetasResults = array_merge($tarjetasResults, $tarjetasBlupyEmpresas);
        

        return response()->json([
            'success'=>true,
            'message'=>'',
            'results'=>$tarjetasResults,
        ]);
    }

    private function getTarjetaBlupyEmpresas($cedula){
        $tarjetasResults = [];
        $farmaService = new FarmaService();
        $farmaCards = $farmaService->empresaAutorizados($cedula);
        $farmaCardData = $farmaCards['data'];

        if ($farmaCardData && isset($farmaCardData['result'])) {
            $result = $farmaCardData['result'];
            if ($result != null) {
                $tarjetasResults[] = [
                    'id' => 3,
                    'descripcion' => 'Blupy empresa',
                    'otorgadoPor' => $result['empresa'],
                    'ruc' => $result['ruc'],
                    'tipo' => 0,
                    'emision' => null,
                    'bloqueo' => false,
                    'condicion' => 'Credito',
                    'condicionVenta' => 2,
                    'cuenta' => null,
                    'principal' => false,
                    'adicional' => false,
                    'numeroTarjeta' => 0,
                    'linea' => $result['clerLimiteCredito'],
                    'pagoMinimo' => 0,
                    'deuda' => $result['deuda'],
                    'disponible' => $result['clerLimiteCredito'] - $result['deuda'],
                    'alianzas' => null,
                ];
            }
        }

        return $tarjetasResults;
    }


    private function getTarjetasFarma($cedula){
        
        $tarjetasResults = [];
        $farmaService = new FarmaService();
        $farmaCardsF = $farmaService->cliente2($cedula);
        $farmaCardDataF = $farmaCardsF['data'];



        if ($farmaCardDataF && isset($farmaCardDataF['result'])) {
            $tarjetasFarma = $farmaCardDataF['result'];
            if ($tarjetasFarma != null) {

                $funcionario = $tarjetasFarma['funcionario'];
                $alianza = $tarjetasFarma['alianza'] ?? null;

                if($funcionario === false && $alianza === null){
                    return [];
                }

                $linea = $tarjetasFarma['clerLimiteCredito'];

                $hoy = Carbon::now()->startOfDay();
                $fechaVigencia = Carbon::parse($tarjetasFarma['clerFchFinVigencia'])
                ->setTimezone('America/Asuncion')
                ->startOfDay();
                if ($fechaVigencia >= $hoy) { // aqui debo comparar la fecha
                    $linea = $linea + $tarjetasFarma['clerLimiteCreditoAdic'];
                }
                $deuda = $tarjetasFarma['deuda'];

                $disponible2 = $linea - $deuda;
                $disponible = $disponible2 < 0 ? 0 : $disponible2;

                $tarjetasResults[] = [
                    'id' => 1,
                    'descripcion' => $alianza ? 'Blupy Alianza' : 'Blupy Farma',
                    'otorgadoPor' => 'Farma S.A.',
                    'ruc' => null,
                    'tipo' => 0,
                    'emision' => null,
                    'bloqueo' => false,
                    'condicion' => 'Credito',
                    'condicionVenta' => 2,
                    'cuenta' => null,
                    'principal' => false,
                    'adicional' => false,
                    'numeroTarjeta' => 0,
                    'linea' => $linea,
                    'pagoMinimo' => 0,
                    'deuda' => $deuda,
                    'disponible' => $disponible,
                    'alianzas' => $alianza,
                    'funcionario' => $funcionario
                ];
            }
        }
        return $tarjetasResults;
    }


    private function getTarjetasInfinita($cedula){
        
        $tarjetasResults = [];
        try {
            $infinitaService = new InfinitaService();
            $infinitaCards = $infinitaService->ListarTarjetasPorDoc($cedula);

            $infinitaCardData = $infinitaCards['data'];

            if ($infinitaCardData &&  isset($infinitaCardData['Tarjetas'])) {

                $tarjetasInfinita = $infinitaCardData['Tarjetas'];
                foreach ($tarjetasInfinita as $tarjeta) {
                    $linea = (int)$tarjeta['MTLinea'];
                    $deuda = (int)$tarjeta['MTSaldo'];
                    $disponible2 = $linea - $deuda;
                    $disponible = $disponible2 < 0 ? 0 : $disponible2;
                    $minimo =  0; //(int)$tarjeta['MCPagMin'];

                    $tarjetasResults[] = [
                        'id' => 2,
                        'descripcion' => 'Blupy Digital',
                        'otorgadoPor' => 'Mi crédito S.A.',
                        'ruc' => null,
                        'tipo' => 1,
                        'emision' => $tarjeta['MTFEmi'],
                        'bloqueo' => !empty($tarjeta['MTBloq']),
                        'condicion' => 'Contado',
                        'condicionVenta' => 1,
                        'cuenta' => $tarjeta['MaeCtaId'],
                        'principal' => $tarjeta['MTTipo'] === 'P',
                        'adicional' => $tarjeta['MTTipo'] === 'A',
                        'numeroTarjeta' => $tarjeta['MTNume'],
                        'linea' => (int)$tarjeta['MTLinea'],
                        'pagoMinimo' => $minimo,
                        'deuda' => $deuda,
                        'disponible' => $disponible,
                        'alianzas' => null,
                    ];
                }
            }
            return $tarjetasResults;
        } catch (\Throwable $th) {
            return [];
        }
        
    }




    public function movimientos(Request $req){

        return response()->json([
            'success'=>true,
            'message'=>'',
            'results'=>[]
        ]);

    }


  


    



    public function extracto(Request $req)
    {
        $validator = Validator::make($req->all(), trans('validation.extracto'), trans('validation.extracto.messages'));
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        // Cache para extractos (10 minutos)
        $infinitaService = new InfinitaService();
        $periodo = $req->periodo ?? Carbon::now()->format('m-Y');
        //$cacheKey = "extracto_{$req->cuenta}_{$periodo}";
        
        //return Cache::remember($cacheKey, 600, function () use ($req, $periodo, $infinitaService) {
            try {
                $res = $infinitaService->extractoCerrado($req->cuenta, 1, $periodo);
                $resultado = (object)$res['data'];
                
                if ($resultado->Retorno == 'Extracto no encontrado.') {
                    return response()->json(['success' => false, 'message' => 'Extracto no disponible', 'results' => null], 404);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Extracto disponible',
                    'results' => ['url' => env('BASE_EXTRACTO') . $resultado->Url]
                ]);
            } catch (\Throwable $th) {
                Log::error($th->getMessage());
                return response()->json(['success' => false, 'message' => 'Error de servidor'], 500);
            }
        //});
    }

    public function misDispositivos(Request $req)
    {
        $user = $req->user();
        
        // Cache para dispositivos (1 minuto)
        return Cache::remember("dispositivos_usuario_{$user->id}", 60, function () use ($user) {
            return response()->json(['success' => true, 'results' => $user->devices]);
        });
    }

    public function eliminarDispositivo(Request $req)
    {
        try {
            $device = Device::findOrFail($req->id);
            $user = $req->user();
            
            // Verificar que el dispositivo pertenece al usuario
            if ($device->user_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Dispositivo no autorizado'], 403);
            }
            
            $device->delete();
            
            // Limpiar cache de dispositivos
            Cache::forget("dispositivos_usuario_{$user->id}");
            
            return response()->json([
                'success' => true, 
                'message' => 'Dispositivo eliminado con éxito', 
                'results' => $user->fresh()->devices
            ]);
        } catch (\Exception $e) {
            
            return response()->json(['success' => false, 'message' => 'Error al eliminar dispositivo'], 500);
        }
    }
}