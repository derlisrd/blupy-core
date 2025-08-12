<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Device;
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
    private $infinitaService;
    private $farmaService;

    public function __construct()
    {
        $this->infinitaService = new InfinitaService();
        $this->farmaService = new FarmaService();
    }

    public function tarjetas(Request $req)
    {
        $user = $req->user();
        $cliente = $user->cliente;
        
        // Cache key único para este usuario
        $cacheKey = "tarjetas_usuario_{$user->id}";
        
        // Intentar obtener del cache (5 minutos)
        return Cache::remember($cacheKey, 300, function () use ($cliente) {
            return $this->procesarTarjetas($cliente);
        });
    }

    private function procesarTarjetas($cliente)
    {
        $results = collect();

        // Usar HTTP facade para llamadas concurrentes
        $responses = Http::pool(function (Pool $pool) use ($cliente) {
            return [
                'infinita' => $pool->timeout(10)->retry(2, 1000)->async()->get('infinita-endpoint', [
                    'cedula' => $cliente->cedula
                ]),
                'farma' => $pool->timeout(10)->retry(2, 1000)->async()->get('farma-endpoint', [
                    'cedula' => $cliente->extranjero == 1 ? null : $cliente->cedula,
                    'codigo_farma' => $cliente->extranjero == 1 ? $cliente->codigo_farma : null
                ])
            ];
        });

        // Procesar Infinita
        try {
            if ($responses['infinita']->successful()) {
                $infinitaData = $responses['infinita']->json();
                $infinitaCards = $this->buildInfinitaCards($infinitaData);
                $results = $results->concat($infinitaCards);
            }
        } catch (\Exception $e) {
            Log::error('Error procesando Infinita: ' . $e->getMessage());
            // Fallback - llamada síncrona
            $results = $results->concat($this->procesarInfinitaFallback($cliente->cedula));
        }

        // Procesar Farma
        try {
            if ($responses['farma']->successful()) {
                $farmaData = $responses['farma']->json();
                $farmaCards = $this->buildFarmaCards($farmaData, $cliente->franquicia);
                $results = $results->concat($farmaCards);
            }
        } catch (\Exception $e) {
            Log::error('Error procesando Farma: ' . $e->getMessage());
            // Fallback - llamada síncrona
            $results = $results->concat($this->procesarFarmaFallback($cliente));
        }

        return response()->json([
            'success' => true,
            'results' => $results->values()->all(),
            'message' => ''
        ]);
    }

    // Métodos fallback síncronos
    private function procesarInfinitaFallback($cedula): Collection
    {
        try {
            $resInfinita = $this->infinitaService->ListarTarjetasPorDoc($cedula);
            return $this->buildInfinitaCards($resInfinita['data'] ?? null);
        } catch (\Exception $e) {
            Log::error('Error API Infinita: ' . $e->getMessage(), ['cedula' => $cedula]);
            SupabaseService::LOG('infinita_api_error', $e->getMessage());
            return collect();
        }
    }

    private function procesarFarmaFallback($cliente): Collection
    {
        try {
            $resFarma = $cliente->extranjero == 1 
                ? $this->farmaService->clientePorCodigo($cliente->codigo_farma)
                : $this->farmaService->cliente($cliente->cedula);
            
            return $this->buildFarmaCards($resFarma['data'] ?? null, $cliente->franquicia);
        } catch (\Exception $e) {
            Log::error('Error API Farma: ' . $e->getMessage(), [
                'cedula' => $cliente->cedula,
                'extranjero' => $cliente->extranjero
            ]);
            SupabaseService::LOG('farma_api_error', $e->getMessage());
            return collect();
        }
    }

    private function buildInfinitaCards($data): Collection
    {
        if (!$data || !isset($data['Tarjetas'])) {
            return collect();
        }

        return collect($data['Tarjetas'])->map(function ($tarjeta) {
            return [
                'id' => 2,
                'descripcion' => 'Blupy Digital',
                'otorgadoPor' => 'Mi crédito S.A.',
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
                'pagoMinimo' => (int)$tarjeta['MCPagMin'],
                'deuda' => (int)$tarjeta['MTSaldo'],
                'disponible' => (int)$tarjeta['MTLinea'] - (int)$tarjeta['MTSaldo'],
                'alianzas' => [],
            ];
        });
    }

    private function buildFarmaCards($data, $franquicia): Collection
    {
        if (!$data || !isset($data['result'])) {
            return collect();
        }

        return collect($data['result'])
            ->map(function ($cliente) {
                $alianzas = collect($cliente['alianzas'])
                    ->filter(fn($alianza) => $alianza['frpaCodigo'] > 126)
                    ->map(fn($alianza) => [
                        'codigo' => $alianza['codigoAdicional'],
                        'nombre' => $alianza['alianza'],
                        'descripcion' => $alianza['alianza'],
                        'formaPagoCodigo' => $alianza['frpaCodigo'],
                        'formaPago' => $alianza['formaPago']
                    ])
                    ->values()
                    ->all();

                return [
                    'cliente' => $cliente,
                    'alianzas' => $alianzas,
                    'tieneAlianzas' => count($alianzas) > 0,
                    'esFuncionario' => $cliente['esFuncionario'] === 'S'
                ];
            })
            ->filter(fn($item) => $item['tieneAlianzas'] || $item['esFuncionario'] || $franquicia === 1)
            ->map(fn($item) => [
                'id' => 1,
                'descripcion' => $item['esFuncionario'] ? 'Blupy Farma' : 'Blupy Alianza',
                'otorgadoPor' => $item['esFuncionario'] ? 'Farma S.A.' : 'Farma por alianza',
                'tipo' => 0,
                'emision' => null,
                'condicion' => 'credito',
                'condicionVenta' => 2,
                'cuenta' => null,
                'bloqueo' => false,
                'numeroTarjeta' => null,
                'linea' => $item['cliente']['limiteCreditoTotal'],
                'pagoMinimo' => null,
                'deuda' => $item['cliente']['pendiente'],
                'disponible' => $item['cliente']['saldoDisponible'],
                'alianzas' => $item['alianzas']
            ]);
    }

    public function movimientos(Request $req)
    {
        // Cache para movimientos (1 minuto)
        $user = $req->user();
        $periodo = $req->periodo ?? Carbon::now()->format('m-Y');
        $cacheKey = "movimientos_{$user->id}_{$periodo}_{$req->cuenta}_{$req->numero_tarjeta}";
        
        return Cache::remember($cacheKey, 60, function () use ($req, $user, $periodo) {
            return $this->procesarMovimientos($req, $user, $periodo);
        });
    }

    private function procesarMovimientos(Request $req, $user, $periodo)
    {
        $validator = Validator::make($req->all(), trans('validation.movimientos'), trans('validation.movimientos.messages'));
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        try {
            $results = collect();
            
            // Determinar qué APIs llamar
            $needsInfinita = isset($req->cuenta) && $req->cuenta !== null && $req->numero_tarjeta !== null;
            $needsFarma = !isset($req->cuenta) || $req->cuenta == null || $req->cuenta == '0';
            
            if ($needsInfinita && $needsFarma) {
                // Ambas APIs - llamadas concurrentes
                $responses = Http::pool(function (Pool $pool) use ($req, $user, $periodo) {
                    return [
                        'infinita' => $pool->timeout(15)->retry(2, 1000)->async()->get('infinita-movimientos', [
                            'cuenta' => $req->cuenta,
                            'periodo' => $periodo,
                            'numero_tarjeta' => $req->numero_tarjeta
                        ]),
                        'farma' => $pool->timeout(15)->retry(2, 1000)->async()->get('farma-movimientos', [
                            'cedula' => $user->cliente->cedula,
                            'periodo' => $periodo
                        ])
                    ];
                });
                
                // Procesar respuestas concurrentes
                if ($responses['infinita']->successful()) {
                    $results = $results->concat($this->processInfinitaMovimientos($responses['infinita']->json()));
                }
                
                if ($responses['farma']->successful()) {
                    $results = $results->concat($this->processFarmaMovimientos($responses['farma']->json()));
                }
                
            } elseif ($needsInfinita) {
                // Solo Infinita
                $results = $results->concat($this->getMovimientosInfinitaSync($req->cuenta, $periodo, $req->numero_tarjeta));
                
            } elseif ($needsFarma) {
                // Solo Farma
                $results = $results->concat($this->getMovimientosFarmaSync($user->cliente->cedula, $periodo));
            }

            return response()->json([
                'success' => true, 
                'results' => $results->sortByDesc('fecha')->values()->all()
            ]);

        } catch (\Throwable $th) {
            Log::error('Error en movimientos: ' . $th->getMessage());
            return response()->json(['success' => false, 'message' => 'Ocurrió un error inesperado', 'results' => []], 500);
        }
    }

    // Métodos síncronos para movimientos
    private function getMovimientosInfinitaSync($cuenta, $periodo, $numeroTarjeta): Collection
    {
        try {
            $resInfinita = $this->infinitaService->movimientosPorFecha($cuenta, $periodo, $numeroTarjeta);
            return $this->processInfinitaMovimientos($resInfinita['data'] ?? []);
        } catch (\Exception $e) {
            Log::error('Error movimientos Infinita: ' . $e->getMessage());
            return collect();
        }
    }

    private function getMovimientosFarmaSync($cedula, $periodo): Collection
    {
        try {
            $resFarma = $this->farmaService->movimientos2($cedula, $periodo);
            return $this->processFarmaMovimientos($resFarma['data'] ?? []);
        } catch (\Exception $e) {
            Log::error('Error movimientos Farma: ' . $e->getMessage());
            return collect();
        }
    }

    // Procesadores de datos
    private function processInfinitaMovimientos($data): Collection
    {
        if (!$data || !isset($data['Tarj']['Mov'])) {
            return collect();
        }

        return collect($data['Tarj']['Mov'])->map(function ($val) {
            $date = Carbon::parse($val['TcMovFec']);
            $horario = Carbon::parse($val['TcMovCFh'], 'UTC')->setTimezone('America/Asuncion');
            
            return [
                'comercio' => $val['TcComNom'],
                'descripcion' => $val['MvDes'],
                'detalles' => $val['TcMovDes'],
                'fecha' => $date->format('Y-m-d'),
                'hora' => $horario->format('H:i:s'),
                'monto' => (int)$val['TcMovImp'],
                'numero' => $val['TcMovNro'],
            ];
        });
    }

    private function processFarmaMovimientos($data): Collection
    {
        if (!$data || !isset($data['result'])) {
            return collect();
        }

        return collect($data['result'])->map(function ($val) {
            $date = Carbon::parse($val['evenFecha'], 'UTC')->setTimezone('America/Asuncion');
            
            return [
                'comercio' => 'Farma S.A.',
                'descripcion' => $val['ticoDescripcion'],
                'detalles' => $val['ticoCodigo'] . ' ' . $val['evenNumero'],
                'fecha' => $date->format('Y-m-d'),
                'hora' => $date->format('H:i:s'),
                'monto' => $val['monto']
            ];
        });
    }

    public function extracto(Request $req)
    {
        $validator = Validator::make($req->all(), trans('validation.extracto'), trans('validation.extracto.messages'));
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        // Cache para extractos (10 minutos)
        $periodo = $req->periodo ?? Carbon::now()->format('m-Y');
        $cacheKey = "extracto_{$req->cuenta}_{$periodo}";
        
        return Cache::remember($cacheKey, 600, function () use ($req, $periodo) {
            try {
                $res = (object)$this->infinitaService->extractoCerrado($req->cuenta, 1, $periodo);
                $resultado = (object)$res->data;
                
                if ($resultado->Retorno == 'Extracto no encontrado.') {
                    return response()->json(['success' => false, 'message' => 'Extracto no disponible', 'results' => null], 404);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Extracto disponible',
                    'results' => ['url' => env('BASE_EXTRACTO') . $resultado->Url]
                ]);
            } catch (\Throwable $th) {
                Log::error('Error extracto: ' . $th->getMessage());
                return response()->json(['success' => false, 'message' => 'Error de servidor'], 500);
            }
        });
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
            Log::error('Error eliminando dispositivo: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al eliminar dispositivo'], 500);
        }
    }
}