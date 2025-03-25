<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Models\Venta;
use App\Services\FarmaService;
use App\Services\SupabaseService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcesarVentasDelDiaFarmaJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fecha;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($fecha)
    {
        $this->fecha = $fecha;
    }

    public function handle()
    {
        try {
            $farmaService = new FarmaService();
            $res = $farmaService->ventasDeFecha($this->fecha);
            $data = (object) $res['data'];

            if (property_exists($data, 'result')) {
                $ventas = $data->result;

                // Obtener todos los códigos de ventas
                $codigosVentas = array_column($ventas, 'ventCodigo');

                // Verificar ventas existentes en una sola consulta
                $ventasExistentes = Venta::whereIn('codigo', $codigosVentas)
                    ->pluck('codigo')
                    ->toArray();

                // Obtener todas las cédulas de clientes para evitar consultas individuales
                $cedulasClientes = array_unique(array_column($ventas, 'cedula'));
                $clientes = Cliente::whereIn('cedula', $cedulasClientes)
                    ->pluck('id', 'cedula')
                    ->toArray();

                // Preparar datos para la inserción
                $insertData = [];
                foreach ($ventas as $venta) {
                    if (!in_array($venta['ventCodigo'], $ventasExistentes)) {
                        $fechaFormateada = Carbon::parse($venta['ventFecha'], 'UTC')
                            ->setTimezone('America/Asuncion')
                            ->format('Y-m-d H:i:s');

                        $cliente_id = $clientes[$venta['cedula']] ?? null;
                        $importe = $venta['ventOperacion'] == 'VENT' ? $venta['ventTotNeto'] : $venta['ventTotNeto'] * (-1);
                        $insertData[] = [
                            'cliente_id' => $cliente_id,
                            'codigo' => $venta['ventCodigo'],
                            'documento' => $venta['cedula'],
                            'adicional' => $venta['codigoAdicional'],
                            'factura_numero' => $venta['ventNumero'],
                            'importe' => $venta['ventTotBruto'],
                            'descuento' => $venta['ventTotDescuento'],
                            'importe_final' => $importe,
                            'forma_pago' => $venta['frpaDescripcion'],
                            'forma_codigo' => $venta['frpaCodigo'],
                            'descripcion' => null,
                            'operacion' => $venta['ventOperacion'],
                            'sucursal' => $venta['estrDescripcion'],
                            'codigo_sucursal' => $venta['estrCodigo'],
                            'fecha' => $fechaFormateada,
                            'forma_venta' => $venta['ventOperacion'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                // Insertar en lotes para mejorar el rendimiento
                if (!empty($insertData)) {
                    foreach (array_chunk($insertData, 500) as $chunk) {
                        Venta::insert($chunk);
                    }
                }
            }
        } catch (\Exception $e) {
            SupabaseService::LOG('error', "Error crítico: {$e->getMessage()}");
        }

        SupabaseService::LOG('schedule_plano', 'ingresadas fecha ' . $this->fecha);
    }
}
