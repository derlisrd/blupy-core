<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Services\InfinitaService;
use App\Services\SupabaseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdatePerfilDigital implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $infinita = new InfinitaService();

        // Obtenemos todos los clientes
        $clientes = Cliente::all();

        foreach ($clientes as $cliente) {
            try {
                // Verificamos con InfinitaService usando la cédula del cliente
                $res = $infinita->ListarTarjetasPorDoc($cliente->cedula);

                // Convertimos a objeto para usar property_exists
                $data = (object)$res['data'];

                // Actualizamos el campo digital según la respuesta
                if (property_exists($data, 'Tarjetas') && !empty($data->Tarjetas)) {
                    $cliente->digital = 1;
                } else {
                    $cliente->digital = 0;
                }

                $cliente->save();

                // Opcional: agrega un pequeño delay para no sobrecargar el servicio externo
                sleep(1);

            } catch (\Exception $e) {
                // Log del error y continuamos con el siguiente cliente
                SupabaseService::LOG('Error al procesar cliente ID: ' . $cliente->id , $e->getMessage());
            }
        }

        SupabaseService::LOG('Job de actualización de clientes digitales completado', 'info');
    }
}
