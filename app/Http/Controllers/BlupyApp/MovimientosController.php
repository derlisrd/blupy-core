<?php
namespace App\Http\Controllers\BlupyApp;


use App\Http\Controllers\Controller;
use App\Services\FarmaService;
use App\Services\InfinitaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MovimientosController extends Controller
{
    private $infinitaService;
    private $farmaService;

    public function __construct()
    {
        $this->infinitaService = new InfinitaService();
        $this->farmaService = new FarmaService();
    }

    public function movimientos(Request $req){
        $validator = Validator::make($req->only('periodo'),['periodo'=>'required'],['periodo.required'=>'El periodo es requerido (MM-AAAA).']);
        if($validator->fails()) return response()->json(['success'=>false,'messages'=>$validator->errors()->first() ], 400);

        $results = [];
        $user = $req->user();
        if(isset($req->cuenta)){
            //infinita
            $resInfinita = (object) $this->infinitaService->movimientosPorFecha($req->cuenta,$req->periodo);
            $infinita = (object) $resInfinita->data;
            if(property_exists($infinita,'Tarj')){
                foreach($infinita->Tarj['Mov'] as $val){
                    array_push($results,[
                        'comercio'=>$val['TcComNom'],
                        'descripcion'=>$val['MvDes'],
                        'detalles'=> $val['TcMovDes'],
                        'fecha'=>$val['TcMovFec'],
                        'monto'=>(int) $val['TcMovImp']
                    ]);
                }
            }
        }
        if(!isset($req->cuenta)){
            //farma
            $resFarma = (object) $this->farmaService->movimientos($user->cliente->cedula,$req->periodo);
            $farma = (object) $resFarma->data;
            if(property_exists($farma,'result')){
                foreach($farma->result['movimientos'] as $val){
                    array_push($results,[
                        'comercio'=>'Farma S.A.',
                        'descripcion'=>$val['ticoDescripcion'],
                        'detalles'=> $val['ticoCodigo'].' '.$val['evenNumero'],
                        'fecha'=>$val['evenFecha'],
                        'monto'=>$val['monto']
                    ]);
                }
            }
        }

        return response()->json([
            'success'=>true,
            'results'=>$results
        ]);
    }

    public function extracto(Request $req){

    }
}
