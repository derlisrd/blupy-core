<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Services\InfinitaService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private $infinitaService;

    public function __construct()
    {
        $this->infinitaService = new InfinitaService();
    }

    public function olvideContrasena(Request $req){

    }

    public function recuperarContrasenaPorEmail(Request $req){

    }

    public function recuperarContrasenaPorCelularSms(Request $req){

    }

    public function restablecerContrasena(Request $req){

    }

    public function cambiarContrasena(Request $req){

    }
    //
    public function cambiarEmail(Request $req){

    }

    // cambiar celular o telefono
    public function cambiarNumeroCelular(){

    }


    public function eliminarCuenta(){

    }

    public function generarCodigoEliminarCuenta(){

    }

    public function confirmarEliminarCuenta(){

    }


    protected function cambiosEnInfinita(){

    }


}
