<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use App\Traits\Helpers;
use Illuminate\Http\Request;

class FarmaController extends Controller{

    use Helpers;

    public function sucursalesCercanas(Request $req){
        $sucu = Sucursal::all();
        $filtrado = [];
        if(!$req->latitud || !$req->longitud){
            return response()->json([
                'success' => true,
                'results' => $sucu
            ]);
        }

        foreach ($sucu as $key => $value) {
            $diskm = (int)$this->distancia($req->latitud, $req->longitud, $value['latitud'], $value['longitud']);
            if( $diskm <= 5){
                array_push($filtrado,$value);
            }
        }


        return response()->json([
            'success' => true,
            'results' => $filtrado
        ]);
    }
}
