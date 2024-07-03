<?php

use App\Http\Controllers\BlupyApp\AuthController;
use App\Http\Controllers\BlupyApp\ConsultasController;
use App\Http\Controllers\BlupyApp\CuentasController;
use App\Http\Controllers\BlupyApp\QRController;
use App\Http\Controllers\BlupyApp\SolicitudesController;
use App\Http\Controllers\BlupyApp\UserController as UserPrivate;
use App\Http\Controllers\Public\UserController as UserPublic;
use App\Http\Controllers\BlupyApp\ValidacionesController;
use Illuminate\Support\Facades\Route;


Route::post('/login',[AuthController::class,'login']);
Route::post('/register',[AuthController::class,'register']);

Route::post('/olvide-contrasena',[UserPublic::class,'olvideContrasena']);
Route::post('/validar-codigo-recuperacion',[UserPublic::class,'validarCodigoRecuperacion']);
Route::post('/restablecer-contrasena',[UserPublic::class,'restablecerContrasena']);

Route::get('/verificar-documento',[ConsultasController::class,'verificarExisteDocumento']);
Route::get('/ciudades',[ConsultasController::class,'ciudades']);
Route::get('/barrios',[ConsultasController::class,'barrios']);

Route::post('/scan',[ValidacionesController::class,'scanearDocumento']);


//Route::middleware(Authenticate::using('api'))->group(function(){
Route::middleware('auth:api')->group(function(){

    Route::post('/check-token',[AuthController::class,'checkToken']);
    Route::post('/refresh-token',[AuthController::class,'refreshToken']);
    Route::post('/logout',[AuthController::class,'logout']);

    Route::get('/tarjetas',[CuentasController::class,'tarjetas']);
    Route::get('/movimientos',[CuentasController::class,'movimientos']);
    Route::get('/extracto',[CuentasController::class,'extracto']);

    Route::get('/solicitudes',[SolicitudesController::class,'solicitudes']);
    Route::post('/solicitar-credito',[SolicitudesController::class,'solicitarCredito']);
    Route::post('/solicitar-ampliacion',[SolicitudesController::class,'solicitarAmpliacion']);
    Route::post('/solicitar-adicional',[SolicitudesController::class,'solicitarAdicional']);

    Route::post('/cambiar-contrasena',[UserPrivate::class,'cambiarContrasena']);
    Route::post('/cambiar-celular',[UserPrivate::class,'cambiarCelular']);
    Route::post('/cambiar-email',[UserPrivate::class,'cambiarEmail']);

    Route::get('/consultar-qr',[QRController::class,'consultar']);
    Route::post('/autorizar-qr',[QRController::class,'autorizar']);

});
