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
            $res = (object)$farmaService->ventasRendidas($this->fecha);
            $data = (object) $res->data;
            if (property_exists($data, 'result')) {
                $ventas = $data->result;
                $codigosVentas = array_column($ventas, 'ventCodigo');
                $ventasExistentes = Venta::whereIn('codigo', $codigosVentas)->pluck('codigo')->toArray();

                $nuevasVentas = array_filter($ventas, function ($venta) use ($ventasExistentes) {
                    return !in_array($venta['ventCodigo'], $ventasExistentes);
                });
                $insertData = array_map(function ($v) {
                    $fechaFormateada = Carbon::parse($v['ventFecha'], 'UTC')
                        ->setTimezone('America/Asuncion')
                        ->format('Y-m-d H:i:s');

                    $cliente = Cliente::where('cedula', $v['cedula'])->first();
                    $cliente_id = $cliente ? $cliente->id : null;

                    return [
                        'cliente_id' => $cliente_id,
                        'codigo' => $v['ventCodigo'],
                        'documento' => $v['cedula'],
                        'adicional' => $v['clieCodigoAdicional'],
                        'factura_numero' => $v['ventNumero'],
                        'importe' => $v['ventTotBruto'],
                        'descuento' => $v['ventTotDescuento'],
                        'importe_final' => $v['ventTotNeto'],
                        'forma_pago' => $v['frpaAbreviatura'],
                        'forma_codigo' => $v['frpaCodigo'],
                        'descripcion' => null,
                        'sucursal' => $v['estrDescripcion'],
                        'codigo_sucursal' => $v['estrCodigo'],
                        'fecha' => $fechaFormateada,
                        'forma_venta' => $v['ventTipo'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, $nuevasVentas);
                Venta::insert($insertData);
            }


        } catch (\Exception $e) {
            SupabaseService::LOG('error', "Error crítico: {$e->getMessage()}");
        }
        SupabaseService::LOG('schedule_plano', 'ingresadas fecha ' . $this->fecha);
    }
}
