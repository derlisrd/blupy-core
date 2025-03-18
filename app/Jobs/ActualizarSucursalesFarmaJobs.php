<?php

namespace App\Jobs;

use App\Models\Sucursal;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\FarmaService;

class ActualizarSucursalesFarmaJobs implements ShouldQueue
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
        $farma = new FarmaService();
        $res = $farma->sucursales();
        $data = (object) $res['data'];
        if (property_exists($data, 'result')) {
            $sucursales = $data->result;
            foreach ($sucursales as $s) {
                $datosAInsertar = [
                    'codigo' => $s['estrCodigo'],
                    'punto' => null,
                    'descripcion' => $s['estrDescripcion'],
                    'departamento' => $s['departamento'],
                    'ciudad' => $s['ciudad'],
                    'direccion' => $s['estrDireccion'],
                    'telefono' => $s['telefono'],
                    'latitud' => $s['latitud'],
                    'longitud' => $s['longitud'],
                    'disponible' => $s['estrDisponible'] === 'S' ? 1 : 0
                ];
                Sucursal::updateOrCreate(['codigo' => $s['estrCodigo']], $datosAInsertar);
            }
        }
    }
}
