<?php

use App\Http\Controllers\BlupyApp\AuthController;
use App\Http\Controllers\BlupyApp\AWSController;
use App\Http\Controllers\BlupyApp\ConsultasController;
use App\Http\Controllers\BlupyApp\CuentasController;
use App\Http\Controllers\BlupyApp\DatosController;
use App\Http\Controllers\BlupyApp\DeviceController;
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


Route::post('/scan',[AWSController::class,'scanearDocumento']);

Route::post('/validar-email',[ValidacionesController::class,'validarEmail']);
Route::post('/confirmar-email',[ValidacionesController::class,'confirmarEmail']);
Route::post('/validar-telefono',[ValidacionesController::class,'validarTelefono']);
Route::post('/confirmar-telefono',[ValidacionesController::class,'confirmarTelefono']);

Route::post('/codigo-nuevo-dispositivo',[DeviceController::class,'codigoNuevoDispositivo']);
Route::post('/confirmar-nuevo-dispositivo',[DeviceController::class,'confirmarNuevoDispositivo']);

//Route::middleware(Authenticate::using('api'))->group(function(){
Route::middleware('auth:api')->group(function(){

    Route::post('/check-token',[AuthController::class,'checkToken']);
    Route::put('/refresh-token',[AuthController::class,'refreshToken']);
    Route::delete('/logout',[AuthController::class,'logout']);

    Route::delete('/eliminar-cuenta',[AuthController::class,'eliminarCuenta']);

    Route::get('/tarjetas',[CuentasController::class,'tarjetas']);
    Route::get('/movimientos',[CuentasController::class,'movimientos']);
    Route::get('/extracto',[CuentasController::class,'extracto']);

    Route::get('/solicitudes',[SolicitudesController::class,'solicitudes']);
    Route::post('/solicitar-credito',[SolicitudesController::class,'solicitarCredito']);
    Route::post('/solicitar-ampliacion',[SolicitudesController::class,'solicitarAmpliacion']);
    Route::post('/solicitar-adicional',[SolicitudesController::class,'solicitarAdicional']);

    Route::put('/cambiar-contrasena',[UserPrivate::class,'cambiarContrasena']);

    Route::put('/cambiar-celular',[DatosController::class,'cambiarCelular']);
    Route::put('/confirma-cambiar-celular',[DatosController::class,'confirmaCambiarCelular']);
    Route::put('/cambiar-email',[DatosController::class,'cambiarEmail']);
    Route::put('/confirma-cambiar-email',[DatosController::class,'confirmaCambiarEmail']);

    Route::get('/consultar-qr',[QRController::class,'consultar']);
    Route::post('/autorizar-qr',[QRController::class,'autorizar']);

    Route::get('/ciudades',[ConsultasController::class,'ciudades']);
    Route::get('/barrios-por-ciudad/{idCiudad}',[ConsultasController::class,'barriosPorCiudad']);

    Route::get('/tipos-laboral',[ConsultasController::class,'tiposLaboral']);
    Route::get('/profesiones',[ConsultasController::class,'profesiones']);


});
