<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\FarmaService;
use App\Services\InfinitaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConsultasController extends Controller
{


    public function infoSucursal(Request $req){

        $validator = Validator::make($req->only(['punto']), ['punto' => 'required']);
        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

            $res = (new FarmaService())->infoSucursal($req->punto);
            $dataFarma = (object)$res['data'];

            return response()->json([
                'success'=> true,
                'results' => $dataFarma->result
            ]);

    }




    public function clienteFarmaMiCredito(Request $req)
    {
        $validator = Validator::make($req->only(['cedula']), ['cedula' => 'required']);

        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        $cliente = Cliente::where('cedula', $req->cedula)->first();




        $infinitaRes =  (new InfinitaService())->ListarTarjetasPorDoc($req->cedula);
        $infinitaData = (object)$infinitaRes['data'];
        $infinitaResult = null;
        if (property_exists($infinitaData, 'Tarjetas')) {
            $infinitaResult = $infinitaData->Tarjetas[0];
        }

        $res = (new FarmaService())->cliente($req->cedula);
        $dataFarma = (object)$res['data'];

        $farmaResult = null;

        if (property_exists($dataFarma, 'result')) {
            $result = $dataFarma->result;
            if (count($result) > 0) {
                $farmaResult = $result[0];
            }
        }
        return response()->json([
            'success' => true,
            'message' => '',
            'results' => [
                'registro' => $cliente ? true : false,
                'farma' => $farmaResult,
                'micredito' => $infinitaResult,
            ]
        ]);
    }

    public function clienteFarmaPorCodigo(Request $req)
    {
        $validator = Validator::make($req->only(['codigo']), ['codigo' => 'required']);

        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);


        $farma = new FarmaService();



        $res = $farma->clientePorCodigo($req->codigo);
        $dataFarma = (object)$res['data'];

        $farmaResult = null;

        if (property_exists($dataFarma, 'result')) {
            $result = $dataFarma->result;
            if (count($result) > 0) {
                $farmaResult = $result[0];
            }
        }
        return response()->json([
            'success' => true,
            'message' => '',
            'results' => [
                'registro' => true,
                'farma' => $farmaResult,
                'micredito' => null,
            ]
        ]);
    }

    public function movimientos(Request $req)
    {
        $validator = Validator::make($req->all(), ['cedula' => 'required', 'periodo' => 'required']);
        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        $farmaService = new FarmaService();

        $resFarma = $farmaService->movimientos2($req->cedula, $req->periodo);
        $farma = (object) $resFarma['data'];
        $results = [];
        if (property_exists($farma, 'result')) {
            foreach ($farma->result as $val) {
                $date = Carbon::parse($val['evenFecha'], 'UTC');
                $date->setTimezone('America/Asuncion');
                $fecha = $date->format('Y-m-d');
                $hora = $date->format('H:i:s');
                array_push($results, [
                    'comercio' => 'Farma S.A.',
                    'descripcion' => $val['ticoDescripcion'],
                    'detalles' => $val['ticoCodigo'] . ' ' . $val['evenNumero'],
                    'fecha' => $fecha,
                    'hora' => $hora,
                    'monto' => $val['monto']
                ]);
            }
        }
        return response()->json([
            'success' => true,
            'message' => '',
            'results' => $results
        ]);
    }
}
