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
                            'adicional' => $venta['clieCodigoAdicional'],
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
                            'forma_venta' => $venta['ventTipo'],
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




    /*
        public function __construct($fecha = null)
    {
        // Si no se proporciona fecha, usar la fecha inicial del loop
        $this->fecha = $fecha ?? '2023-07-01';
    }
    public function handle()
    {
        // Establecer fechas de inicio y fin
        $fechaInicio = Carbon::parse('2023-07-01');
        $fechaFin = Carbon::now();

        // Si se pasa una fecha específica, usarla
        if ($this->fecha !== '2023-07-01') {
            $fechaInicio = Carbon::parse($this->fecha);
        }

        // Mientras la fecha de inicio sea menor o igual a la fecha final
        while ($fechaInicio->lte($fechaFin)) {
            try {
                $fechaActual = $fechaInicio->format('Y-m-d');

                $farmaService = new FarmaService();
                $res = $farmaService->ventasDeFecha($fechaActual);
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
                                'adicional' => $venta['clieCodigoAdicional'],
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
                                'forma_venta' => $venta['ventTipo'],
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

                SupabaseService::LOG('schedule_plano', 'ingresadas fecha ' . $fechaActual);
            } catch (\Exception $e) {
                SupabaseService::LOG('error', "Error crítico en fecha {$fechaActual}: {$e->getMessage()}");
            }

            // Avanzar al siguiente día
            $fechaInicio->addDay();
        }
    }


    public static function encolarProcesamientoHistorico()
    {
        $fechaInicio = Carbon::parse('2023-07-01');
        $fechaFin = Carbon::now();

        while ($fechaInicio->lte($fechaFin)) {
            self::dispatch($fechaInicio->format('Y-m-d'));
            $fechaInicio->addDay();
        }
    } */


}
