<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Services\FarmaService;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class VentasController extends Controller
{
    public function index(Request $req) {}

    public function acumuladosMes(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'periodo' => 'nullable|date_format:Y-m'
        ]);

        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        try {


            // Usar el periodo del request o el mes actual si no se proporciona
            $periodo = $req->input('periodo') ?? date('Y-m');

            // Obtener el primer y último día del mes actual
            $fechaInicio = Carbon::createFromFormat('Y-m', $periodo)->startOfMonth()->format('Y-m-d');
            $fechaFin = Carbon::createFromFormat('Y-m', $periodo)->endOfMonth()->format('Y-m-d');

            // Consulta base para ventas del mes actual
            $query = Venta::whereBetween('fecha', [$fechaInicio, $fechaFin]);

            // Realizar todas las consultas con el filtro del mes actual
            $acumuladoTotal = (clone $query)->sum('importe_final');

            $acumuladoBlupyDigital = (clone $query)->where('forma_codigo', '135')->sum('importe_final');
            $acumuladoBlupy3CuotasDigital = (clone $query)->where('forma_codigo', '139')->sum('importe_final');

            $acumuladoBlupy1DiaFuncionarios = (clone $query)->where('forma_codigo', '129')->whereNull('adicional')->sum('importe_final');
            $acumuladoBlupy1DiaAlianzas = (clone $query)->where('forma_codigo', '129')->whereNotNull('adicional')->sum('importe_final');

            $acumuladoBlupy3Cuotas = (clone $query)->where('forma_codigo', '127')->sum('importe_final');
            $acumuladoBlupy3CuotasAso = (clone $query)->where('forma_codigo', '140')->sum('importe_final');
            $acumuladoBlupy4CuotasAso = (clone $query)->where('forma_codigo', '136')->sum('importe_final');

            return response()->json([
                'success' => true,
                'message' => 'Acumulados del mes actual',
                'results' => [
                    'periodo' => $periodo,
                    'total' => (int)$acumuladoTotal,
                    'blupyDigital' => (int)$acumuladoBlupyDigital,
                    'blupy1DiaFuncionarios' => (int)$acumuladoBlupy1DiaFuncionarios,
                    'blupy1DiaAlianzas' => (int)$acumuladoBlupy1DiaAlianzas,
                    'blupy3Cuotas' => (int)$acumuladoBlupy3Cuotas,
                    'blupy3CuotasAso' => (int)$acumuladoBlupy3CuotasAso,
                    'blupy3CuotasDigital' => (int)$acumuladoBlupy3CuotasDigital,
                    'blupy4CuotasAso' => (int)$acumuladoBlupy4CuotasAso,
                ]
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function acumulados(Request $req)
    {
        $acumuladoTotal = Venta::sum('importe_final');

        $acumuladoBlupyDigital = Venta::where('forma_codigo', '135')->sum('importe_final');
        $acumuladoBlupy3CuotasDigital = Venta::where('forma_codigo', '139')->sum('importe_final');

        $acumuladoBlupy1DiaFuncionarios = Venta::where('forma_codigo', '129')->whereNull('adicional')->sum('importe_final');
        $acumuladoBlupy1DiaAlianzas = Venta::where('forma_codigo', '129')->whereNotNull('adicional')->sum('importe_final');

        $acumuladoBlupy3Cuotas = Venta::where('forma_codigo', '127')->sum('importe_final');
        $acumuladoBlupy3CuotasAso = Venta::where('forma_codigo', '140')->sum('importe_final');
        $acumuladoBlupy4CuotasAso = Venta::where('forma_codigo', '136')->sum('importe_final');

        return response()->json(
            [
                'success' => true,
                'message' => 'Acumulados',
                'results' => [
                    'total' => (int)$acumuladoTotal,
                    'blupyDigital' => (int)$acumuladoBlupyDigital,
                    'blupy1DiaFuncionarios' => (int)$acumuladoBlupy1DiaFuncionarios,
                    'blupy1DiaAlianzas' => (int)$acumuladoBlupy1DiaAlianzas,
                    'blupy3Cuotas' => (int)$acumuladoBlupy3Cuotas,
                    'blupy3CuotasAso' => (int)$acumuladoBlupy3CuotasAso,
                    'blupy3CuotasDigital' => (int)$acumuladoBlupy3CuotasDigital,
                    'blupy4CuotasAso' => (int)$acumuladoBlupy4CuotasAso,
                ]
            ]
        );
    }

    public function porCodigo(Request $req)
    {
        try {
            $validator  = Validator::make($req->all(), ['codigo' => 'required']);
            if ($validator->fails())
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

            $farma = new FarmaService();
            $res = $farma->ventaPorCodigo($req->codigo);

            $venta = (object) $res['data'];

            if (property_exists($venta, 'result')) {
                return response()->json(['success' => true, 'message' => 'Ventas', 'results' => $venta->result], $res['status']);
            }

            return response()->json(['success' => false, 'message' => 'Hubo un error de servidor.'], 500);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function porFactura(Request $req)
    {
        try {
            $validator  = Validator::make($req->all(), ['factura' => 'required']);

            if ($validator->fails())
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

            $farma = new FarmaService();
            $res = $farma->ventaPorFactura($req->factura);

            $venta = (object) $res['data'];

            if (property_exists($venta, 'result')) {
                return response()->json(['success' => true, 'message' => 'Ventas', 'results' => $venta->result], $res['status']);
            }
            return response()->json(['success' => false, 'message' => 'Hubo un error de servidor.'], 500);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
