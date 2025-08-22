<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Services\FarmaService;
use App\Services\InfinitaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MovimientosController extends Controller
{
    
    public function movimientos(Request $req){
        $validator = Validator::make($req->all(), trans('validation.movimientos'), trans('validation.movimientos.messages'));
        if($validator->fails())
            return response()->json(['success'=>false,'message'=>$validator->errors()->first() ], 400);

        $cliente = $req->user()->cliente;
        $results = [];
        
        if($req->cuenta == '0' || $req->cuenta == 0){
            $farmaResponse = (new FarmaService())->movimientos2($cliente->cedula,$req->periodo);
            $farmaData = $farmaResponse['data'];
            if ($farmaData && isset($farmaData['result'])) {
                $movimientos = $farmaData['result'];
                foreach($movimientos as $val){
                    $date = Carbon::parse($val['evenFecha'], 'UTC')->setTimezone('America/Asuncion');
                    $results[] = [
                        'comercio' => 'Farma S.A.',
                        'descripcion' => $val['ticoDescripcion'],
                        'detalles' => $val['ticoCodigo'] . ' ' . $val['evenNumero'],
                        'fecha' => $date->format('Y-m-d'),
                        'hora' => $date->format('H:i:s'),
                        'monto' => $val['monto']
                    ];
                }
            } 
        }

        if($req->cuenta>0){
            $infiService = (new InfinitaService())->movimientosPorFecha($req->cuenta,$req->periodo,$req->numero_tarjeta);
            $infiData = $infiService['data'];

            if ($infiData && isset($infiData['Tarj']['Mov'])) {

                $movimientosInfi = $infiData['Tarj']['Mov'];
                foreach($movimientosInfi as $val){
                    $date = Carbon::parse($val['TcMovFec']);
                    $horario = Carbon::parse($val['TcMovCFh'], 'UTC')->setTimezone('America/Asuncion');
                    
                    $results[] = [
                        'comercio' => $val['TcComNom'],
                        'descripcion' => $val['MvDes'],
                        'detalles' => $val['TcMovDes'],
                        'fecha' => $date->format('Y-m-d'),
                        'hora' => $horario->format('H:i:s'),
                        'monto' => (int)$val['TcMovImp'],
                        'numero' => $val['TcMovNro'],
                    ];
                }
            }


            /* $date = Carbon::parse($val['TcMovFec']);
            $horario = Carbon::parse($val['TcMovCFh'], 'UTC')->setTimezone('America/Asuncion');
            
            return [
                'comercio' => $val['TcComNom'],
                'descripcion' => $val['MvDes'],
                'detalles' => $val['TcMovDes'],
                'fecha' => $date->format('Y-m-d'),
                'hora' => $horario->format('H:i:s'),
                'monto' => (int)$val['TcMovImp'],
                'numero' => $val['TcMovNro'],
            ]; */
        }



        return response()->json([
            'success'=>true,
            'message'=>'',
            'results'=>$results
        ]);
    }
}
