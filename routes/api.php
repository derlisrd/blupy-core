<?php

use App\Http\Controllers\BlupyApp\AuthController;
use App\Http\Controllers\BlupyApp\AWSController;
use App\Http\Controllers\BlupyApp\ConsultasController;
use App\Http\Controllers\BlupyApp\CuentasController;
use App\Http\Controllers\BlupyApp\DatosController;
use App\Http\Controllers\BlupyApp\DeviceController;
use App\Http\Controllers\BlupyApp\FarmaController;
use App\Http\Controllers\BlupyApp\InformacionesController;
use App\Http\Controllers\BlupyApp\NotificacionesController;
use App\Http\Controllers\BlupyApp\QRController;
use App\Http\Controllers\BlupyApp\SolicitudesController;
use App\Http\Controllers\BlupyApp\UserController as UserPrivate;
use App\Http\Controllers\Public\UserController as UserPublic;
use App\Http\Controllers\BlupyApp\ValidacionesController;
use App\Http\Controllers\Public\VersionController;
use Illuminate\Support\Facades\Route;


Route::post('/login',[AuthController::class,'login']);
Route::post('/confirmar-nuevo-dispositivo',[DeviceController::class,'confirmarNuevoDispositivo']);
Route::post('/codigo-nuevo-dispositivo',[DeviceController::class,'codigoNuevoDispositivo']);
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

Route::get('/enviame-codigo-sms',[ValidacionesController::class,'enviameCodigoSMS']);

Route::get('/verificar-version',[VersionController::class,'verificarVersion']);

Route::middleware('auth:api')->group(function(){

    Route::get('/info',[InformacionesController::class,'InfoPopUpInicial']);

    Route::get('/mis-dispositivos',[CuentasController::class,'misDispositivos']);
    Route::delete('/eliminar-dispositivo',[CuentasController::class,'eliminarDispositivo']);

    Route::get('/notificaciones',[NotificacionesController::class,'porUser']);

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
    Route::post('/agregar-adicional',[SolicitudesController::class,'agregarAdicional']);
    Route::get('/verificar-estado-solicitud',[SolicitudesController::class,'verificarEstadoSolicitud']);

    Route::put('/cambiar-contrasena',[UserPrivate::class,'cambiarContrasena']);

    Route::put('/solicitar-cambiar-celular',[DatosController::class,'solicitarCambiarCelular']);
    Route::put('/confirmar-cambiar-celular',[DatosController::class,'confirmarCambiarCelular']);
    Route::put('/solicitar-cambiar-email',[DatosController::class,'solicitarCambiarEmail']);
    Route::put('/confirmar-cambiar-email',[DatosController::class,'confirmarCambiarEmail']);

    Route::get('/consultar-qr/{id}',[QRController::class,'consultar']);
    Route::post('/autorizar-qr',[QRController::class,'autorizar']);

    Route::get('/ciudades',[ConsultasController::class,'ciudades']);
    Route::get('/barrios-por-ciudad/{idCiudad}',[ConsultasController::class,'barriosPorCiudad']);

    Route::get('/tipos-laboral',[ConsultasController::class,'tiposLaboral']);
    Route::get('/profesiones',[ConsultasController::class,'profesiones']);

    Route::get('/sucursales-cercanas',[FarmaController::class,'sucursalesCercanas']);

});


<<<<<<< HEAD

=======
>>>>>>> dev
