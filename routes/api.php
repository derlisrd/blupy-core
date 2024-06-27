<?php

use App\Http\Controllers\BlupyApp\AuthController;
use App\Http\Controllers\BlupyApp\ConsultasController;
use App\Http\Controllers\BlupyApp\CuentasController;
use App\Http\Controllers\BlupyApp\MovimientosController;
use App\Http\Controllers\BlupyApp\QRController;
use App\Http\Controllers\BlupyApp\SolicitudesController;
use App\Http\Controllers\BlupyApp\UserController;
use App\Http\Controllers\BlupyApp\ValidacionesController;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\Middleware\Authenticate;

Route::post('/login',[AuthController::class,'login']);
Route::post('/register',[AuthController::class,'register']);

Route::post('/olvide-contrasena',[UserController::class,'olvideContrasena']);


Route::get('/verificar-documento',[ConsultasController::class,'verificarExisteDocumento']);
Route::get('/ciudades',[ConsultasController::class,'ciudades']);
Route::get('/barrios',[ConsultasController::class,'barrios']);

Route::post('/scan',[ValidacionesController::class,'scanearDocumento']);


Route::middleware(Authenticate::using('api'))->group(function(){

    Route::post('/check-token',[AuthController::class,'checkToken']);
    Route::post('/refresh-token',[AuthController::class,'refreshToken']);
    Route::post('/logout',[AuthController::class,'logout']);

    Route::get('/tarjetas',[CuentasController::class,'tarjetas']);

    Route::get('/movimientos',[MovimientosController::class,'movimientos']);

    Route::get('/solicitudes',[SolicitudesController::class,'solicitudes']);

    Route::get('/consultar-qr',[QRController::class,'consultar']);
    Route::post('/autorizar-qr',[QRController::class,'autorizar']);

});
