<?php

namespace App\Http\Controllers\BlupyApp;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PdfController extends Controller
{
    public function recibo(Request $req){
        $validate = Validator::make($req->all(),[
            'monto'=>'required|numeric',
            'fecha'=>'required|date',
            'numero'=>'required',
            'hora'=>'required',
            'cuenta' => 'required',
        ]);

        if($validate->fails())
            return response()->json(['success'=>false,'message'=>$validate->errors()->first()],400);

        $data = [
            'monto' => $req->monto,
            'fecha' => $req->fecha,
            'numero' => $req->numero,
            'hora' => $req->hora,
            'cuenta' => $req->cuenta,
        ];

        //$pdf = Pdf::loadView('pdf.recibo', $data);
        //return $pdf->download('invoice.pdf');
        return response()->json(['success'=>true,'message'=>'Recibo generado correctamente.']);
    }
}
