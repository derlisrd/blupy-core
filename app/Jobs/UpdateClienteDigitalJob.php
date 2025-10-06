<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Services\InfinitaService;
use App\Services\SupabaseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateClienteDigitalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 3;
    public $backoff = 60;

    private $clienteId;

    /**
     * Constructor para procesar un cliente específico
     */
    public function __construct($clienteId = null)
    {
        $this->clienteId = $clienteId;
    }

    /**
     * Execute the job.
     */
    public function handle(InfinitaService $infinitaService): void
    {
        // Si se especifica un cliente, procesar solo ese
        if ($this->clienteId) {
            $this->procesarCliente($this->clienteId, $infinitaService);
            return;
        }

        // Procesar en chunks para evitar consumir mucha memoria
        Cliente::where('digital', 1)
            ->select('id', 'cedula')
            ->chunkById(100, function ($clientes) use ($infinitaService) {
                $updates = [];
                
                foreach ($clientes as $cliente) {
                    try {
                        $existe = $this->verificarTarjetaDigital($cliente->cedula, $infinitaService);
                        
                        // Acumular updates para hacer bulk update
                        $updates[$existe][] = $cliente->id;
                        
                    } catch (\Exception $e) {
                        Log::error("Error procesando cliente {$cliente->id}: " . $e->getMessage());
                    }
                }
                
                // Bulk update para mejor rendimiento
                $this->bulkUpdate($updates);
            });
    }

    /**
     * Procesar un cliente individual
     */
    private function procesarCliente($clienteId, InfinitaService $infinitaService): void
    {
        $cliente = Cliente::select('id', 'cedula', 'digital')
            ->find($clienteId);

        if (!$cliente) {
            return;
        }

        try {
            $existe = $this->verificarTarjetaDigital($cliente->cedula, $infinitaService);
            
            if ($cliente->digital !== $existe) {
                $cliente->digital = $existe;
                $cliente->save();
            }
        } catch (\Exception $e) {
            Log::error("Error procesando cliente {$clienteId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verificar si el cliente tiene tarjeta digital
     */
    private function verificarTarjetaDigital(string $cedula, InfinitaService $infinitaService): int
    {
        $resInfinita = $infinitaService->ListarTarjetasPorDoc($cedula);
        $infinita = (object)($resInfinita['data'] ?? []);
        
        return property_exists($infinita, 'Tarjetas') ? 1 : 0;
    }

    /**
     * Actualizar clientes en bulk
     */
    private function bulkUpdate(array $updates): void
    {
        DB::transaction(function () use ($updates) {
            foreach ($updates as $digitalValue => $clienteIds) {
                if (empty($clienteIds)) {
                    continue;
                }
                
                Cliente::whereIn('id', $clienteIds)
                    ->update(['digital' => $digitalValue]);
            }
        });
        SupabaseService::LOG('update_cliente_digital','Job');
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('UpdateClienteDigitalJob failed: ' . $exception->getMessage(), [
            'cliente_id' => $this->clienteId,
            'trace' => $exception->getTraceAsString()
        ]);
    }
}