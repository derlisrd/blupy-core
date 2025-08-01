<?php

namespace App\Http\Controllers\Private;

use App\Http\Controllers\Controller;
use App\Services\FarmaService;
use App\Services\InfinitaService;
use App\Services\SupabaseService;
use Illuminate\Support\Facades\Log;

class CuentasController extends Controller
{
    private $infinitaService;
    private $farmaService;

    public function __construct()
    {
        $this->infinitaService = new InfinitaService();
        $this->farmaService = new FarmaService();
    }

    public function tarjetaBlupyDigital(string $cedula)
    {
        $results = [];
        $resInfinita = (object) $this->infinitaService->ListarTarjetasPorDoc($cedula);
        $infinita = (object)$resInfinita->data;
        if (property_exists($infinita, 'Tarjetas')) {
            foreach ($infinita->Tarjetas as $val) {
                array_push($results, [
                    'id' => 2,
                    'descripcion' => 'Blupy crédito digital',
                    'otorgadoPor' => 'Mi crédito S.A.',
                    'tipo' => 1,
                    'bloqueo' => $val['MTBloq'] === "" ? false : true,
                    'condicion' => 'Contado',
                    'condicionVenta' => 1,
                    'cuenta' => $val['MaeCtaId'],
                    'principal' => $val['MTTipo'] === 'P',
                    'adicional' => $val['MTTipo'] === 'A',
                    'numeroTarjeta' => $val['MTNume'],
                    'linea' => (int)$val['MTLinea'],
                    'pagoMinimo' => (int) $val['MCPagMin'],
                    'deuda' => (int) $val['MTSaldo'],
                    'disponible' => (int) $val['MTLinea'] - (int) $val['MTSaldo'],
                    'alianzas' => []
                ]);
            }
        }
        return count($results) > 0 ? $results[0] : null;
    }

    public function tarjetas(string $cedula, int $extranjero, string $codigo_farma, int $franquicia)
    {

        $results = [];

        // Manejo de errores para API Infinita
        try {
            $resInfinita = $this->infinitaService->ListarTarjetasPorDoc($cedula);
            $infinita = (object)$resInfinita['data'];

            if (isset($infinita->Tarjetas)) {
                foreach ($infinita->Tarjetas as $val) {
                    array_push($results, [
                        'id' => 2,
                        'descripcion' => 'Blupy Digital',
                        'otorgadoPor' => 'Mi crédito S.A.',
                        'tipo' => 1,
                        'emision' => $val['MTFEmi'],
                        'bloqueo' => $val['MTBloq'] === "" ? false : true,
                        'condicion' => 'Contado',
                        'condicionVenta' => 1,
                        'cuenta' => $val['MaeCtaId'],
                        'principal' => $val['MTTipo'] === 'P',
                        'adicional' => $val['MTTipo'] === 'A',
                        'numeroTarjeta' => $val['MTNume'],
                        'linea' => (int)$val['MTLinea'],
                        'pagoMinimo' => (int) $val['MCPagMin'],
                        'deuda' => (int) $val['MTSaldo'],
                        'disponible' => (int) $val['MTLinea'] - (int) $val['MTSaldo'],
                        'alianzas' => [],
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Log del error pero continúa la ejecución
            Log::error('Error al consultar API Infinita: ' . $e->getMessage(), [
                'cedula' => $cedula,
                'trace' => $e->getTraceAsString()
            ]);

            // Opcionalmente enviar notificación a Supabase
            SupabaseService::LOG('infinita_api_error', $e->getMessage());
        }
        // Manejo de errores para API Farma
        $resFarma = null;
        try {
            if ($extranjero == 1) {
                $resFarma = $this->farmaService->clientePorCodigo($codigo_farma);
            } else {
                $resFarma = $this->farmaService->cliente($cedula);
            }
        } catch (\Exception $e) {
            // Log del error pero continúa la ejecución
            Log::error('Error al consultar API Farma: ' . $e->getMessage(), [
                'cedula' => $cedula,
                'extranjero' => $extranjero,
                'codigo_farma' => $codigo_farma,
                'trace' => $e->getTraceAsString()
            ]);

            SupabaseService::LOG('farma_api_error', $e->getMessage());

            // Continúa con $resFarma = null
        }

        // El resto del código se mantiene igual
        if (!$resFarma) {
            return $results;
        }

        $farma = (object) $resFarma['data'];
        if (property_exists($farma, 'result')) {
            foreach ($farma->result as $val) {
                $alianzas = [];
                foreach ($val['alianzas'] as $alianza) {
                    if ($alianza['frpaCodigo'] > 126) {
                        array_push($alianzas, [
                            'codigo' => $alianza['codigoAdicional'],
                            'nombre' => $alianza['alianza'],
                            'descripcion' => $alianza['alianza'],
                            'formaPagoCodigo' => $alianza['frpaCodigo'],
                            'formaPago' => $alianza['formaPago']
                        ]);
                    }
                }
                if (count($alianzas) > 0 || $val['esFuncionario'] === 'S' || $franquicia === 1) {
                    array_push($results, [
                        'id' => 1,
                        'descripcion' => $val['esFuncionario'] === 'S' ? 'Blupy Farma' : 'Blupy Alianza',
                        'otorgadoPor' => $val['esFuncionario'] === 'S' ? 'Farma S.A.' : 'Farma por alianza',
                        'tipo' => 0,
                        'emision' => null,
                        'condicion' => 'credito',
                        'condicionVenta' => 2,
                        'cuenta' => null,
                        'bloqueo' => false,
                        'numeroTarjeta' => null,
                        'linea' => $val['limiteCreditoTotal'], //$val['clerLimiteCredito'] +  $val['clerLimiteCreditoAdic']
                        'pagoMinimo' => null,
                        'deuda' => $val['pendiente'],
                        'disponible' => $val['saldoDisponible'], // ($val['clerLimiteCredito'] +  $val['clerLimiteCreditoAdic']) - $val['pendiente'],
                        'alianzas' => $alianzas
                    ]);
                }
            }
        }

        return $results;
    }
}
