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
        $farmaService = new FarmaService();

        $res = (object)$farmaService->ventasRendidas($this->fecha);
        $data = (object) $res->data;
        if (property_exists($data, 'result')) {
            $ventas = $data->result;
            foreach ($ventas as $v) {
                $venta = Venta::where('codigo', $v['ventCodigo'])->first();
                if (!$venta) {
                    $date = Carbon::parse($v['ventFecha'],'UTC');
                    $date->setTimezone('America/Asuncion');
                    $fechaFormateada = $date->format('Y-m-d H:i:s');
                    $cliente = Cliente::where('cedula', $v['cedula'])->first();
                    $cliente_id = $cliente ? $cliente->id : null;
                    $ventaCreada = Venta::create([
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
                        'fecha' => $fechaFormateada,
                        'forma_venta' => $v['ventTipo']
                    ]);
                }
            }
        }

        SupabaseService::LOG('schedule_plano','ingresadas fecha '.$this->fecha);
    }

}
