<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InformesVentasController extends Controller
{
    public function compararMeses(Request $req)
    {

        $validator = Validator::make($req->all(), [
            'fecha1' => ['nullable', 'regex:/^(0[1-9]|1[0-2])\-\d{4}$/'], // MM-YYYY
            'fecha2' => ['nullable', 'regex:/^(0[1-9]|1[0-2])\-\d{4}$/']  // MM-YYYY
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        // Obtener el mes actual y el mes anterior
        $currentMonth = Carbon::now()->format('m-Y');
        $previousMonth = Carbon::now()->subMonth()->format('m-Y');

        // Asignar fechas si son nulas o no existen
        $fecha1 = $req->fecha1 ?? $previousMonth;
        $fecha2 = $req->fecha2 ?? $currentMonth;

        // Convertir las fechas en objetos Carbon
        $fecha1 = explode('-', $fecha1);
        $fecha2 = explode('-', $fecha2);

        $fecha1_inicio = Carbon::createFromDate($fecha1[1], $fecha1[0], 1)->startOfDay()->format('Y-m-d H:i:s'); // Primer día
        $fecha1_fin = Carbon::createFromDate($fecha1[1], $fecha1[0], 1)->endOfMonth()->endOfDay()->format('Y-m-d H:i:s');; // Último día

        $fecha2_inicio = Carbon::createFromDate($fecha2[1], $fecha2[0], 1)->startOfDay()->format('Y-m-d H:i:s');;
        $fecha2_fin = Carbon::createFromDate($fecha2[1], $fecha2[0], 1)->endOfMonth()->endOfDay()->format('Y-m-d H:i:s');;

        $ventas1 = Venta::whereBetween('fecha', [$fecha1_inicio, $fecha1_fin]);
        $ventas2 = Venta::whereBetween('fecha', [$fecha2_inicio, $fecha2_fin]);



        $tickets1 = $ventas1->count();
        $tickets2 = $ventas2->count();

        $total1 = $ventas1->sum('importe');
        $total2 = $ventas2->sum('importe');




        return response()->json([
            'success' => true,
            'results' => [
                'tickets1' => $tickets1,
                'tickets2' => $tickets2,
                'total1' => (int)$total1,
                'total2' => (int)$total2
            ]
        ]);
    }

    public function topSucursalesTickets(Request $request)
    {
        // Obtener mes y año del request
        $mes = $request->mes; // Ejemplo: 8 para agosto
        $ano = $request->mes; // Ejemplo: 2024

        // Construir la consulta base
        $query = Venta::selectRaw('codigo_sucursal, sucursal, COUNT(*) as tickets, SUM(importe) as total')
            ->groupBy('codigo_sucursal', 'sucursal')
            ->orderByDesc('tickets')
            ->take(10);

        // Si se proporcionan mes y año, filtrar por ese mes
        if ($mes && $ano) {
            $query->whereYear('fecha', $ano)
                ->whereMonth('fecha', $mes);
        }

        $ventas = $query->get();

        return response()->json([
            'success' => true,
            'results' => $ventas
        ]);
    }

    public function topSucursalesIngresos(Request $request)
    {
        $mes = $request->mes; // Ejemplo: 8 para agosto
        $ano = $request->ano; // Ejemplo: 2024

        $query = Venta::selectRaw("codigo_sucursal,sucursal, SUM(importe) as total_facturacion, COUNT(*) as tickets")
            ->groupBy('sucursal', 'codigo_sucursal')
            ->orderByDesc('total_facturacion')
            ->take(10);

        if ($mes && $ano) {
            $query->whereYear('fecha', $ano)
                ->whereMonth('fecha', $mes);
        }

        $query = $query->get();
        return response()->json([
            'success' => true,
            'results' => $query
        ]);
    }

    public function topSucursalesTicketsPromedio()
    {
        $ventas = Venta::selectRaw('sucursal, SUM(importe) / COUNT(*) as promedio_ticket')
            ->groupBy('sucursal')
            ->orderByDesc('promedio_ticket')
            ->take(5)
            ->get();
        $topSucursal1 = $ventas->sortByDesc('promedio_ticket')->first();

        return response()->json([
            'success' => true,
            'results' => [
                'top' => $ventas,
                'top_sucursal' => $topSucursal1 ? [
                    'sucursal' => $topSucursal1->sucursal,
                    'total' => (int) $topSucursal1->total
                ] : null
            ]
        ]);
    }

    public function diaMasVenta()
    {
        $diaMasVentas = Venta::selectRaw("DATE(fecha) as fecha, COUNT(*) as tickets, SUM(importe) as total")
            ->groupBy('fecha')
            ->orderByDesc('tickets')
            ->first();

        $diaSemanaMasVentas = Venta::selectRaw("DAYNAME(fecha) as dia_semana, COUNT(*) as tickets, SUM(importe) as total")
            ->groupBy('dia_semana')
            ->orderByDesc('tickets')
            ->first();

        return response()->json([
            'success' => true,
            'results' => [
                'dia_mas_ventas' => $diaMasVentas ? [
                    'fecha' => $diaMasVentas->fecha,
                    'tickets' => $diaMasVentas->tickets,
                    'total' => (int) $diaMasVentas->total
                ] : null,
                'dia_semana_mas_ventas' => $diaSemanaMasVentas ? [
                    'dia_semana' => $diaSemanaMasVentas->dia_semana,
                    'tickets' => $diaSemanaMasVentas->tickets,
                    'total' => (int) $diaSemanaMasVentas->total
                ] : null
            ]
        ]);
    }

    public function mesMayorFacturacion(Request $req)
    {
        $mesMayorFacturacion = Venta::selectRaw("DATE_FORMAT(fecha, '%m-%Y') as mes, SUM(importe) as total_facturacion, COUNT(*) as tickets")
            ->groupBy('mes')
            ->orderByDesc('total_facturacion')
            ->first();

        return response()->json([
            'success' => true,
            'results' => $mesMayorFacturacion ? [
                'mes' => $mesMayorFacturacion->mes,
                'tickets' => $mesMayorFacturacion->tickets,
                'total_facturacion' => (int) $mesMayorFacturacion->total_facturacion
            ] : null
        ]);
    }
    public function formaPago()
    {
        $formasPago = Venta::selectRaw("forma_codigo,forma_pago, COUNT(*) as cantidad, SUM(importe) as total")
            ->groupBy('forma_pago', 'forma_codigo')
            ->orderByDesc('cantidad')
            ->get();

        return response()->json(
            [
                'success' => true,
                'results' => $formasPago
            ]
        );
    }
}
