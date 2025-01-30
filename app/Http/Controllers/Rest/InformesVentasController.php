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
            'fecha1' => ['required', 'regex:/^(0[1-9]|1[0-2])\-\d{4}$/'], // MM/YYYY
            'fecha2' => ['required', 'regex:/^(0[1-9]|1[0-2])\-\d{4}$/']  // MM/YYYY
        ]);

        if ($validator->fails())
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);

        $fecha1 = explode('-', $req->fecha1);
        $fecha2 = explode('-', $req->fecha2);

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

    public function topSucursalesTickets()
    {
        // Agrupar ventas por sucursal para ambos meses
        $ventas1 = Venta::selectRaw('sucursal, COUNT(*) as tickets, SUM(importe) as total')
            ->groupBy('sucursal')
            ->take(10)
            ->get();
        // Sucursal con más ventas (tickets) en cada mes
        $topSucursal1 = $ventas1->sortByDesc('tickets')->first();

        return response()->json([
            'success' => true,
            'results' => [
                'top' => $ventas1,
                'top_sucursal' => $topSucursal1 ? [
                    'sucursal' => $topSucursal1->sucursal,
                    'tickets' => $topSucursal1->tickets,
                    'total' => (int) $topSucursal1->total
                ] : null
            ]
        ]);
    }

    public function topSucursalesIngresos()
    {
        $sucursalMayorFacturacion = Venta::selectRaw("sucursal, SUM(importe) as total_facturacion, COUNT(*) as tickets")
            ->groupBy('sucursal')
            ->orderByDesc('total_facturacion')
            ->first();

        return response()->json([
            'success' => true,
            'results' => $sucursalMayorFacturacion ? [
                'sucursal' => $sucursalMayorFacturacion->sucursal,
                'tickets' => $sucursalMayorFacturacion->tickets,
                'total_facturacion' => (int) $sucursalMayorFacturacion->total_facturacion
            ] : null
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
