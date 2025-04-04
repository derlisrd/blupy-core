<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Services\FarmaService;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VentasController extends Controller
{
    public function periodoForma(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'periodo' => 'nullable|date_format:Y-m',
            'forma_codigo' => 'nullable|numeric',
            'alianza' => 'nullable|numeric|in:0,1',
        ]);
        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        // Si no hay periodo, responder con error
        if (!$req->has('periodo') || !$req->periodo)
            return response()->json(['success' => false, 'message' => 'Periodo es requerido'], 400);


        // Si no hay forma_codigo, responder con error
        if (!$req->has('forma_codigo')) {
            return response()->json(['success' => false, 'message' => 'forma_codigo es requerido'], 400);
        }

        // Crear fechas una sola vez
        $carbon = Carbon::createFromFormat('Y-m', $req->periodo);
        $fechaInicio = $carbon->copy()->startOfMonth()->format('Y-m-d');
        $fechaFin = $carbon->copy()->endOfMonth()->format('Y-m-d');

        // Construir la consulta más eficiente
        $query = Venta::select('id', 'codigo', 'factura_numero','documento','forma_codigo','importe_final','sucursal','operacion','fecha','adicional') // Solo columnas necesarias
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->where('forma_codigo', $req->forma_codigo)
            ->orderBy('fecha', 'desc')
            ;

        // Aplicar filtro de alianza
        if ($req->alianza === '1') {
            $query->whereNotNull('adicional');
        } else {
            $query->whereNull('adicional');
        }

        return response()->json([
            'success' => true,
            'message' => 'Ventas',
            'results' => $query->get()
        ]);
    }


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
            $acumuladoBlupy3CuotasAlianza = (clone $query)->where('forma_codigo', '140')->sum('importe_final');
            $acumuladoBlupy4CuotasAlianza = (clone $query)->where('forma_codigo', '136')->sum('importe_final');

            return response()->json([
                'success' => true,
                'message' => 'Acumulados del mes actual',
                'results' => [
                    'periodo' => $periodo,
                    'total' => (int)$acumuladoTotal,
                    'blupyDigital' => [
                        'total' => (int)$acumuladoBlupyDigital,
                        'codigo' => 135,
                        'alianza' => false,
                        'descripcion' => 'Blupy Digital'
                    ],
                    'blupy1DiaFuncionarios' => [
                        'total' => (int)$acumuladoBlupy1DiaFuncionarios,
                        'codigo' => 129,
                        'alianza' => false,
                        'descripcion' => 'Blupy 1 día Funcionarios'
                    ],
                    'blupy1DiaAlianzas' => [
                        'total' => (int)$acumuladoBlupy1DiaAlianzas,
                        'codigo' => 129,
                        'alianza' => true,
                        'descripcion' => 'Blupy 1 día Alianzas'
                    ],
                    'blupy3Cuotas' => [
                        'total' => (int)$acumuladoBlupy3Cuotas,
                        'codigo' => 127,
                        'alianza' => false,
                        'descripcion' => 'Blupy 3 cuotas'
                    ],
                    'blupy3CuotasAlianza' => [
                        'total' => (int)$acumuladoBlupy3CuotasAlianza,
                        'codigo' => 140,
                        'alianza' => true,
                        'descripcion' => 'Blupy 3 cuotas Alianzas'
                    ],
                    'blupy3CuotasDigital' => [
                        'total' => (int)$acumuladoBlupy3CuotasDigital,
                        'codigo' => 139,
                        'alianza' => false,
                        'descripcion' => 'Blupy 3 cuotas Digital'
                    ],
                    'blupy4CuotasAlianza' => [
                        'total' => (int)$acumuladoBlupy4CuotasAlianza,
                        'codigo' => 136,
                        'alianza' => true,
                        'descripcion' => 'Blupy 4 cuotas Alianzas'
                    ],
                ]
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function acumulados()
    {
        // Una sola consulta para obtener todos los acumulados por forma_codigo
        $acumuladosPorForma = Venta::select('forma_codigo', DB::raw('SUM(importe_final) as total'))
            ->where('operacion','<>','DEVO')
            ->groupBy('forma_codigo')
            ->get()
            ->keyBy('forma_codigo');

        // Una consulta separada para el caso especial de forma_codigo 129 con adicional nulo o no nulo
        $acumuladosFuncionariosAlianzas = Venta::select(
            'forma_codigo',
            DB::raw('CASE WHEN adicional IS NULL THEN "funcionarios" ELSE "alianzas" END as tipo'),
            DB::raw('SUM(importe_final) as total')
        )
            ->where('operacion','<>','DEVO')
            ->where('forma_codigo', '129')
            ->groupBy('forma_codigo', DB::raw('CASE WHEN adicional IS NULL THEN "funcionarios" ELSE "alianzas" END'))
            ->get();

        // Extraer los valores para funcionarios y alianzas
        $acumuladoBlupy1DiaFuncionarios = 0;
        $acumuladoBlupy1DiaAlianzas = 0;

        foreach ($acumuladosFuncionariosAlianzas as $item) {
            if ($item->tipo == 'funcionarios') {
                $acumuladoBlupy1DiaFuncionarios = (int)$item->total;
            } else {
                $acumuladoBlupy1DiaAlianzas = (int)$item->total;
            }
        }

        // Calcular el total general
        $acumuladoTotal = Venta::sum('importe_final');

        return response()->json([
            'success' => true,
            'message' => 'Acumulados',
            'results' => [
                'periodo' => null,
                'total' => (int)$acumuladoTotal,
                'blupyDigital' => (int)($acumuladosPorForma['135']->total ?? 0),
                'blupy1DiaFuncionarios' => $acumuladoBlupy1DiaFuncionarios,
                'blupy1DiaAlianzas' => $acumuladoBlupy1DiaAlianzas,
                'blupy3Cuotas' => (int)($acumuladosPorForma['127']->total ?? 0),
                'blupy3CuotasAso' => (int)($acumuladosPorForma['140']->total ?? 0),
                'blupy3CuotasDigital' => (int)($acumuladosPorForma['139']->total ?? 0),
                'blupy4CuotasAso' => (int)($acumuladosPorForma['136']->total ?? 0),
            ]
        ]);
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
